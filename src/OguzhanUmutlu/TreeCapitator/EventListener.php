<?php

namespace OguzhanUmutlu\TreeCapitator;

use pocketmine\block\Block;
use pocketmine\entity\object\FallingBlock;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityBlockChangeEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;

class EventListener implements Listener {
    public function blockDropEvent(EntityBlockChangeEvent $event) {
        $entity = $event->getEntity();
        if(!$entity instanceof FallingBlock || !$entity->namedtag->hasTag("TreeCapitator")) return;
        $event->setCancelled();
        $entity->level->dropItem($entity, Item::nbtDeserialize($entity->namedtag->getCompoundTag("TreeCapitator")));
        $entity->flagForDespawn();
    }

    public function onBreak(BlockBreakEvent $event) {
        $b = $event->getBlock();
        if(!in_array($b->getId(), [Block::LOG, Block::LOG2, Block::LEAVES, Block::LEAVES2])) return;
        TreeCapitator::startBreakTree($event->getPlayer(), $b, [Block::LOG => 0, Block::LOG2 => 1, Block::LEAVES => 0, Block::LEAVES2 => 1][$b->getId()], $b->getDamage());
    }
}