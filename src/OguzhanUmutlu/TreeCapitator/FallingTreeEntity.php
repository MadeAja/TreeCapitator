<?php

namespace OguzhanUmutlu\TreeCapitator;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Fallable;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use UnexpectedValueException;

class FallingTreeEntity extends Entity {
    /*** @var Item[] */
    public $drops = [];
    /*** @var Vector3|null */
    public $startPos = null;
    public const NETWORK_ID = self::FALLING_BLOCK;

    public $width = 0.98;
    public $height = 0.98;

    protected $baseOffset = 0.49;

    protected $gravity = 0.04;
    protected $drag = 0.02;

    /** @var Block */
    protected $block;

    public $canCollide = false;

    protected function initEntity() : void{
        parent::initEntity();
        $blockId = 0;
        if($this->namedtag->hasTag("TileID", IntTag::class))
            $blockId = $this->namedtag->getInt("TileID");
        elseif($this->namedtag->hasTag("Tile", ByteTag::class)) {
            $blockId = $this->namedtag->getByte("Tile");
            $this->namedtag->removeTag("Tile");
        }
        if($blockId === 0)
            throw new UnexpectedValueException("Invalid " . get_class($this) . " entity: block ID is 0 or missing");
        $damage = $this->namedtag->getByte("Data", 0);
        $this->block = BlockFactory::get($blockId, $damage);
        $this->propertyManager->setInt(self::DATA_VARIANT, $this->block->getRuntimeId());
    }

    public function canCollideWith(Entity $entity) : bool{
        return false;
    }

    public function canBeMovedByCurrents() : bool{
        return false;
    }

    public function attack(EntityDamageEvent $source) : void{
        if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
            parent::attack($source);
        } else {
            $source->setCancelled();
            parent::attack($source);
        }
    }

    public function getBlock() : int{
        return $this->block->getId();
    }

    public function getDamage() : int{
        return $this->block->getDamage();
    }

    public function saveNBT() : void{
        parent::saveNBT();
        $this->namedtag->setInt("TileID", $this->block->getId(), true);
        $this->namedtag->setByte("Data", $this->block->getDamage());
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->closed)
            return false;
        if(!$this->startPos)
            $this->startPos = $this->asVector3();
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if(!$this->isFlaggedForDespawn()){
            $pos = Position::fromObject($this->add(-$this->width / 2, $this->height, -$this->width / 2)->floor(), $this->getLevelNonNull());
            $this->block->position($pos);
            if($this->block instanceof Fallable)
                $this->block->tickFalling();
            if($this->onGround) {
                $this->flagForDespawn();
                foreach($this->drops as $drop) {
                    $checks = [
                        $this->level->getBlock($this->startPos->add(0, -1))->getId() == Block::DIRT,
                        $this->level->getBlock($this->startPos->add(0, -2))->getId() == Block::DIRT,
                        $this->level->getBlock($this->startPos->add(0, -3))->getId() == Block::DIRT
                    ];
                    if(($checks[0] || $checks[1] || $checks[2]) && $drop->getId() == Item::SAPLING && TreeCapitator::getInstance()->getConfig()->getNested("auto-sapling", true)) {
                        $place = null;
                        switch(true) {
                            case $checks[0]:
                                $place = $this->startPos;
                                break;
                            case $checks[1]:
                                $place = $this->startPos->add(0, 1);
                                break;
                            case $checks[2]:
                                $place = $this->startPos->add(0, 2);
                                break;
                        }
                        if($place) {
                            if($this->level->getBlock($place)->getId() == 0)
                                $this->level->setBlock($place, Block::get(Block::SAPLING, $drop->getDamage()));
                            else
                                $this->level->dropItem($this, $drop);
                        }
                    } else
                        $this->level->dropItem($this, $drop);
                }
                $damage = TreeCapitator::getInstance()->getConfig()->getNested("fall-damage", 1);
                if($damage > 0)
                    foreach($this->getViewers() as $player)
                        if($player->floor()->equals($this->floor()))
                            $player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_CONTACT, $damage));
                $hasUpdate = true;
            }
        }
        return $hasUpdate;
    }
}