<?php

namespace pocketmine\block;

use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockBreakInfo;
use pocketmine\player\Player;
use pocketmine\world\World;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

class CommandBlock extends Block implements Listener{
  private string $command = '';
  private bool $isRepeat = false;
  private bool $isConditional = false;
  private array $waitingForCommandInput = [];

  public function __construct(){
    parent::__construct(new BlockIdentifier(137, 0), 'Command Block', BlockBreakInfo::indestructible());
  }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function getCommand() : string{
        return $this->command;
    }

    public function setRepeat(bool $repeat) : void{
        $this->isRepeat = $repeat;
    }

    public function isRepeat() : bool{
        return $this->isRepeat;
    }

    public function setConditional(bool $conditional): void{
        $this->isConditional = $conditional;
    }

    public function isConditional() : bool{
        return $this->isConditional;
    }
  
  public function executeCommand(World $world, Player $player) : void
    {
        if ($this->command !== '') {
            $world->getServer()->dispatchCommand($player, $this->command);
            $player->sendMessage(TextFormat::GREEN . 'Command Block executed: ' . $this->command);
        } else {
            $player->sendMessage(TextFormat::RED . 'No command set in this Command Block.');
        }
    }

    public function onInteract(Player $player) : bool
    {
        if (!$player->isOp() || !$player->isCreative()) {
            $player->sendMessage(TextFormat::RED . 'You don\'t have permission to use the Command Block.');
            return false;
        }

        $player->sendMessage(TextFormat::YELLOW . 'Type the command in chat prefixed with "/", or type "cancel" to exit.');
        $player->sendMessage(TextFormat::YELLOW . 'Example: /say Hello, world!');

        $this->waitingForCommandInput[$player->getName()] = $this;

        return true;
    }

    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();

        if (isset($this->waitingForCommandInput[$player->getName()])) {
            $event->cancel();

            if (strtolower($message) === 'cancel') {
                unset($this->waitingForCommandInput[$player->getName()]);
                $player->sendMessage(TextFormat::RED . 'Command Block setup canceled.');
                return;
            }

            $commandBlock = $this->waitingForCommandInput[$player->getName()];
            $commandBlock->setCommand(ltrim($message, '/'));
            $player->sendMessage(TextFormat::GREEN . 'Command set to: ' . $message);

            unset($this->waitingForCommandInput[$player->getName()]);
        }
    }
}
