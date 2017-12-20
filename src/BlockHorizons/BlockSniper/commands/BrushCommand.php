<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockSniper\commands;

use BlockHorizons\BlockSniper\brush\BrushMode;
use BlockHorizons\BlockSniper\data\Translation;
use BlockHorizons\BlockSniper\Loader;
use BlockHorizons\BlockSniper\sessions\SessionManager;
use BlockHorizons\BlockSniper\ui\WindowHandler;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;

class BrushCommand extends BaseCommand {

	public function __construct(Loader $loader) {
		parent::__construct($loader, "brush", Translation::COMMANDS_BRUSH_DESCRIPTION, "/brush [mode] [selection/brush]", ["b"]);
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

		if(!isset($args[0]) or strtolower($args[0]) !== "mode") {
			$windowHandler = new WindowHandler();
			$packet = new ModalFormRequestPacket();
			$packet->formId = $windowHandler->getWindowIdFor(WindowHandler::WINDOW_MAIN_MENU);
			$packet->formData = $windowHandler->getWindowJson(WindowHandler::WINDOW_MAIN_MENU, $this->getLoader(), $sender);
			$sender->dataPacket($packet);
			return true;
		}

		$mode = BrushMode::MODE_BRUSH;
		switch(strtolower($args[1])) {
			case "selection":
			case "select":
			case "new":
			case "points":
			case "point":
				$mode = BrushMode::MODE_SELECTION;
		}

		SessionManager::getPlayerSession($sender)->getBrush()->setMode($mode);

		return true;
	}
}
