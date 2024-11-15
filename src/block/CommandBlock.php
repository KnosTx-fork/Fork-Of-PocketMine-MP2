<?php

namespace pocketmine\block;

use pocketmine\block\tile\Tile;
use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class CommandBlock extends Block
{
    private string $command = '';
    /** @var array<string, self> */
    private array $waitingForCommandInput = [];

    public function __construct()
    {
        parent::__construct(
            new BlockIdentifier(137, 0, null), 
            "Command Block", 
            new BlockTypeInfo(BlockBreakInfo::indestructible())
        );
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function executeCommand(World $world, Player $player): void
    {
        if ($this->command !== '') {
            $world->getServer()->dispatchCommand($player, $this->command);
            $player->sendMessage(TextFormat::GREEN . 'Command Block executed: ' . $this->command);
        } else {
            $player->sendMessage(TextFormat::RED . 'No command set in this Command Block.');
        }
    }

    public function onInteract(Item $item, Vector3 $face, Vector3 $clickVector, Player $player, &$returnedItems): bool
    {
        if (!$player->hasPermission("pocketmine.commandblock.use") || !$player->isCreative()) {
            $player->sendMessage(TextFormat::RED . 'You don\'t have permission to use the Command Block.');
            return false;
        }

        $player->sendMessage(TextFormat::YELLOW . 'Type the command in chat prefixed with "/", or type "cancel" to exit.');
        $player->sendMessage(TextFormat::YELLOW . 'Example: /say Hello, world!');

        $this->waitingForCommandInput[$player->getName()] = $this;

        return true;
    }

    public function onPlayerChat(Player $player, string $message): void
    {
        if (isset($this->waitingForCommandInput[$player->getName()])) {
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
