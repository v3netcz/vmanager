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

use vBuilder,
		vManager,
		Gridito,
		Nette;

/**
 * Model for ORM queries
 *
 * @author Adam Staněk (V3lbloud)
 * @since Oct 26, 2011
 */
class OrmModel implements Gridito\IModel {
	
	protected $_fluent;
	
	protected $_limit;
	protected $_offset;
	protected $_sortColumn;
	protected $_sortMethod;
	protected $_items;
	
	public function __construct(vBuilder\Orm\Fluent $fluent) {
		$this->_fluent = $fluent;
	}
	
	public function getUniqueId($item) {
		$idFields = array_flip($this->getEntityMetadata()->getIdFields());
		foreach($idFields as $key=>$value) $idFields[$key] = $item->$key;
		
		return md5(implode($idFields));
	}
	
	public function getItemByUniqueId($uniqueId) {
		$fluent = clone $this->_fluent;
		
		// TODO: Prepsat pekneji
		foreach($fluent as $curr) {
			if($this->getUniqueId($curr) == $uniqueId) return $curr;
		}
		
		return null;
	}

	public function getItemsByUniqueIds(array $uniqueIds) {
		throw new Nette\NotImplementedException('Not implemented yet');
	}
	
	public function getItems() {
		if($this->_items === null) {
			$fluent = clone $this->_fluent;

			if(isset($this->_limit)) $fluent->limit($this->_limit);
			if(isset($this->_offset)) $fluent->offset($this->_offset);

			if(isset($this->_sortColumn)) {
				$fluent->orderBy("[$this->_sortColumn] $this->_sortMethod");
			}

			$this->_items = $fluent->fetchAll();
		}
		
		return $this->_items;
	}

	public function setSorting($column, $type = self::ASC) {
		//if($this->_items !== null) throw new Nette\InvalidStateException('Data already loaded');
		
		$this->_sortColumn = $column;
		$this->_sortMethod = $type;
	}

	public function setLimit($limit) {
		//if($this->_items !== null) throw new Nette\InvalidStateException('Data already loaded');
		
		$this->_limit = $limit;
	}

	public function setOffset($offset) {
		//if($this->_items !== null) throw new Nette\InvalidStateException('Data already loaded');
		
		$this->_offset = $offset;
	}
	
	// ------------------
	
	/**
	 * Returns array iterator
	 * 
	 * @return \ArrayIterator 
	 */
	public function getIterator() {
		return new \ArrayIterator($this->getItems());
	}
	
	/**
	 * Returns number of records
	 * 
	 * @return int
	 */
	public function count() {
		return count($this->getItems());
	}
	
	// ------------------
	
	/**
	 * Returns class name of record entity
	 * 
	 * @return string class name
	 */
	protected function getEntityClass() {
		return $this->_fluent->getRowClass();
	}
	
	/**
	 * Returns metadata of record entity
	 * 
	 * @return vBuilder\Orm\IEntityMetadata 
	 */
	protected function getEntityMetadata() {
		$class = $this->getEntityClass();
		return $class::getMetadata();
	}
	
}

