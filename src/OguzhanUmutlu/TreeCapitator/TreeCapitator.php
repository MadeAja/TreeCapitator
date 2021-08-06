<?php

namespace OguzhanUmutlu\TreeCapitator;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class TreeCapitator extends PluginBase {
    /*** @var TreeCapitator */
    private static $instance;

    public function onEnable() {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    /*** @return TreeCapitator */
    public static function getInstance(): TreeCapitator {
        return self::$instance;
    }

    private static function convertToEntity(Player $player, Position $position, int $type, int $meta, int $loop = 0): void {
        if($loop > 30) return;
        $block = $position->level->getBlock($position);
        if(!in_array($block->getId(), [Block::LOG, Block::LOG2, Block::LEAVES, Block::LEAVES2])) return;
        $item = $player->getInventory()->getItemInHand();
        if(!$item instanceof TieredTool) return;
        $item->applyDamage(1);
        $nbt = Entity::createBaseNBT($position);
        $nbt->setInt("TileID", $block->getId());
        $nbt->setByte("Data", $block->getDamage());
        $nbt->setTag(Item::get($block->getItemId(), $block->getDamage())->nbtSerialize(0, "TreeCapitator"));
        $entity = Entity::createEntity("FallingSand", $position->level, $nbt);
        $entity->spawnToAll();
        $position->level->setBlock($position, Block::get(0));
        $position->level->addParticle(new DestroyBlockParticle($block, $block));
        self::startBreakTree($player, $block, $type, $meta, $loop);
    }

    public static function startBreakTree(Player $player, Block $block, int $type, int $meta, int $loop = 0) {
        $log = [Block::LOG, Block::LOG2][$type];
        $leave = [Block::LEAVES, Block::LEAVES2][$type];
        foreach($block->getAllSides() as $b) {
            if(($b->getId() == $log || $b->getId() == $leave) && $b->getDamage() == $meta) {
                $loop++;
                self::convertToEntity($player, $b, $type, $meta, $loop);
            }
        }
    }
}