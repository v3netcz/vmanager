<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vManager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vManager. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vManager\Modules\Tickets;

use vManager,
	 vBuilder,
	 Nette,
	 dibi;

/**
 * Project entity data class
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jun 14, 2011
 * 
 * @Table(name="pm_priorities")
 * 
 * @Column(id, pk, type="integer")
 * @Column(name, realName="priorityName", type="string")
 * @Column(weight, type="integer")
 * 
 */
class Priority extends vBuilder\Orm\ActiveEntity {

	static private $maxPriorityHeight;
	
	/**
	 * Returns translated name for display purposes
	 * 
	 * @return string
	 */
	function getLabel() {
		return __($this->getName());
	}
	
	/**
	 * Returns miximum weight of all priorities
	 * @return int 
	 */
	static function getMaxPriorityWeight() {
		if(self::$maxPriorityHeight !== null) return self::$maxPriorityHeight;
		
		self::$maxPriorityHeight = (int) dibi::query('SELECT MAX([weight]) FROM ['.self::getMetadata()->getTableName().']')->fetchSingle();
		return self::$maxPriorityHeight;
	}
	
}