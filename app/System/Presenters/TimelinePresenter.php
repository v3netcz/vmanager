<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vManager is free software: you can redistribute it and/or modify
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

namespace vManager\Modules\System;

use vManager,
	vManager\Timeline\IRecord,
	vManager\Timeline\IRecordRenderer,
	vManager\Form,
	Nette;

/**
 * Timeline presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 13, 2012
 */
class TimelinePresenter extends SecuredPresenter {

	/** @persistent */
	public $forUser;

	private $_timelineEnabledModules;
	private $_recordRendererInstances;
	private $_availableUsers;

	public function startup() {
		parent::startup();

		// Validace uzivatelskeho jmena	
		if($this->forUser !== null) {
			$found = false;
			foreach($this->getAvailableUsers() as $curr) {
				if($curr->id == $this->forUser) {
					$found = true;
					break;
				}
			}
			
			if(!$found) {
				$this->flashMessage(_x("Cannot show timeline for user id %d", array($this->forUser)));
				$this->forUser = null;
				$this->redirect('this');
			}
		}
	
	}

	protected function getAvailableUsers() {
		if(!isset($this->_availableUsers)) {
			$uids = array();
		
			foreach($this->getTimelineEnabledModules() as $moduleInstance) {
				foreach($moduleInstance->getTimelineUsers() as $uid) {
					if(!in_array($uid, $uids))
						$uids[] = $uid;
				}
			}

			$this->_availableUsers = $this->context->repository->findAll('vManager\\Security\\User')->where('[id] IN %in', $uids);
		}
		
		return $this->_availableUsers;
	}

	public function renderDefault() {
		$until = new \DateTime;
		$since = clone $until;
		$since->sub(\DateInterval::createFromDateString('6 months'));
			  
		$uids = array();
		if($this->forUser == null)
			foreach($this->getAvailableUsers() as $user) $uids[] = $user->id;
		else
			$uids[] = $this->forUser;
			  
			  
		$this->template->showUserFilter = count($this->getAvailableUsers()) > 1;
		$this->template->records = array_slice($this->getTimelineRecords($since, $until, $uids), 0, 50);
	}
	
	protected function createComponentTimelineFilter($name) {
		$form = new Form($this, $name);		
		
		$forUserValues = array();
		foreach($this->getAvailableUsers() as $curr) {
			if($curr->isInRole('Ticket user'))
				$forUserValues[$curr->id] = $curr->displayName;
		}
		
		$form->addSelect('forUser', __('For user:'), $forUserValues)
			->setPrompt(__('-- All users --'))
			->setDefaultValue($this->forUser);

		$form->addSubmit('s', __('Filter'));

		$form->onSuccess[] = callback($this, $name . 'Submitted');
	}
	
	public function timelineFilterSubmitted(Form $form) {
		$values = $form->getValues();
		
		$this->forUser = $values->forUser;
		$this->redirect('this');
	}
	
	/**
	 * Returns instance of renderer for given record
	 *
	 * @param vManager\Timeline\IRecord
	 *
	 * @return vManager\Timeline\IRecordRenderer
	 * @throws Nette\InvalidStateException if renderer class is invalid
	 */
	public function getRecordRenderer(IRecord $record) {
		if(!isset($this->_recordRendererInstances[$record->getRendererClass()])) {
			$class = $record->getRendererClass();
		
			if(!class_exists($class))
				throw new Nette\InvalidStateException("Timeline record renderer class " . var_export($class, true) . " requested by " . get_class($record) . ' does not exist');
				
			$instance = new $class($this);
			if(!($instance instanceof IRecordRenderer))
				throw new Nette\InvalidStateException("Class " . var_export($class, true) . " should implement vManager\\Timeline\\IRecordRenderer interface but it does not");
				
			$this->_recordRendererInstances[$class] = $instance;
		}
		
		return $this->_recordRendererInstances[$record->getRendererClass()];
	}
	
	/**
	 * Returns array of timeline records (ordered by timestamp)
	 *
	 * @param DateTime since
 	 * @param DateTime until
 	 * @param int user id
	 *
	 * @return array of vManager/Timeline/IRecord
	 * @throws Nette\InvalidArgumentException if gathered results are invalid
	 */
	public function getTimelineRecords(\DateTime $since, \DateTime $until, $forUid) {
		if(count($this->getTimelineEnabledModules()) == 0)
			throw new Nette\InvalidStateException("No timeline enabled modules has been loaded");
			
		$data = array();
			
		foreach($this->getTimelineEnabledModules() as $module) {
			$moduleData = $module->getTimelineRecords($since, $until, array_intersect($forUid, $module->getTimelineUsers()));
			
			if(!is_array($moduleData))
				throw new Nette\InvalidArgumentException(get_class($module) . '::getTimelineRecords has to return array, ' .gettype($moduleData) . ' given');
			
			foreach($moduleData as $record) {
				if(!($record instanceof IRecord))
					throw new Nette\InvalidArgumentException(get_class($module) . '::getTimelineRecords has to return array of vManager\Timeline\IRecord, array of ' .gettype($record) . ' given');	
					
				$data[] = $record;
			}
		}
		
		usort($data, function ($a, $b) {
			if($a->getTimestamp() == $b->getTimestamp()) return 0;
			
			return ($a->getTimestamp() > $b->getTimestamp()) ? -1 : 1;
		});
		
		return $data;
	}
	
	
	/**
	 * Returns instances of all timeline capable modules
	 *
	 * @return array of vManager\Application\ITimelineEnabledModule
	 */
	protected function getTimelineEnabledModules() {
		if(!isset($this->_timelineEnabledModules)) {
			$this->_timelineEnabledModules = array();
			
			foreach(vManager\Application\ModuleManager::getModules() as $moduleInstance) {
				if($moduleInstance instanceof vManager\Application\ITimelineEnabledModule) {
					$this->_timelineEnabledModules[] = $moduleInstance;
				}
			}
		}
		
		return $this->_timelineEnabledModules;
	}
	
	protected function createTemplate($class = null) {
		$template = parent::createTemplate($class);
		$template->registerHelper('dayOfWeekInWords', 'vManager\Application\Helpers::dayOfWeekInWords');
		
		return $template;
	}
	
}