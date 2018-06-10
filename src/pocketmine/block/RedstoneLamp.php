<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneLamp extends Solid{

	protected $id = self::REDSTONE_LAMP;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Redstone Lamp";
	}

	public function getHardness() : float{
		return 0.3;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($this->level->isBlockPowered($this)){
			$this->level->setBlock($this, BlockFactory::get(Block::LIT_REDSTONE_LAMP), false, true);
		}else{
			$this->level->setBlock($this, $this, false, true);
		}

		return true;
	}

	public function onNearbyBlockChange() : void{
		if($this->level->isBlockPowered($this)){
			$this->level->setBlock($this, BlockFactory::get(Block::LIT_REDSTONE_LAMP), false, false);
		}
	}

	public function onRedstoneUpdate() : void{
		if($this->level->isBlockPowered($this)){
			$this->level->setBlock($this, BlockFactory::get(Block::LIT_REDSTONE_LAMP), false, false);
		}
	}
}
