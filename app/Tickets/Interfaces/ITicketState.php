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

use vManager, Nette;

/**
 * Interface of ticket state data classes
 *
 * @author Adam Staněk (V3lbloud)
 * @since Nov 10, 2011
 */
interface ITicketState {
	
	/**
	 * Returns state's ID
	 * 
	 * @return string
	 */
	public function getId();
	
	/**
	 * Returns state's display name
	 * 
	 * @return string 
	 */
	public function getName();
	
	/**
	 * Return array of succeeding states
	 * 
	 * @return array of ITicketState
	 */
	public function getSuccessors();
	
	/**
	 * Returns true if this state is valid initial state of ticket
	 * 
	 * @return bool
	 */
	public function isInitial();
	
	/**
	 * Returns true if this state is considered as resolution (it's final)
	 * 
	 * @return bool
	 */
	public function isFinal();	
	
	/**
	 * Config factory method
	 * 
	 * @param string state id
	 * @param Nette\ArrayHash config array
	 * @param vManager\Modules\Tickets module reference
	 */
	public static function fromConfig($id, Nette\ArrayHash $config, vManager\Modules\Tickets $module);
	
}
