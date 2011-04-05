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

use Nette;

/**
 * Application specific implementation of PresenterFactory
 * for overloading name of Presenter classes
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class PresenterFactory extends Nette\Application\PresenterFactory {

	private $baseDir;
	
	/**
	 * @param  string
	 */
	public function __construct($baseDir = "", Nette\IContext $context = null) {
		if($context == null)
			$context = Nette\Environment::getContext();

		$this->baseDir = $baseDir;
		
		parent::__construct($baseDir, $context);
	}

	/**
	 * Formats presenter class name from its name.
	 * @param  string
	 * @return string
	 */
	public function formatPresenterClass($presenter) {
		if(($p = Nette\String::replace($presenter, "/^([^\\:]+)\\:(.+)$/", 'vManager\\Modules\\\${1}\\\${2}Presenter')) !== NULL)
			return $p;


		return parent::formatPresenterClass($presenter);
	}

	/**
	 * Formats presenter name from class name.
	 * @param  string
	 * @return string
	 */
	public function unformatPresenterClass($class) {
		if(($c = Nette\String::replace($class, "/^vManager\\\\Modules\\\\([^\\\\]+)\\\\(.+?)Presenter$/", '${1}:${2}')) !== NULL)
			return $c;

		return parent::unformatPresenterClass($class);
	}

	/**
	 * Formats presenter class file name.
	 * @param  string
	 * @return string
	 */
	public function formatPresenterFile($presenter) {
		if(($p = Nette\String::replace($presenter, "/^([^\\:]+)\\:(.+)$/", $this->baseDir . '/${1}/Presenters/${2}Presenter.php')) !== NULL)  
			return $p;
				
		return parent::formatPresenterFile($presenter);
	}

}
