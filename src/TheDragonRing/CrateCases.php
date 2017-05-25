<?php

namespace TheDragonRing;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\inventory\Inventory;
use pocketmine\Player;
use pocketmine\Server;

use TheDragonRing\AutoSaveTask;

class CrateCases extends PluginBase implements Listener{

    public function onEnable(){
        $this->saveResource("config.yml");
        $this->saveResource("placedCrates.yml");
        $this->saveResource("users.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->placedCrates = new Config($this->getDataFolder() . "placedCrates.yml", Config::YAML);
        $this->users = new Config($this->getDataFolder() . "users.yml", Config::YAML);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSaveTask($this), 600);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getLogger()->info(TF::GREEN . "Enabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        $cmd = strtolower($command->getName());
        $crateTypes = array("BrineCrate", "ChristmasCrate", "EasterCrate", "HalloweenCrate", "FreedomChest");
        if($cmd == "keybal"){
            if(!isset($args[0]){
                $sender->sendMessage(TF::DARK_RED . "Usage: /keybal <type>");
            }else{
                foreach($crateTypes as $crateType){
                    if($args[0] != $crateType){
                        $sender->sendMessage("Invalid Type. Available Types: BrineCrate, ChristmasCrate, EasterCrate, HalloweenCrate & FreedomChest");
                    }else{
                        $message = str_replace("{keys}", $this->users->get($sender->getName())["keys"][$args[0]]), $this->config->get("settings")["viewKeyMessage"]);
                        $message = str_replace("{type}", $args[0], $message);
                        $sender->sendMessage($message);
                    }
                }
			}
        }
        if($cmd == "addcrate"){
            if(!isset($args[0]){
                $sender->sendMessage(TF::DARK_RED . "Usage: /addcrate <type>");
            }else{
                foreach($crateTypes as $crateType){
                    if($args[0] != $crateType){
                        $sender->sendMessage("Invalid Type. Available Types: BrineCrate, ChristmasCrate, EasterCrate, HalloweenCrate & FreedomChest");
                    }else{
                        $crateName = $args[0];
                        $cratePos = array_push($this->placedCrates->get($crateName), array("X" => $sender->getX(), "Y" => $sender->getY(), "Z" => $sender->getZ(), "Level" => $sender->getLevel()->getName()));
                        $this->placedCrates->set($crateName, $cratePos);
                        $sender->getLevel()->setBlock(new Vector3($sender->getX(), $sender->getY(), $sender->getZ()), Block::get(54));
                        $this->addFloatingText($sender->getLevel(), array("X" => $sender->getX(), "Y" => $sender->getY()+1, "Z" => $sender->getZ()), $crateName);
                        $sender->sendMessage(TF::GREEN . "A new $crateName has been succesfully added!");
                    }
                }
            }
        }
        if($cmd == "givekeys"){
            if(!isset($args[2]){
                $sender->sendMessage(TF::DARK_RED . "Usage: /givekeys <player> <type> <amount>");
            }else{
                foreach($crateTypes as $crateType){
                    if($args[1] != $crateType){
                        $sender->sendMessage("Invalid Type. Available Types: BrineCrate, ChristmasCrate, EasterCrate, HalloweenCrate & FreedomChest");
                    }else{
                        $message = str_replace("{keys}", $this->9getKeys($args[0], $sender->getName()), $this->config->get("settings")["viewKeyMessage"]);
                        $message = str_replace("{type}", $args[0], $message);
                        $sender->sendMessage($message);
                    }
                }
			}
		}

		public function onPlayerInteract(PlayerInteractEvent $event) {
			$player = $event->getPlayer();
			$name = strtolower($player->getName());
			$block = $event->getBlock();
			$x = $block->getX();
			$y = $block->getY();
			$z = $block->getZ();
			if($player->hasPermission('adc.admin')) {
				if($this->setting['set']) {
					$this->setting['set'] = false;
					$cName = $this->setting['case'];
					$case = $this->cases[$cName];
					$this->placedCases["$x.$y.$z"] = $case;
					$this->placedCases['world'] = strtolower($player->getLevel()->getName());
					$this->saveCase();
					$player->sendMessage("§a[aDonateCases] Кейс $cName установлен на $x $y $z");
					$this->addText($player, ['x' => $x + 0.5, 'y' => $y + 1.2, 'z' => $z + 0.6], $case['title']);
					return true;
				}
			}
			if(isset($this->placedCases["$x.$y.$z"])) {
				if($this->users[$name] < 1) {
					$player->sendMessage($this->config['noKey']);
					return true;
				}
				$this->users[$name]--;
				$case = $this->placedCases["$x.$y.$z"];
				$win = $this->getWin($case['items']);
				switch($case['type']) {
					
					case 'donate':
							$this->getServer()->getInstance()->dispatchCommand(new ConsoleCommandSender(), str_replace('{player}', $name, $win['command']));
							$win['count'] = 1;
						break;
				
					case 'money':
							$count = explode('/', $win['count']);
							if(!isset($count[1]))
								$count[1] = $count[0];
							$count = mt_rand($count[0], $count[1]);
							$win['count'] = $count;
							$this->eco->giveMoney($name, $count);
						break;

					case 'item':
							$item = $this->item($win);
							$player->getInventory()->addItem($item);
						break;

				}
				$player->sendMessage(str_replace(['{item}', '{count}'], [$win['chat'], $win['count']], $this->config['win']));
			}
		}

		public function onPlayerJoin(PlayerJoinEvent $event) {
			$name = strtolower($event->getPlayer()->getName());
			if(!isset($this->users[$name]))
				$this->users[$name] = 0;
		}

		private function getWin($list) {
			do {
				$item = $list[array_rand($list)];
				$chance = mt_rand(1, 100);
				if($chance <= $item['chance'])
					return $item;
			} while($chance > $item['chance']);
		}

		public function onPlayerRespawn(PlayerRespawnEvent $event) {
			foreach($this->placedCases as $coords => $case) {
				$coords = explode('.', $coords);
				$this->addText($event->getPlayer(), ['x' => $coords[0] + 0.5, 'y' => $coords[1] + 1.2, 'z' => $coords[2] + 0.6], $case['title']);
			}
		}

		private function addText($player, $coords, $text) {
			$player->getLevel()->addParticle(new FloatingTextParticle(new Vector3($coords['x'], $coords['y'], $coords['z']), '', str_replace('\n', "\n", $text)));
		}

		private function item($i) {
			if(empty($i['damage']))
				$i['damage'] = 0;
			if(empty($i['count']))
				$i['count'] = 1;
			$item = Item::get($i['id'], $i['damage'], $i['count']);
			if(!empty($i['name']))
				$item->setCustomName($i['name']);
			if($item->isArmor())
				if(!empty($i['color'])) {
					$rgb = explode(' ', $i['color']);
					if(count($rgb) == 3)
						$item->setCustomColor(Color::getRGB($rgb[0], $rgb[1], $rgb[2]));
				}
 			if(isset($i['enchants'])) {
 				if(is_array($i['enchants'])) {
 					foreach($i['enchants'] as $ench) {
 						if(!isset($ench['level']))
 							$ench['level'] = 1;
 						$ench = Enchantment::getEnchantment($ench['id'])->setLevel($ench['level']);
 						$item->addEnchantment($ench);
 					}
 				}
 			}
 			return $item;
		}

		public function saveCase() {
			$cfg = new Config($this->getDataFolder().'placedCases.yml', Config::YAML);
			$cfg->setAll($this->placedCases);
			$cfg->save();
			unset($cfg);
		}

		public function save() {
			$cfg = new Config($this->getDataFolder().'users.yml', Config::YAML);
			$cfg->setAll($this->users);
			$cfg->save();
			unset($cfg);
		}

	}

?>
