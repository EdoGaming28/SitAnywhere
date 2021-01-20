<?php

namespace EdoGaming\Sit;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class SitCommand extends PluginCommand{
    public function __construct(Plugin $owner){
        /** @var StairSeat $owner */
        parent::__construct($owner->getToggleCommandLabel(), $owner);
        $this->setPermission('sitanywhere.toggle');
        $this->setDescription('§bSit Anywhere By EdoGaming');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)){
            return;
        }

        if($sender instanceof Player){
            $sender->sendMessage($this->toggle(strtolower($sender->getName())) ? (TextFormat::GREEN . 'Kamu Sekarang Bisa Duduk Dimana Saja Dengan Mengklik Block') : (TextFormat::RED . 'Kamu Sekarang Tidak Bisa Duduk Dimana Saja'));
        }else{
            $this->getPlugin()->getLogger()->info('You can use this command in-game');
        }
    }

    private function toggle(string $name) : bool{
        /** @var StairSeat $owner */
        $owner = $this->getPlugin();
        $conf = $owner->getToggleConfig();
        $next = ($conf->exists($name)) ? !$conf->get($name) : false;
        $conf->set($name, $next);
        $conf->save();
        return $next;
    }
}
