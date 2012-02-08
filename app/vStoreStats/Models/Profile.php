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

namespace vManager\Modules\vStoreStats;

use vManager, vBuilder, Nette;

/**
 * Profile data class
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 8, 2011
 */
class Profile extends vBuilder\Object {

	/** @var Nette\DI\IContainer DI context container */
	private $_context;
	
	/** @var string profile id */
	private $_id;
	
	/** @var array of DataSources\BaseDataSource */
	private $_dataSources = array();
	
	/** @var array of parameters */
	protected $_parameters;
	
	/** @var DateTime */
	private $_since = -1;
	
	/** @var DateTime */
	private $_until = -1;
	
	public function __construct($id, array $params, Nette\DI\IContainer $context) {
		$this->_context = $context;
		$this->_parameters = $params;
		$this->_id = $id;
		
		if(!isset($params['dataSources']))
			throw new vBuilder\InvalidConfigurationException('Missing data source configuration for vStoreStats.profiles.' . var_export($id, true));
		
		foreach((array) $params['dataSources'] as $k=>$curr) {
			$className = 'vManager\Modules\vStoreStats\DataSources\\';
			
			if(isset($curr['type'])) {
				$className .= $curr['type'];
				if(!class_exists($className))
					throw new Nette\InvalidStateException('vStoreStats data source ' . var_export($curr['type'], true) . ' does not exist');
			} else
				$className .= 'vBuilderCmsV2';
				
			$this->_dataSources[] = new $className($k, (array) $curr, $this);
		}
		
		if(count($this->_dataSources) == 0)
			throw new vBuilder\InvalidConfigurationException('At least one data source has to be configured for vStoreStats.profiles.' . var_export($id, true));
	}
	
	/**
	 * Returns context container
	 *
	 * @return Nette\DI\IContainer
	 */
	public function getContext() {
		return $this->_context;
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getName() {
		return isset($this->_parameters['name']) ? $this->_parameters['name'] : $this->id;
	}
	
	public function getUrl() {
		return isset($this->_parameters['url']) ? new Nette\Http\Url($this->_parameters['url']) : null;
	}
	
	/**
	 * Returns date and time of the first record on this profile
	 *
	 * @return DateTime
	 */
	public function getSince() {
		if($this->_since === -1) $this->gatherSinceUntil();
		return $this->_since;
	}
	
	/**
	 * Returns date and time of the last record on this profile
	 *
	 * @return DateTime
	 */
	public function getUntil() {
		if($this->_until === -1) $this->gatherSinceUntil();
		return $this->_until;
	}
	
	private function gatherSinceUntil() {
		$this->_until = null;
		$this->_since = null;
		
		foreach($this->_dataSources as $ds) {
			if($ds->getUntil() != null && ($this->_until === null || $ds->getUntil() > $this->_until))
				$this->_until = $ds->getUntil();
				
			if($ds->getSince() != null && ($this->_since === null || $ds->getSince() < $this->_since))
				$this->_since = $ds->getSince();
		}
	}
	
	/**
	 * Call to undefined method.
	 * @param  string  method name
	 * @param  array   arguments
	 * @return mixed
	 * @throws Nette\MemberAccessException
	 */
	public function __call($name, $args) {
		if ($name === '') {
			throw new Nette\MemberAccessException("Call to class '" . get_called_class() . "->name()' method without name.");
		}
		
		$satisfied = false;		
		foreach($this->_dataSources as $ds) {
			list($since, $until) = $args;
			
			if($until < $ds->since || $since > $ds->until) continue;
		
			$refl = $ds->getReflection();
			if($refl->hasMethod($name)) {
				$satisfied = true;
				$result = $refl->getMethod($name)->invokeArgs($ds, $args);
				
				// Dokud se nedoresi podpora pro vice result setu
				return $result;
			}
		}
		
		if(!$satisfied)
			throw new Nette\MemberAccessException("Call to undefined method " . get_called_class() . "::$name().");
	}

}