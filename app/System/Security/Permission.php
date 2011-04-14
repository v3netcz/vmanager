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

namespace vManager\Security;

use vManager, vBuilder, Nette;

/**
 * Implementation of ACL authorization handler
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 10, 2011
 */
class Permission extends Nette\Security\Permission {
	
	private $initialized = false;
	private $roles = array();
	
	/**
	 * Initializes roles, resources, etc.
	 */
	private function init() {				
		// Anonymni (neprihlaseny) navstevnik
		$this->addRole('guest');
		
		// Prihlaseny uzivatel
		$this->addRole('User', 'guest');
		
		// Inicializace opravneni modulu
		foreach(vManager\Application\ModuleManager::getModules() as $curr) {
			if($curr->isEnabled() && $curr instanceof vManager\Application\IAclEnabledModule) 
				$curr->initPermission($this);
		}
		
		// Administrator
		$this->addRole('Administrator', count($this->roles) > 2 ? $this->getAllRegistredRoles() : 'User');
		$this->allow('Administrator', self::ALL, self::ALL);
		
		$this->initialized = true;
	}
	
	/**
	 * Checks if role is allowed to perform action and takes of calling
	 * initialization methods.
	 */
	public function isAllowed($role, $resource, $privilege) {
		if(!$this->initialized) $this->init();
		
		return parent::isAllowed($role, $resource, $privilege);
	}
	
	/**
	 * Adds role (don't have access to parent's roles)
	 */
	public function addRole($role, $parents = NULL) {
		if(!in_array($role, $this->roles)) $this->roles[] = $role;
		parent::addRole($role, $parents);
	}
	
	/**
	 * Returns all registred roles to this class (without User and guest)
	 */
	public function getAllRegistredRoles() {
		return array_diff($this->roles, array('guest', 'User') );
	}
	
}
