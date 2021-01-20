<?php

namespace EdoGaming\Sit;

use pocketmine\block\Air;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\block\Stair;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Sit extends PluginBase{
    const CONFIG_VERSION = 2;

    /** @var SeatData[] */
    private $seatData = [];
    /** @var Config */
    private $toggleConfig;
    
    public function onEnable() {
		$this->toggleConfig = new Config($this->getDataFolder() . 'toggle.yml', Config::YAML);
        $this->reloadConfig();
        if(!$this->isCompatibleWithConfig()){
            $this->getLogger()->warning('Your configuration file is outdated. To update the config, please delete it at '.($this->getDataFolder() . 'config.yml'));
        }
        if($this->getConfig()->get('register-sit-command', true)){
            $this->getServer()->getCommandMap()->register($this->getName(), new SitCommand($this));
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
			
		if(empty($this->getConfig()->get("author"))){
			
			$this->getLogger()->info("Fatal error! Lu Ubah Author Anjir!");
			$this->getServer()->shutdown();
			
		} elseif($this->getConfig()->get("author") !== "EdoGaming"){
			
			$this->getLogger()->info("Fatal error! Lu Ubah Author anjir!");
			$this->getServer()->shutdown();
			
		} elseif($this->getDescription()->getAuthors()[0] !== "EdoGaming" or $this->getDescription()->getName() !== "SitAnywhere"){
			
			$this->getLogger()->info("Fatal error! Lu Edit Edit PL Nya");
			$this->getServer()->shutdown();
			
		} else {
			
			$this->getLogger()->info("§eSit Anywhere By EdoGaming");
		}
    }
    public function isEnabledStair(Block $block) : bool{
        $conf = $this->getConfig();
        $id = $block->getId();
        return true;
    }

    public function isAllowedHigherHeight() : bool{
        return (bool) $this->getConfig()->get('allow-seat-high-height', true);
    }

    public function isAllowedWhileSneaking() : bool{
        return (bool) $this->getConfig()->get('allow-seat-while-sneaking', true);
    }

    public function standWhenBreak() : bool{
        return (bool) $this->getConfig()->get('stand-up-when-break-block', true);
    }

    public function isToggleEnabled(Player $player) : bool{
        return (bool) $this->toggleConfig->get(strtolower($player->getName()), true);
    }

    public function canApplyWorld(Level $level) : bool{
        return ((bool) $this->getConfig()->get('apply-all-worlds', true)) ? true : (in_array($level->getFolderName(), array_map('trim', explode(',', (string) $this->getConfig()->get('apply-world-names', '')))));
    }

    public function isDisabledDamagesWhenSit() : bool{
        return (bool) $this->getConfig()->get('disable-damage-when-sit', false);
    }

    public function isEnabledCheckOnBlock() : bool{
        return (bool) $this->getConfig()->get('enable-check-up-block', false);
    }

    public function isAllowedOnlyRightClick() : bool{
        return (bool) $this->getConfig()->get('allow-only-right-click', false);
    }

    public function checkClick(PlayerInteractEvent $event) : bool{
        return $this->isAllowedOnlyRightClick() ? ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) : ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK);
    }

    public function getToggleCommandLabel() : string{
        return (string) $this->getConfig()->get('toggle-command-label', 'sit');
    }

    public function canSit(Player $player, Block $block) : bool{
        return (
            $this->isToggleEnabled($player) &&
            $this->canApplyWorld($block->getLevel()) &&
            $this->isEnabledStair($block) &&
            !$this->isSitting($player) &&
            ($this->isAllowedHigherHeight() || (!$this->isAllowedHigherHeight() && ($player->y >= $block->y))) &&
            ($this->isAllowedWhileSneaking() || (!$this->isAllowedWhileSneaking() && !$player->isSneaking())) &&
            (!$this->isEnabledCheckOnBlock() || ($this->isEnabledCheckOnBlock() && $block->getLevel()->getBlock($block->up()) instanceof Air))
        );
    }

    public function addSeatData(SeatData $data) : void{
        $this->seatData[] = $data;
    }

    public function getSeatDataByPlayer(Player $player) : ?SeatData{
        foreach($this->seatData as $seatDatum)
            if($player->getId() === $seatDatum->getPlayer()->getId())
                return $seatDatum;
        return null;
    }

    public function getSeatDataByPosition(Position $pos) : ?SeatData{
        foreach($this->seatData as $seatDatum)
            if($seatDatum->equals($pos))
                return $seatDatum;
        return null;
    }

    public function removeSeatDataByPosition(Position $pos) : bool{
        foreach($this->seatData as $key => $seatDatum)
            if($seatDatum->equals($pos)){
                $seatDatum->stand();
                unset($this->seatData[$key]);
                return true;
            }
        return false;
    }

    /**
     * Return player is sitting on the stairs.
     * Developers have to use this method to check whether player is sitting
     *
     * @param Player $player
     *
     * @return bool
     */
    public function isSitting(Player $player) : bool{
        return $this->getSeatDataByPlayer($player) instanceof SeatData;
    }

    /**
     * @return SeatData[]
     */
    public function getAllSeatData() : array{
        return $this->seatData;
    }

    public function getToggleConfig() : Config{
        return $this->toggleConfig;
    }

    private function isCompatibleWithConfig() : bool{
        return $this->getConfig()->get('config-version') == self::CONFIG_VERSION;
    }
}
