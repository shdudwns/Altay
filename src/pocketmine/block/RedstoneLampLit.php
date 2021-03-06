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

class RedstoneLampLit extends RedstoneLamp{

	protected $id = self::LIT_REDSTONE_LAMP;

	public function getName() : string{
		return "Lit Redstone Lamp";
	}

	public function getLightLevel() : int{
		return 15;
	}

	public function onNearbyBlockChange() : void{
		if(!$this->level->isBlockPowered($this)){
			$this->level->scheduleDelayedBlockUpdate($this, 4);
		}
	}

	public function onRedstoneUpdate() : void{
		if(!$this->level->isBlockPowered($this)){
			$this->level->scheduleDelayedBlockUpdate($this, 4);
		}
	}

	public function onScheduledUpdate() : void{
		if(!$this->level->isBlockPowered($this)){
			$this->level->setBlock($this, BlockFactory::get(Block::REDSTONE_LAMP), false, false);
		}
	}
}
