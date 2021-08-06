<?php

namespace OguzhanUmutlu\TreeCapitator;

use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\ClosureTask;

class EventListener implements Listener {
    public function onBreak(BlockBreakEvent $event) {
        $lvl = $event->getBlock()->level->getFolderName();
        $worlds = TreeCapitator::getInstance()->getConfig()->getNested("enabled-worlds");
        if(!$event->getPlayer()->hasPermission("tree"."capitator.use"))
        if(is_array($worlds) && !empty($worlds) && !in_array($lvl, $worlds))
            return;
        TreeCapitator::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick)use($event):void{
            $b = $event->getBlock();
            if(!in_array($b->getId(), [Block::LOG, Block::LOG2, Block::LEAVES, Block::LEAVES2])) return;
            TreeCapitator::startBreakTree($event->getPlayer(), $b, [Block::LOG => 0, Block::LOG2 => 1, Block::LEAVES => 0, Block::LEAVES2 => 1][$b->getId()], $b->getDamage());
        }), 1);
    }
}