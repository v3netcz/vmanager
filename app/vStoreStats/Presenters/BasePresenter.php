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

use vManager,
	Nette;

/**
 * Base presenter of vStore stats
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 4, 2011
 */
class BasePresenter extends vManager\Modules\System\SecuredPresenter {
	
	private $_connection;
	private $_profileNames;
	private $_profiles = array();
	
	/** @persistent */
	public $profileId;
	
	public function startup() {
		parent::startup();

		if(!isset($this->profileId))
			list($this->profileId) = array_keys($this->getProfileNames());

	}	
	
	/**
	 * Returns array of profile names
	 *
	 * @return array
	 */
	public function getProfileNames() {
		if(!isset($this->_profileNames)) {
			if(!isset($this->context->parameters['vStoreStats']['profiles']))
				throw new vBuilder\InvalidConfigurationException('Missing vStoreStats.profiles');
		
			$this->_profileNames = array();
			foreach((array) $this->context->parameters['vStoreStats']['profiles'] as $k => $profile) {
				$this->_profileNames[$k] = isset($profile['name']) ? $profile['name'] : $k;
			}
		}
			
		return $this->_profileNames;
	}
	
	/**
	 * Returns specified / current profile
	 *
	 * @param string profile key
	 */
	public function getProfile($id = null) {
		if($id === null) $id = $this->profileId;
	
		if(!isset($this->_profiles[$id])) {
			if(!isset($this->context->parameters['vStoreStats']['profiles'][$id]))
				throw new Nette\InvalidStateException("Profile " . var_export($id, true) . " is not defined");
		
			$this->_profiles[$id] = new Profile($id, (array) $this->context->parameters['vStoreStats']['profiles'][$id], $this->context);
		}
		
		return $this->_profiles[$id];
	}
	
	public function createTemplate($class = null) {
		$template = parent::createTemplate($class);
		$template->registerHelper('currency', 'vBuilder\Latte\Helpers\FormatHelpers::currency');
		return $template;
	}
	
}