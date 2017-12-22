<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockSniper\listeners;

use BlockHorizons\BlockSniper\Loader;
use BlockHorizons\BlockSniper\sessions\SessionManager;
use BlockHorizons\BlockSniper\ui\WindowHandler;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BrushListener implements Listener {

	/** @var Loader */
	private $loader = null;

	public function __construct(Loader $loader) {
		$this->loader = $loader;
	}

	/**
	 * @param PlayerInteractEvent $event
	 *
	 * @return bool
	 */
	public function brush(PlayerInteractEvent $event): bool {
		$player = $event->getPlayer();
		if($player->getInventory()->getItemInHand()->getId() === $this->getLoader()->getSettings()->getBrushItem()) {
			if($player->hasPermission("blocksniper.command.brush")) {
				$brush = ($session = SessionManager::getPlayerSession($player))->getBrush();
				$brush->execute($session, $this->getPlotPoints($player));
				$event->setCancelled();
				return true;
			}
		}

		if($player->getInventory()->getItemInHand()->getId() === $this->getLoader()->getSettings()->getSelectionItem()) {
			if($event->getAction() !== $event::RIGHT_CLICK_BLOCK) {
				return false;
			}
			if($player->hasPermission("blocksniper.command.brush")) {
				$session = SessionManager::getPlayerSession($player);
				$b = $event->getBlock();
				$session->setFirstSelectionPoint($b);

				$player->sendMessage(TextFormat::YELLOW . "1: " . TextFormat::BOLD . "(" . TextFormat::AQUA . $b->x . ", " . $b->y . ", " . $b->z . TextFormat::YELLOW . ")");
				return true;
			}
		}

		return false;
	}

	/**
	 * @param BlockBreakEvent $event
	 *
	 * @return bool
	 */
	public function selectPoint(BlockBreakEvent $event): bool {
		$player = $event->getPlayer();
		if($player->getInventory()->getItemInHand()->getId() === $this->getLoader()->getSettings()->getSelectionItem()) {
			if($player->hasPermission("blocksniper.command.brush")) {
				$session = SessionManager::getPlayerSession($player);
				$b = $event->getBlock();
				$session->setSecondSelectionPoint($b);

				$player->sendMessage(TextFormat::YELLOW . "2: " . TextFormat::BOLD . "(" . TextFormat::AQUA . $b->x . ", " . $b->y . ", " . $b->z . TextFormat::YELLOW . ")");
				return true;
			}
		}
		return false;
	}

	/**
	 * @return Loader
	 */
	public function getLoader(): Loader {
		return $this->loader;
	}

	/**
	 * @param Player $player
	 *
	 * @return Vector2[][]
	 */
	public function getPlotPoints(Player $player): array {
		if($player->hasPermission("blocksniper-myplot-bypass") || !$this->getLoader()->isMyPlotAvailable()) {
			return [];
		}
		$plotPoints = [];
		$settings = $this->getLoader()->getMyPlot()->getLevelSettings($player->getLevel()->getName());
		if($settings === null) {
			return [[new Vector2(), new Vector2()]];
		}
		$plotSize = $settings->plotSize;
		foreach($this->getLoader()->getMyPlot()->getPlotsOfPlayer($player->getName(), $player->getLevel()->getName()) as $plot) {
			$minVec = new Vector2($plot->X, $plot->Z);
			$maxVec = new Vector2($plot->X + $plotSize, $plot->Z + $plotSize);
			$plotPoints[] = [$minVec, $maxVec];
		}
		if(empty($plotPoints)) {
			return [[new Vector2(), new Vector2()]];
		}
		return $plotPoints;
	}

	/**
	 * @param PlayerItemHeldEvent $event
	 *
	 * @return bool
	 */
	public function onItemHeld(PlayerItemHeldEvent $event): bool {
		$player = $event->getPlayer();
		if($event->getItem()->getId() === $this->getLoader()->getSettings()->getBrushItem()) {
			if($player->hasPermission("blocksniper.command.brush")) {
				$windowHandler = new WindowHandler();
				$packet = new ModalFormRequestPacket();
				$packet->formId = $windowHandler->getWindowIdFor(WindowHandler::WINDOW_BRUSH_MENU);
				$packet->formData = $windowHandler->getWindowJson(WindowHandler::WINDOW_BRUSH_MENU, $this->getLoader(), $player);
				$player->dataPacket($packet);
				return true;
			}
		}
		return false;
	}
}
