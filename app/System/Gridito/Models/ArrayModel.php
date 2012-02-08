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

namespace vManager\Grid;

/**
 * Simple array model
 *
 * @author Adam Staněk (V3lbloud)
 * @since May 5, 2011
 */
class ArrayModel extends \Gridito\AbstractModel {

	/** @var array data */
	protected $data = array();
	
	/**
	 * Constructor
	 * 
	 * @param array data
	 */
	public function __construct(array $data = array()) {
		foreach($data as $key=>$curr) {
			$this->data[$key] = new \DibiRow($curr);
		}
	}

	public function getItemByUniqueId($uniqueId) {
		foreach($this->data as $curr) {
			if(isset($curr[$this->getPrimaryKey()]) && $curr[$this->getPrimaryKey()] = $uniqueId)
				return $curr;
		}
		
		return false;
	}

	public function getItems() {	
		$items = array();
		$sortIndex = array();
		reset($this->data);
		list($sortColumn, $sortType) = $this->getSorting();
		
		for($i = 0; $i < $this->getOffset(); $i++) next($this->data);
		for($i = 0; $i < $this->getLimit(); $i++) {		
			$items[key($this->data)] = current($this->data);
			
			if($sortColumn != null && isset($items[key($this->data)]->$sortColumn))
				$sortIndex[key($this->data)] = $items[key($this->data)]->$sortColumn;
			
			next($this->data);
		}
		
		if($sortColumn != null && count($sortIndex) > 0) {
			if($sortType == self::DESC) arsort($sortIndex);
			else asort($sortIndex);
			
			$items2 = array();
			foreach($sortIndex as $k => $v) $items2[$k] = $items[$k];
			$items = $items2;
		}
		
		
		return $items;
	}

	/**
	 * Item count
	 * @return int
	 */
	protected function _count() {
		return count($this->data);
	}

}