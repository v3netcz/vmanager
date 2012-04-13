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
	 vManager\Modules\Tickets\Ticket,
	 vManager\Modules\Tickets\Attachment;

/**
 * Ticketing system module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class Tickets extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule,
	vManager\Application\IAclEnabledModule, vManager\Application\ITimelineEnabledModule {
	
	protected $_availableTicketStates;
	protected $_finalTicketStates;
	protected $_initialTicketStates;
	
	/**
	 * Constructor. Initializes module and registers event handlers
	 */
	public function __construct() {
		// TODO: udelat to konfigurovatelny
		Ticket::$onTicketCreated[] = callback(__CLASS__ . '\\TicketChangeMailer::ticketCreated');
		Ticket::$onTicketUpdated[] = callback(__CLASS__ . '\\TicketChangeMailer::ticketUpdated');
		
		Attachment::registerAttachmentFileHandler();
	}
	
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		$acl->addResource('TaskManagement');
		
		// Presentery
		$acl->addResource('Tickets:Ticket', 'TaskManagement');
		$acl->addResource('Tickets:Project', 'TaskManagement');
				$acl->addResource('Tickets:JsonConnector', 'TaskManagement');
				
		// Uzivatel ticketovaciho systemu
		$acl->addRole('Ticket user', 'User');
		$acl->allow('Ticket user', 'Tickets:Ticket', Nette\Security\Permission::ALL);
		$acl->allow('Ticket user', 'Tickets:Project', 'default');	
		$acl->allow('Ticket user', 'Tickets:Project', 'detail');
				
		// Administrator ticketovaciho systemu		
		$acl->addRole('Project manager', 'Ticket user');			
		$acl->allow('Project manager', 'TaskManagement', Nette\Security\Permission::ALL);						
	}
	
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		
		$user = Nette\Environment::getUser();
		
		if($user->isAllowed('Tickets:Ticket', 'default')) {
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

			if($user->isAllowed('Tickets:Project', 'create')) {
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
			} else {
				$menu[] = array(
					 'url' => Nette\Environment::getApplication()->getPresenter()->link(':Tickets:Project:default'),
					 'label' => __('Projects'),
					 'icon' => System::getBasePath() . '/images/icons/small/grey/PowerPoint%20Documents.png'
				);
			}
		}
		
		return $menu;
	}
	
	/**
	 * Returns array of timeline records
	 *
	 * @param DateTime since
 	 * @param DateTime until
	 *
	 * @return array of vManager/Timeline/IRecord
	 */
	public function getTimelineRecords(\DateTime $since, \DateTime $until) {
		$data = array();
		$context = Nette\Environment::getContext();
		
		$ticketRevisions = $context->connection->select('[ticketId], [name], [timestamp], [author], [revision], [state]')
						->from(Ticket::getMetadata()->getTableName())
						->where('[timestamp] BETWEEN %d AND %t', $since, $until)
						->fetchAll();
		
		// Pokud se nejedna o projektoveho manazera, vyselektuju jen ukoly, kterych
		// se dotycny ucastnil
		if(!$context->user->identity->isInRole('Project manager')) {
			$uid = $context->user->id;
		
			$myTickets = $context->connection->select('DISTINCT [ticketId]')
						->from(Ticket::getMetadata()->getTableName())
						->where('[author] = %i OR [assignedTo] = %i', $uid, $uid)
						->fetchAll();
			
			foreach($ticketRevisions as $key=>$curr) {
				$found = false;
				foreach($myTickets as $curr2) {
					if($curr->ticketId == $curr2->ticketId) {
						$found = true;
						break;
					}
				}
				
				if(!$found)
					unset($ticketRevisions[$key]);
			}
			
		}
			
						
		foreach($ticketRevisions as $curr) {
			$record = new Tickets\TimelineRecord($curr->timestamp);
			$record->ticketId = $curr->ticketId;
			$record->ticketName = $curr->name;
			$record->author = $context->repository->get('vManager\\Security\\User', $curr->author);
			
			if($curr->revision > 0) {
				$record->hasBeenCreated = $curr->revision == 1;
				$record->hasBeenSolved = false;
				
				foreach($this->getFinalTicketStates() as $state) {
					if($curr->state == $state->id) {
						$record->hasBeenSolved = true;
						break;
					}
				}
			}
			
			$data[] = $record;
		}
		
	
		return $data;
	}
	
	/**
	 * Returns ticket state object
	 * 
	 * @param string id of method
	 * @return ITicketState 
	 */
	public function getTicketState($id) {
		if($id === null) return null;
		
		if(!isset($this->availableTicketStates[$id]))
			throw new Nette\InvalidArgumentException("Ticket state '$id' is not defined");
		
		return $this->availableTicketStates[$id];
	}
	
	/**
	 * Returns default ticket state
	 * 
	 * @return ITicketState
	 */
	public function getDefaultTicketState() {
		$s = $this->availableTicketStates;
		
		return reset($s);
	}
	
	/**
	 * Returns all available ticket states
	 * 
	 * @return array of ITicketState
	 */
	public function getAvailableTicketStates() {
		if(!isset($this->_availableTicketStates)) {
			$this->_availableTicketStates = array();
			
			if(isset($this->config['ticketStates'])) {
				foreach($this->config['ticketStates'] as $id => $stateConfig) {
										
					if(isset($stateConfig['type'])) {
						$class = 'vManager\\Modules\\Tickets' . ucfirst($class) . 'TicketState';
					} else
						$class = 'vManager\\Modules\\Tickets\\TicketState';
					
					$this->_availableTicketStates[$id] = $class::fromConfig($id, $stateConfig, $this);
				}
			}
						
			if(count($this->_availableTicketStates) == 0) throw new Nette\InvalidStateException('No ticket states defined');
		}
		
		return $this->_availableTicketStates;
	}
	
	/**
	 * Returns all final (resolved) ticket states
	 * 
	 * @return array of ITicketState
	 */
	public function getFinalTicketStates() {
		if(!$this->_finalTicketStates) {
			$this->_finalTicketStates = array();
			
			foreach($this->availableTicketStates as $curr) {
				if($curr->isFinal()) $this->_finalTicketStates[] = $curr;
			}
		}
		
		return $this->_finalTicketStates;
	}
	
	/**
	 * Returns all initial ticket states
	 * 
	 * @return array of ITicketState
	 */
	public function getInitialTicketStates() {
		if(!$this->_initialTicketStates) {
			$this->_initialTicketStates = array();
			
			foreach($this->availableTicketStates as $curr) {
				if($curr->isInitial()) $this->_initialTicketStates[] = $curr;
			}
		}
		
		return $this->_initialTicketStates;
	}

}
