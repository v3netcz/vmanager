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

use vManager,
	 Nette;

/**
 * Module manager class.
 * 
 * Module classes have to extend vManager\Application\Module and they have to be
 * in vManager\Modules namespace.
 * 
 * If you add any module you need to clear applicaiton cache.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class ModuleManager {

	private static $modules;

	/**
	 * Returns array of all vManager modules (even not enabled ones)
	 * 
	 * @return array of modules 
	 */
	public static function getModules() {
		if(self::$modules !== null)
			return self::$modules;

		$cache = Nette\Environment::getCache();
		if(isset($cache["moduleClasses"]))
			$moduleClasses = (Array) $cache["moduleClasses"];
		else {
			$moduleClasses = array();
			
			$loaders = Nette\Loaders\AutoLoader::getLoaders();
			$classes = array();
			if(count($loaders) > 0) {
				foreach($loaders as $loader) {
					if($loader instanceof Nette\Loaders\RobotLoader) {
						$classes = \array_keys($loader->getIndexedClasses());
						break;
					}
				}
			}

			if(count($classes) == 0) $classes = get_declared_classes();

			foreach($classes as $className) {
				// Protoze je to vyrazne rychlejsi nez overovat interface pro vsechny
				if(Nette\Utils\Strings::startsWith($className, 'vManager\\Modules\\')) {
					$class = new Nette\Reflection\ClassType($className);
					if($class->isSubclassOf('vManager\\Application\\Module'))
						$moduleClasses[] = $className;
				}
			}
			
			$cache["moduleClasses"] = $moduleClasses;
		}
		
		self::$modules = array();
		foreach($moduleClasses as $class) {
			self::$modules[] = $class::getInstance();
		}
		
		return self::$modules;
	}

}
