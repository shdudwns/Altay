<?php

/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneWire extends Transparent{
	protected $id = self::REDSTONE_WIRE;

	/** @var bool */
	private $canProvidePower = true;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getBlastResistance() : float{
		return 0;
	}

	public function getName() : string{
		return "Redstone Wire";
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($face != Vector3::SIDE_UP || !$this->canBePlacedOn($blockClicked)){
			return false;
		}

		$this->level->setBlock($blockReplace, $this, true, false);
		$this->updateSurroundingRedstone(true);

		foreach($this->getVerticalSides() as $side => $vertical){
			$this->level->updateAroundRedstone($vertical, Vector3::getOppositeSide($side));
			$this->updateAround($vertical, Vector3::getOppositeSide($side));
		}

		foreach($this->getHorizontalSides() as $side => $horizontal){
			if($horizontal->isNormalBlock()){
				$this->updateAround($horizontal->getSide(Vector3::SIDE_UP), Vector3::SIDE_DOWN);
			}else{
				$this->updateAround($horizontal->getSide(Vector3::SIDE_DOWN), Vector3::SIDE_UP);
			}
		}

		return true;
	}

	private function updateAround(Block $block, int $face) : void{
		if($block->getId() == Block::REDSTONE_WIRE){
			$this->level->updateAroundRedstone($block, $face);

			foreach($this->getAllSides() as $side => $block){
				$this->level->updateAroundRedstone($block, Vector3::getOppositeSide($side));
			}
		}
	}

	private function updateSurroundingRedstone(bool $force) : void{
		$this->calculateCurrentChanges($force);
	}

	private function calculateCurrentChanges(bool $force) : void{
		$pos = clone $this;

		$meta = $this->getDamage();
		$maxStrength = $meta;
		$this->canProvidePower = false;
		$power = $this->getIndirectPower();

		$this->canProvidePower = true;

		if($power > 0 && $power > ($maxStrength - 1)){
			$maxStrength = $power;
		}

		$strength = 0;

		foreach($this->getHorizontalSides() as $side => $horizontalBlock){
			$flag = $horizontalBlock->getX() != $this->getX() || $horizontalBlock->getZ() != $this->getZ();

			if($flag){
				$strength = $this->getMaxCurrentStrength($horizontalBlock, $strength);
			}

			if($horizontalBlock->isNormalBlock() && !$this->getSide(Vector3::SIDE_UP)->isNormalBlock()){
				if($flag){
					$strength = $this->getMaxCurrentStrength($horizontalBlock->getSide(Vector3::SIDE_UP), $strength);
				}
			}elseif($flag && !$horizontalBlock->isNormalBlock()){
				$strength = $this->getMaxCurrentStrength($horizontalBlock->getSide(Vector3::SIDE_DOWN), $strength);
			}
		}

		if($strength > $maxStrength){
			$maxStrength = $strength - 1;
		}elseif($maxStrength > 0){
			--$maxStrength;
		}else{
			$maxStrength = 0;
		}

		if($power > $maxStrength - 1){
			$maxStrength = $power;
		}

		if($meta != $maxStrength){
			//$this->level->getServer().getPluginManager().callEvent(new BlockRedstoneEvent(this, meta, maxStrength));

			$this->setDamage($maxStrength);
			$this->level->setBlock($this, $this, false, false);

			$this->level->updateAroundRedstone($this, null);
			foreach($pos->getAllSides() as $side => $block){
				$this->level->updateAroundRedstone($block, Vector3::getOppositeSide($side));
			}
		}elseif($force){
			foreach($pos->getAllSides() as $side => $block){
				$this->level->updateAroundRedstone($block, Vector3::getOppositeSide($side));
			}
		}
	}

	private function getMaxCurrentStrength(Vector3 $pos, int $maxStrength) : int{
		if($this->level->getBlockIdAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()) != $this->getId()){
			return $maxStrength;
		}else{
			$strength = $this->level->getBlockDataAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
			return max($strength, $maxStrength);
		}
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		$this->level->setBlock($this, BlockFactory::get(Block::AIR), true, true);

		$this->updateSurroundingRedstone(false);

		foreach ($this->getAllSides() as $block) {
			$this->level->updateAroundRedstone($block, null);
		}

		foreach ($this->getHorizontalSides() as $block) {
			if ($block->isNormalBlock()) {
				$this->updateAround($block->getSide(Vector3::SIDE_UP), Vector3::SIDE_DOWN);
			} else {
				$this->updateAround($block->getSide(Vector3::SIDE_DOWN), Vector3::SIDE_UP);
			}
		}

		return true;
	}

	public function onNearbyBlockChange() : void{
		if(!$this->canBePlacedOn($this->getSide(Vector3::SIDE_DOWN))){
			$this->level->useBreakOn($this);
			return;
		}

		$this->updateSurroundingRedstone(false);
	}

	public function onRedstoneUpdate() : void{
		$this->updateSurroundingRedstone(false);
	}

	public function canBePlacedOn(Vector3 $v) : bool{
		$b = $this->level->getBlock($v);

		return $b->isSolid() && !$b->isTransparent() && $b->getId() != Block::GLOWSTONE;
	}

	public function getStrongPower(int $side) : int{
		return !$this->canProvidePower ? 0 : $this->getWeakPower($side);
	}

	public function getWeakPower(int $side) : int{
		if(!$this->canProvidePower){
			return 0;
		}else{
			$power = $this->getDamage();

			if($power == 0){
				return 0;
			}elseif($side == Vector3::SIDE_UP){
				return $power;
			}else{
				$array = [];
				$horizontalSides = $this->getHorizontalSides();

				foreach($horizontalSides as $face => $block){
					if ($this->isPowerSourceAt($block, $face)) {
						$array[] = $face;
					}
				}

				if(isset($horizontalSides[$side]) && empty($array)){
					return $power;
				}elseif(in_array($side, $array) && !in_array(Vector3::rotateYCCW($side), $array) && !in_array(Vector3::rotateY($side), $array)){
					return $power;
				}else{
					return 0;
				}
			}
		}
	}

	private function isPowerSourceAt(Block $block, int $side) : bool{
		$flag = $block->isNormalBlock();
		$up = $block->getSide(Vector3::SIDE_UP);
		$flag1 = $up->isNormalBlock();
		return !$flag1 && $flag && $this->canConnectUpwardsTo($up) || ($this->canConnectTo($block, $side) || !$flag && $this->canConnectUpwardsTo($block->getSide(Vector3::SIDE_UP)));
	}

	protected static function canConnectUpwardsTo(Block $block) : bool{
		return self::canConnectTo($block, null);
	}

	protected static function canConnectTo(Block $block, ?int $side) : bool{
		if($block->getId() == Block::REDSTONE_WIRE){
			return true;
		}elseif(BlockRedstoneDiode::isDiode($block)){
			/** @var BlockRedstoneDiode $block */
			$face = $block->getFacing();
			return $face == $side || Vector3::getOppositeSide($face) == $side;
		}else{
			return $block->isPowerSource() && $side != null;
		}
	}

	public function isPowerSource() : bool{
		return $this->canProvidePower;
	}

	private function getIndirectPower() : int{
		$power = 0;

		foreach($this->getAllSides() as $side => $block){
			$blockPower = $this->getIndirectPowerBy($block, $side);

			if($blockPower >= 15){
				return 15;
			}

			if($blockPower > $power){
				$power = $blockPower;
			}
		}

		return $power;
	}

	private function getIndirectPowerBy(Block $block, int $side) : int{
		if($block->getId() == Block::REDSTONE_WIRE){
			return 0;
		}

		return $block->isNormalBlock() ? $this->getStrongPowerBy($block->getSide($side), $side) : $block->getWeakPower($side);
	}

	private function getStrongPowerBy(Block $block, int $direction) : int{
		if($block->getId() == Block::REDSTONE_WIRE){
			return 0;
		}

		return $block->getStrongPower($direction);
	}

}