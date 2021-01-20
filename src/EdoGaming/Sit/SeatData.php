<?php

namespace EdoGaming\Sit;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;

class SeatData{
    /** @var Player */
    private $player;
    /** @var Block */
    private $block;
    /** @var Vector3 */
    private $position;
    /** @var int */
    private $eid;

    public function __construct(Player $player, Block $block){
        $this->eid = Entity::$entityCount++;
        $this->player = $player;
        $this->block = $block;
        $this->position = $block->add(0.5, 2.1, 0.5);
    }

    /**
     * @return Player
     */
    public function getPlayer() : Player{
        return $this->player;
    }

    /**
     * @return Block
     */
    public function getBlock() : Block{
        return $this->block;
    }

    /**
     * @param Position $pos
     *
     * @return bool
     */
    public function equals(Position $pos) : bool{
        return ($this->block->equals($pos) && $this->block->getLevel()->getId() === $pos->getLevel()->getId());
    }

    public function stand() : void{
        $pk = new SetActorLinkPacket();
        $pk->link = new EntityLink($this->eid, $this->player->getId(), EntityLink::TYPE_REMOVE, true, true);//TODO: Check causedByRider
        $this->player->getServer()->broadcastPacket($this->player->getServer()->getOnlinePlayers(), $pk);
        $pk = new RemoveActorPacket();
        $pk->entityUniqueId = $this->eid;
        $this->player->getServer()->broadcastPacket($this->player->getServer()->getOnlinePlayers(),$pk);
        $this->player->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
    }

    /**
     * @param Player[] $target
     */
    public function seat(array $target) : void{
        $addEntity = new AddActorPacket();
        $addEntity->entityRuntimeId = $this->eid;
        $addEntity->type = AddActorPacket::LEGACY_ID_MAP_BC[Entity::CHICKEN];
        $addEntity->position = $this->position;
        $flags = (1 << Entity::DATA_FLAG_IMMOBILE | 1 << Entity::DATA_FLAG_SILENT | 1 << Entity::DATA_FLAG_INVISIBLE);
        $addEntity->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags]];
        $setEntity = new SetActorLinkPacket();
        $setEntity->link = new EntityLink($this->eid, $this->player->getId(), EntityLink::TYPE_RIDER, true, true);//TODO: Check causedByRider
        $this->player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
        $this->player->getServer()->broadcastPacket($target, $addEntity);
        $this->player->getServer()->broadcastPacket($target, $setEntity);
    }

    public function optimizeRotation() : void{
        $pk = new MoveActorAbsolutePacket();
        $pk->position = $this->position;
        $pk->entityRuntimeId = $this->eid;
        $pk->xRot = $this->player->getPitch();
        $pk->yRot = $this->player->getYaw();
        $pk->zRot = $this->player->getYaw();
        $this->player->getServer()->broadcastPacket($this->player->getServer()->getOnlinePlayers(), $pk);
    }
}
