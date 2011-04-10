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

namespace vManager\Application;

use vManager, vBuilder, Nette;

/**
 * Base module class
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class Module extends vBuilder\Object {
	
	private static $instance = array();
	
	/**
	 * Factory method
	 * 
	 * PHP 5.3 required!
	 * 
	 * @return Module 
	 */
	final public static function getInstance() {
		$c = get_called_class();
		
		
		if(!isset(self::$instance[$c])) {
			self::$instance[$c] = new $c;
		}
		
		return self::$instance[$c];
	}
	
	public function getName() {
		return substr(get_called_class(), 17);
	}
	
	/**
	 * Returns version number. Default implementation returns 1.00.
	 * For formating options see vBuilder\Framework
	 * 
	 * @return int
	 */
	public function getVersion() {
		return 10000;
	}
	
	/**
	 * Returns configuration array for this module
	 * 
	 * @return array
	 */
	public function getConfig() {
		return (Array) Nette\Environment::getConfig($this->getName(), array());
	}
	
	/**
	 * Returns true if this module is enabled in current configuration
	 * 
	 * @return bool
	 */
	public function isEnabled() {
		$c = $this->getConfig();
		return isset($c["enabled"]) && $c["enabled"];
	}
	
}
