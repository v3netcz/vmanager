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

namespace vManager\Modules;

use vBuilder, vManager, Nette, Nette\Utils\Strings;

/**
 * Module for managing webhosting services
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jul 10, 2012
 */
class Hosting extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule,
	vManager\Application\IAclEnabledModule {
		
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		$acl->addResource('Hosting');
		$acl->addResource('Hosting:Host', 'Hosting');
		
		$acl->addRole('Hosting Manager', 'User');
		$acl->allow('Hosting Manager', 'Hosting', Nette\Security\Permission::ALL);
	}
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		
		$user = Nette\Environment::getUser();
				
		if($user->isAllowed('Hosting:Host', 'default')) {
			$menu[] = array(
				'url' => Nette\Environment::getApplication()->getPresenter()->link(':Hosting:Host:default'),
				'label' => __('Hosting'),
				'icon' => System::getBasePath() . '/images/icons/small/grey/Linux.png',
			);
		}	
		
		return $menu;
	}
	
}
