<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockSniper\commands\cloning;

use BlockHorizons\BlockSniper\cloning\types\CopyType;
use BlockHorizons\BlockSniper\cloning\types\TemplateType;
use BlockHorizons\BlockSniper\commands\BaseCommand;
use BlockHorizons\BlockSniper\data\Translation;
use BlockHorizons\BlockSniper\Loader;
use BlockHorizons\BlockSniper\sessions\SessionManager;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use Schematic\Schematic;

class CloneCommand extends BaseCommand {

	public function __construct(Loader $loader) {
		parent::__construct($loader, "clone", Translation::get(Translation::COMMANDS_CLONE_DESCRIPTION), "/clone <type> [name]");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		if(!$this->testPermission($sender)) {
			$this->sendNoPermission($sender);
			return false;
		}

		if(!$sender instanceof Player) {
			$this->sendConsoleError($sender);
			return false;
		}

		$session = SessionManager::getPlayerSession($sender);
		if($session === null) {
			return false;
		}

		if(!$session->hasPointsSelected()) {
			$sender->sendMessage($this->getWarning() . Translation::get(Translation::COMMANDS_CLONE_NO_POINTS));
			return false;
		}

		switch(strtolower($args[0])) {
			default:
			case "copy":
				$shape = $session->getBrush()->getShape(true);
				$cloneType = new CopyType($sender, false, $shape->getCenter(), $shape->getBlocksInside());

				$cloneType->saveClone();
				$sender->sendMessage(TF::GREEN . Translation::get(Translation::COMMANDS_CLONE_COPY_SUCCESS));
				return true;

			case "template":
				if(!isset($args[1])) {
					$sender->sendMessage($this->getWarning() . Translation::get(Translation::COMMANDS_CLONE_TEMPLATE_MISSING_NAME));
					return false;
				}
				$shape = $session->getBrush()->getShape(true);
				$cloneType = new TemplateType($sender, false, $shape->getCenter(), $shape->getBlocksInside(), $args[1]);
				$cloneType->saveClone();
				$sender->sendMessage(TF::GREEN . Translation::get(Translation::COMMANDS_CLONE_TEMPLATE_SUCCESS, [$this->getLoader()->getDataFolder() . "templates/" . $args[1]]));
				return true;

			case "scheme":
			case "schem":
			case "schematic":
				if(!isset($args[1])) {
					$sender->sendMessage($this->getWarning() . Translation::get(Translation::COMMANDS_CLONE_SCHEMATIC_MISSING_NAME));
					return false;
				}
				$shape = $session->getBrush()->getShape(true);
				$schematic = new Schematic();
				$schematic
					->setBlocks($shape->getBlocksInside())
					->setMaterials(Schematic::MATERIALS_POCKET)
					->encode()
					->setLength($shape->sizes["l"])
					->setHeight($shape->sizes["h"])
					->setWidth($shape->sizes["w"])
					->save($this->getLoader()->getDataFolder() . "schematics/" . $args[1] . ".schematic");
				$sender->sendMessage(TF::GREEN . Translation::get(Translation::COMMANDS_CLONE_SCHEMATIC_SUCCESS, [$this->getLoader()->getDataFolder() . "templates/" . $args[1]]));
				return true;
		}
	}
}
