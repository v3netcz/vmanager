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

use vManager, vBuilder, Nette;

/**
 * Data class for ticket state representation
 *
 * @author Adam Staněk (V3lbloud)
 * @since Nov 10, 2011
 */
class TicketState extends vBuilder\Object implements ITicketState {
	
	/** @var string ID of state */
	protected $_id;
	
	/** @var string display name of state */
	protected $_name;
	
	/** @var array of successor ids (string) */
	protected $_successorIds = array();
	
	/** @var array of ITicketState */
	protected $_successors;
	
	/** @var bool if state is initial */
	protected $_isInitial;
	
	/**
	 * Config factory method
	 * 
	 * @param string state id
	 * @param Nette\ArrayHash config array
	 * @param vManager\Modules\Tickets module reference
	 */
	public static function fromConfig($id, Nette\ArrayHash $config, vManager\Modules\Tickets $module) {
		$s = new static;
		$s->_id = $id;
		
		if(isset($config['name'])) $s->_name = trim($config['name']);
		if(isset($config['succ'])) $s->_successorIds = (array) $config['succ'];
		
		return $s;
	}
	
	/**
	 * Returns state's ID
	 * 
	 * @return string
	 */
	public function getId() {
		return $this->_id;
	}
	
	/**
	 * Returns state's display name
	 * 
	 * @return string 
	 */
	public function getName() {
		return $this->_name != '' ? __($this->_name) : $this->_id;
	}
	
	/**
	 * Returns true if this state is valid initial state of ticket
	 * 
	 * @return bool
	 */
	public function isInitial() {
		if(!isset($this->_isInitial)) {
			$this->_isInitial = true;
			$states = vManager\Modules\Tickets::getInstance()->getAvailableTicketStates();
			
			foreach($states as $state) {
				if($state === $this) continue;
				
				foreach($state->successors as $succ) {
					if($succ === $this) {
						$this->_isInitial = false;
						return false;
					}
				}
			}
		}
		
		return $this->_isInitial;
	}
	
	/**
	 * Returns true if this state is considered as resolution (it's final)
	 * 
	 * @return bool
	 */
	public function isFinal() {
		return count($this->successorIds) == 0;
	}
	
	/**
	 * Return array of succeeding states
	 * 
	 * @return array of ITicketState
	 */
	public function getSuccessors() {
		if(!isset($this->_successors)) {
			$this->_successors = array();
			
			foreach($this->_successorIds as $succId)
				$this->_successors[] = vManager\Modules\Tickets::getInstance()->getTicketState($succId);
		}
		
		return $this->_successors;
	}
	
	/**
	 * Return array of succeeding state IDs
	 * 
	 * @return array of string
	 */
	public function getSuccessorIds() {
		return $this->_successorIds;
	}
	
}
