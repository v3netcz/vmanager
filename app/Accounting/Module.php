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
 * Module for listing and creating invoices
 *
 * @author Adam Staněk (V3lbloud)
 * @since May 5, 2011
 */
class Accounting extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule,
	vManager\Application\IAclEnabledModule {
	
	public function __construct() {
		$config = $this->getConfig();
		if(isset($config['invoiceDir'])) {
	
		  vManager\Modules\System\FilesPresenter::$handers[] = function ($filename) use($config) {
						  
			  // Filtering exact match, for security reasons
			  if(($matches = Nette\Utils\Strings::match($filename, '/^\/invoices\/([A-Za-z0-9\\.\\-_]+\.(pdf|xml))$/')) != null) {
				  if(!Nette\Environment::getUser()->isAllowed('Accounting:Invoice', 'default')) {
					  
					  $context = Nette\Environment::getContext();
					  $context->application->getPresenter()->flashMessage(__('You don\'t have enough privileges to perform this action.'), 'warning');
					  $backlink = $context->application->storeRequest();
					  $context->application->getPresenter()->redirect(':System:Sign:in', array('backlink' => $backlink));
				  }				
				  
				  $filepath = $config['invoiceDir'] . '/' . $matches[1];
				  
				  if(file_exists($filepath))
					  return new vBuilder\Application\Responses\FileResponse(
									  $filepath,
									  null, 
									  Strings::endsWith($filename, '.pdf') ? 'application/pdf' : 'text/xml'
					  );
			  }
		  };
		  
		}
	}
	
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		$acl->addResource('Accounting');
		$acl->addResource('Accounting:Invoice', 'Accounting');
		$acl->addResource('Accounting:Employee', 'Accounting');
		
		$acl->addRole('Accounting Manager', 'User');
		$acl->allow('Accounting Manager', 'Accounting', Nette\Security\Permission::ALL);
	}
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		
		$user = Nette\Environment::getUser();
		
		if($user->isAllowed('Accounting:Invoice', 'default')) {
			$childMenus = array();
			
			$childMenus[] = array(
				'url' => Nette\Environment::getApplication()->getPresenter()->link(':Accounting:Invoice:default'),
				'label' => __('Invoices')
			);
			
			if($user->isAllowed('Accounting:Employee', 'default')) {
				$childMenus[] = array(
					'url' => Nette\Environment::getApplication()->getPresenter()->link(':Accounting:Employee:default'),
					'label' => __('Employees')
				);
			}
		
			$menu[] = array(
				'url' => Nette\Environment::getApplication()->getPresenter()->link(':Accounting:Invoice:default'),
				'label' => __('Accounting'),
				'icon' => System::getBasePath() . '/images/icons/small/grey/Money.png',
				'children' => $childMenus
			);
		}
		
		
		return $menu;
	}
	
}
