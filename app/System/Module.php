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

use vManager, vBuilder, Nette, vManager\Security\User, vBuilder\Orm\Behaviors\Secure;

/**
 * Base vManager system module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class System extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule,
	vManager\Application\IAclEnabledModule {
	
	private $_ownerInfo;
	
	public function __construct() {
		global $context;
	
		// Avatars
		vManager\Security\User::registerAvatarFileHandler();
		
		// Company logo
		vManager\Modules\System\OwnerInfo::registerLogoFileHandler();
	}

	/**
	 * Returns owner info
	 *
	 * @return System\OwnerInfo
	 */
	public function getOwnerInfo() {
		if(!isset($this->_ownerInfo)) {
			$this->_ownerInfo = new System\OwnerInfo;
		}
		
		return $this->_ownerInfo;
	}
	
	/**
	 * Returns true if this module is enabled in current configuration
	 * 
	 * @return bool
	 */
	public function isEnabled() {
		return true; // System module has to be always enabled
	}
	
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		$acl->addResource('System');
		$acl->addResource('System:Homepage', 'System');
		$acl->addResource('System:Search', 'System');
		$acl->addResource('System:UserSettings', 'System');
		$acl->addResource('System:Settings', 'System');
		$acl->addResource('System:Timeline', 'System');
		$acl->addResource('System:Texy', 'System');
		// $acl->addResource('System:JsonConnector', 'System');
		
		// Vsichni musi mit pristup k tomu cist uzivatele (login)
		// Zaroven uzivatel musi mit opravneni k tomu, editovat vlastni profil
		$acl->allow('guest', User::getParentResourceId(), Secure::ACL_PERMISSION_READ);
		$acl->allow('guest', User::getParentResourceId(), Secure::ACL_PERMISSION_UPDATE); // Password reset form, TODO: osetrit to
		//$acl->allow('User', User::getParentResourceId(), Secure::ACL_PERMISSION_UPDATE, array('vManager\Security\User', 'permissionOwnProfileAssert'));
		
		$acl->allow('User', 'System:Homepage', Nette\Security\Permission::ALL);
		$acl->allow('User', 'System:Search', Nette\Security\Permission::ALL);
		$acl->allow('User', 'System:UserSettings', Nette\Security\Permission::ALL);
		$acl->allow('User', 'System:Timeline', Nette\Security\Permission::ALL);
		$acl->allow('User', 'System:Texy', Nette\Security\Permission::ALL);
	}
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		$menu[] = array(
			 'url' => Nette\Environment::getApplication()->getPresenter()->link(':System:Homepage:default'),
			 'label' => __('Homepage'),
			 'icon' => self::getBasePath() . '/images/icons/small/grey/Home.png'
		);
		
		$menu[] = array(
			 'url' => Nette\Environment::getApplication()->getPresenter()->link(':System:Timeline:default'),
			 'label' => __('Timeline'),
			 'icon' => self::getBasePath() . '/images/icons/small/grey/Books.png'
		);
		
		return $menu;
	}
	
	/**
	 * Returns base path to WWW directory
	 * 
	 * @return string
	 */
	public static function getBasePath($absolute = false) {
		$baseUri = rtrim(Nette\Environment::getContext()->httpRequest->getUrl()->getBaseUrl(), '/');		
		return $absolute ? $baseUri : preg_replace('#https?://[^/]+#A', '', $baseUri);
	}
	
}
