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

use vManager, vBuilder, Nette,
	 vManager\Modules\System,
	 vManager\Modules\Tickets\Ticket;

/**
 * Ticketing system module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class Tickets extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule,
	vManager\Application\IAclEnabledModule {
	
	/**
	 * Constructor. Initializes module and registers event handlers
	 */
	public function __construct() {
		// TODO: udelat to konfigurovatelny
		Ticket::$onTicketCreated[] = callback(__CLASS__ . '\\TicketChangeMailer::ticketCreated');
		Ticket::$onTicketUpdated[] = callback(__CLASS__ . '\\TicketChangeMailer::ticketUpdated');
	}
	
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		$acl->addResource('Tickets');
		
		// Presentery
		$acl->addResource('Tickets:Default', 'Tickets');
		$acl->addResource('Tickets:Ticket', 'Tickets');
		$acl->addResource('Tickets:Project', 'Tickets');
				
		// Uzivatel ticketovaciho systemu
		$acl->addRole('Ticket user', 'User');
		$acl->allow('Ticket user', 'Tickets', Nette\Security\Permission::ALL);	// Presentery
				
		// Administrator ticketovaciho systemu		
		$acl->addRole('Ticket admin', 'Ticket user');				
				
		// Uzivatel systemu projektu
		$acl->addRole('Project user', 'User');
		$acl->allow('Project user', 'Tickets', Nette\Security\Permission::ALL);	// Presentery
				
		// Administrator systemu projektu
		$acl->addRole('Project admin', 'Project user');		
		
	}
	
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		
		if(Nette\Environment::getUser()->isAllowed('Tickets:Default', 'default')) {
			$menu[] = array(
				 'url' => Nette\Environment::getApplication()->getPresenter()->link(':Tickets:Ticket:default'),
				 'label' => __('Tasks'),
				 'icon' => System::getBasePath() . '/images/icons/small/grey/Flag.png',
				 'children' => array(
					  array(
							'url' => Nette\Environment::getApplication()->getPresenter()->link(':Tickets:Ticket:default'),
							'label' => __('My tickets')
					  ),
					  array(
							'url' => Nette\Environment::getApplication()->getPresenter()->link(':Tickets:Ticket:create'),
							'label' => __('Create a new ticket')
					  )
				 )
			);
		}

		if(Nette\Environment::getUser()->isAllowed('Tickets:Default', 'default')) {
			$menu[] = array(
				 'url' => Nette\Environment::getApplication()->getPresenter()->link(':Tickets:Project:default'),
				 'label' => __('Projects'),
				 'icon' => System::getBasePath() . '/images/icons/small/grey/PowerPoint%20Documents.png',
				 'children' => array(
					  array(
							'url' => Nette\Environment::getApplication()->getPresenter()->link(':Tickets:Project:default'),
							'label' => __('Show projects')
					  ),
					  array(
							'url' => Nette\Environment::getApplication()->getPresenter()->link(':Tickets:Project:create'),
							'label' => __('Create a new project')
					  )
				 )
			);
		}
		
		return $menu;
	}

}
