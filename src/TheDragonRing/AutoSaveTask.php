<?php

namespace TheDragonRing;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

use TheDragonRing\CrateCases;

class AutoSaveTask extends PluginTask{

    public function __construct(CrateCases $CrateCases){
        parent::__construct($CrateCases);
        $this->crateCases = $CrateCases;
    }

    public function onRun($tick){
        $this->crateCases->save();
    }

}

?>