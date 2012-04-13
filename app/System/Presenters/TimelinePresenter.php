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
	Nette;

/**
 * Timeline presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 13, 2012
 */
class TimelinePresenter extends SecuredPresenter {

	private $_timelineEnabledModules;
	private $_recordRendererInstances;

	public function renderDefault() {
		$until = new \DateTime;
		$since = clone $until;
		$since->sub(\DateInterval::createFromDateString('2 months'));
			  
		$this->template->records = $this->getTimelineRecords($since, $until);
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
	 *
	 * @return array of vManager/Timeline/IRecord
	 * @throws Nette\InvalidArgumentException if gathered results are invalid
	 */
	public function getTimelineRecords(\DateTime $since, \DateTime $until) {
		if(count($this->getTimelineEnabledModules()) == 0)
			throw new Nette\InvalidStateException("No timeline enabled modules has been loaded");
			
		$data = array();
			
		foreach($this->getTimelineEnabledModules() as $module) {
			$moduleData = $module->getTimelineRecords($since, $until);
			
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
	
}