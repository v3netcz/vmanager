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

namespace vManager\Modules\vStoreStats\DataSources;

use vManager,
	vBuilder,
	Nette,
	vManager\Modules\vStoreStats\Profile;

/**
 * Base data source
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 8, 2011
 */
abstract class BaseDataSource extends vBuilder\Object {

	/** @var vManager\Modules\vStoreStats\Profile */
	private $_profile;
	
	/** @var string */
	private $_id;
	
	/** @var DibiConnection */
	private $_connection;
	
	/** @var array of parameters */
	protected $_parameters;
	
	
	/**
	 * Returns date and time of the first record from this source
	 *
	 * @return DateTime
	 */
	abstract public function getSince();
	
	/**
	 * Returns date and time of the last record from this source
	 *
	 * @return DateTime
	 */
	abstract public function getUntil();
	
	public function __construct($id, array $parameters, Profile $profile) {
		$this->_profile = $profile;
		$this->_id = $id;
		$this->_parameters = $parameters;
	}
	
	/**
	 * Returns DS ID
	 *
	 * @return string
	 */
	public function getId() {
		return $this->_id;
	}
	
	/**
	 * Returns bound profile
	 *
	 * @return vManager\Modules\vStoreStats\Profile
	 */
	public function getProfile() {
		return $this->_profile;
	}
	
	/**
	 * Returns context container
	 *
	 * @return Nette\DI\IContainer
	 */
	public function getContext() {
		return $this->profile->context;
	}
	
	/**
	 * Returns DB connection
	 *
	 * @return DibiConnection
	 */
	public function getConnection() {
		if(!isset($this->_connection)) {
			
			$config = array_merge($this->context->database->connection->getConfig(), (array) $this->_parameters['dbConnection']);
			$this->_connection = new \DibiConnection($config);
		}
		
		return $this->_connection;
	}

}