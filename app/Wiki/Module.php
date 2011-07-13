<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 V3Net.cz
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
use Nette\Environment;
use Nette\Application\Routers\Route;

/**
 * Wiki module
 *
 * @author Jirka Vebr
 */
class Wiki extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule,
	vManager\Application\IAclEnabledModule {
		
	public function __construct() {
		if(!$this->isEnabled()) return ;
		
		$application = \Nette\Environment::getApplication();
		$application->onStartup[] = function() use ($application) {
			$router = $application->getRouter();

			Route::addStyle('wikiId', null); // we don't want rawurlencode in order to preserve the "/" signs
			Route::setStyleProperty('wikiId', Route::PATTERN, '[a-z0-9/\-]+');

			$router[] = new Route('wiki/edit[<wikiId>]', 'Wiki:Default:editArticle');
			$router[] = new Route('wiki/create[<wikiId>]', 'Wiki:Default:createArticle');
			$router[] = new Route('wiki/tree[<wikiId>]', 'Wiki:Default:tree');
			$router[] = new Route('wiki[<wikiId>]', 'Wiki:Default:default');

		};
	}
	
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		// TODO: Predelat aby to bralo akce, az se to finalizuje
		
		$acl->addResource('Wiki');
		$acl->addResource('Wiki:Default', 'Wiki');
		$acl->addResource('Wiki:EditArticle', 'Wiki');
		$acl->addResource('Wiki:CreateArticle', 'Wiki');
		$acl->addResource('Wiki:Tree', 'Wiki');
		
		$acl->addRole('Wiki admin', 'User');
		
		$acl->allow('User', 'Wiki:Default', Nette\Security\Permission::ALL);
		$acl->allow('Wiki admin', 'Wiki:EditArticle', Nette\Security\Permission::ALL);
		$acl->allow('Wiki admin', 'Wiki:CreateArticle', Nette\Security\Permission::ALL);
	}
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		$menu[] = array(
			 'url' => Nette\Environment::getApplication()->getPresenter()->link(':Wiki:Default:default'),
			 'label' => __('Wiki'),
			 'icon' => System::getBasePath() . '/images/icons/small/grey/Documents.png'
		);
		return $menu;
	}
	
}
