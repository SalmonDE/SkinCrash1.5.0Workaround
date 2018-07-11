<?php
declare(strict_types = 1);

namespace SalmonDE;

use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\UUID;

class SkinCrashWorkaround extends PluginBase implements Listener {

    public const REMOVE_DELAY = 70;

    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDataPacketSend(DataPacketSendEvent $event): void{
        if($event->getPacket() instanceof AddPlayerPacket){
            if($this->getServer()->getPlayerByUUID($event->getPacket()->uuid) === \null){
                $skin = new Skin('Standard_Custom', \str_repeat("\x80", 8192));
                $entry = PlayerListEntry::createAdditionEntry($event->getPacket()->uuid, 0, 'blame mojang - 1.5', '', 0, $skin);

                $pk = new PlayerListPacket();
                $pk->type = PlayerListPacket::TYPE_ADD;
                $pk->entries = [$entry];

                $event->getPlayer()->directDataPacket($pk);

                $this->getScheduler()->scheduleDelayedTask(new class($event->getPacket()->uuid, $event->getPlayer()) extends Task{
                    private $uuid;
                    private $player;

                    public function __construct(UUID $uuid, Player $player){
                        $this->uuid = $uuid;
                        $this->player = $player;
                    }

                    public function onRun(int $ct): void{
                        $entry = PlayerListEntry::createRemovalEntry($this->uuid);
                        $pk = new PlayerListPacket();
                        $pk->type = PlayerListPacket::TYPE_REMOVE;
                        $pk->entries = [$entry];

                        $this->player->directDataPacket($pk);
                    }
                }, self::REMOVE_DELAY);
            }
        }
    }
}
