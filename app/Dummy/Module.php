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

use vManager, Nette;

/**
 * Test module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 11, 2011
 */
class Dummy extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule,
	vManager\Application\IAclEnabledModule {
		
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		$acl->addResource('Dummy');
		$acl->addResource('Dummy:Default', 'Dummy');
		$acl->allow('User', 'Dummy', Nette\Security\Permission::ALL);
	}
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		$menu[] = array(
			 'url' => Nette\Environment::getApplication()->getPresenter()->link(':Dummy:Default:default'),
			 'label' => __('Dummy test module'),
			 // 'icon' => self::getBasePath() . '/images/icons/small/grey/Home.png'
		);
		return $menu;
	}
	
}
