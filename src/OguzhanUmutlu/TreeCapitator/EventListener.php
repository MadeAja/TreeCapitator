<?php

namespace OguzhanUmutlu\TreeCapitator;

use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\ClosureTask;

class EventListener implements Listener {
    public function onBreak(BlockBreakEvent $event) {
        $lvl = $event->getBlock()->level->getFolderName();
        $itemId = $event->getItem()->getId();
        $enabledWorlds = TreeCapitator::getInstance()->getConfig()->getNested("enabled-worlds");
        $disabledWorlds = TreeCapitator::getInstance()->getConfig()->getNested("disabled-worlds");
        $enabledItems = TreeCapitator::getInstance()->getConfig()->getNested("enabled-items");
        $disabledItems = TreeCapitator::getInstance()->getConfig()->getNested("disabled-items");
        if(!$event->getPlayer()->hasPermission("tree"."capitator.use"))
            return;
        if(is_array($enabledWorlds) && !empty($enabledWorlds) && !in_array($lvl, $enabledWorlds))
            return;
        if(is_array($disabledWorlds) && in_array($lvl, $disabledWorlds))
            return;
        if(is_array($enabledItems) && !empty($enabledItems) && !in_array($itemId, $enabledItems))
            return;
        if(is_array($disabledItems) && in_array($itemId, $disabledItems))
            return;
        TreeCapitator::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick)use($event):void{
            if($event->isCancelled()) return;
            $b = $event->getBlock();
            if(!in_array($b->getId(), [Block::LOG, Block::LOG2, Block::LEAVES, Block::LEAVES2])) return;
            TreeCapitator::startBreakTree($event->getPlayer(), $b, [Block::LOG => 0, Block::LOG2 => 1, Block::LEAVES => 0, Block::LEAVES2 => 1][$b->getId()], $b->getDamage(), 0, $b->asVector3());
        }), 1);
    }
}