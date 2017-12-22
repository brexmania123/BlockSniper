<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockSniper\brush\shapes;

use BlockHorizons\BlockSniper\brush\BaseShape;
use BlockHorizons\BlockSniper\sessions\SessionManager;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

class SphereShape extends BaseShape {

	const ID = self::SHAPE_SPHERE;

	/** @var bool */
	private $trueSphere = false;

	public function __construct(Player $player, Level $level, int $radius, Position $center, bool $hollow = false, bool $selected = false, bool $cloneShape = false) {
		parent::__construct($player, $level, $center, $hollow, $selected);
		$this->radius = $radius;
		if($cloneShape) {
			$this->center[1] += $this->radius;
		}
		$this->trueSphere = SessionManager::getPlayerSession($player)->getBrush()->getPerfect();
	}

	/**
	 * @param bool $vectorOnly
	 *
	 * @return array
	 */
	public function getBlocksInside(bool $vectorOnly = false): array {
		[$targetX, $targetY, $targetZ] = $this->center;
		[$minX, $minY, $minZ, $maxX, $maxY, $maxZ] = $this->calculateBoundaryBlocks($targetX, $targetY, $targetZ, $this->radius, $this->radius);
		$radiusX = ($maxX - $minX) / 2;
		$radiusZ = ($maxZ - $minZ) / 2;
		$radiusY = ($maxY - $minY) / 2;
		$radius = ($radiusX + $radiusZ) / 2;

		$yFactor = ($radius / $radiusY) ** 2;
		$xFactor = ($radiusZ / $radiusX) ** 0.9;
		$zFactor = ($radiusX / $radiusZ) ** 0.9;

		$radiusSquared = ($radius - ($this->trueSphere ? 0 : 0.5)) ** 2 + ($this->trueSphere ? 0.5 : 0);

		$blocksInside = [];

		for($x = $maxX; $x >= $minX; $x--) {
			$xs = ($targetX - $x) * ($targetX - $x) * $xFactor;
			for($y = $maxY; $y >= $minY; $y--) {
				$ys = ($targetY - $y) * ($targetY - $y) * $yFactor;
				for($z = $maxZ; $z >= $minZ; $z--) {
					$zs = ($targetZ - $z) * ($targetZ - $z) * $zFactor;
					if($xs + $ys + $zs < $radiusSquared) {
						if($this->hollow === true) {
							if($y !== $maxY && $y !== $minY && ($xs + $ys + $zs) < $radiusSquared - 3 - $radius / 0.5) {
								continue;
							}
						}
						$blocksInside[] = $vectorOnly ? new Vector3($x, $y, $z) : $this->getLevel()->getBlock(new Vector3($x, $y, $z));
					}
				}
			}
		}
		return $blocksInside;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->hollow ? "Hollow Sphere" : "Sphere";
	}

	/**
	 * @return int
	 */
	public function getApproximateProcessedBlocks(): int {
		if($this->hollow) {
			$blockCount = 4 * M_PI * $this->radius;
		} else {
			$blockCount = 4 / 3 * M_PI * ($this->radius ** 3);
		}

		return (int) ceil($blockCount);
	}

	/**
	 * Returns the radius of the sphere.
	 *
	 * @return int
	 */
	public function getRadius(): int {
		return $this->radius;
	}

	/**
	 * @return array
	 */
	public function getTouchedChunks(): array {
		$maxX = $this->center[0] + $this->radius;
		$minX = $this->center[0] - $this->radius;
		$maxZ = $this->center[2] + $this->radius;
		$minZ = $this->center[2] - $this->radius;

		$touchedChunks = [];
		for($x = $minX; $x <= $maxX + 16; $x += 16) {
			for($z = $minZ; $z <= $maxZ + 16; $z += 16) {
				$chunk = $this->getLevel()->getChunk($x >> 4, $z >> 4, true);
				if($chunk === null) {
					continue;
				}
				$touchedChunks[Level::chunkHash($x >> 4, $z >> 4)] = $chunk->fastSerialize();
			}
		}
		return $touchedChunks;
	}
}
