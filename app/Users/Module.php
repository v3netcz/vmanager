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

use vManager, Nette,
	 vManager\Modules\System, vManager\Security\User, vBuilder\Orm\Behaviors\Secure;

/**
 * Ticketing system module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class Users extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule,
	vManager\Application\IAclEnabledModule {
	
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		$acl->addResource('Users');
		$acl->addResource('Users:Default', 'Users');
		$acl->addResource('Users:Edit', 'Users');
		$acl->addRole('User manager', 'User');		
		
		// Manazeri uzivatelu nesmeji editovat uzivatele s vyssimi opravnenimi nez maji samy
		$acl->allow('User manager', User::getParentResourceId(), Secure::ACL_PERMISSION_UPDATE, array('vManager\Security\User', 'permissionNoHigherUserAssert'));
		
		$acl->allow('User manager', 'Users', Nette\Security\Permission::ALL);
	}
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		
		if(Nette\Environment::getUser()->isAllowed('Users:Default', 'default')) {
			$menu[] = array(
				'label' => __('Users'),
				'icon' => System::getBasePath() . '/images/icons/small/grey/User%202.png',
				'children' => array(
					  array(
							'url' => Nette\Environment::getApplication()->getPresenter()->link(':Users:Default:default'),
							'label' => __('Show users')
						),
					  array(
							'url' => Nette\Environment::getApplication()->getPresenter()->link(':Users:Edit:addNewUser'),
							'label' => __('Add new user')
						)
				)
			);
		}
		
		return $menu;
	}

}
