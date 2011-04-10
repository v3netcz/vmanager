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
 * Navigation model. Generates menu items from loaded modules.
 * For ordering items set menuPriority to desired number in module config.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 6, 2011
 */
class Navigation {

	private static $menuItems;
	
	/**
	 * Returns array of menu items.
	 * 
	 * TODO: Upravit tak, aby se dalo nerekurzivne prolezt vice vrstev pro jednodussi
	 * generovani seznamu ze sablon
	 * 
	 * @return array
	 */
	static function getMenuItems() {
		if(static::$menuItems !== null) return static::$menuItems;
		
		$menuItems = array();
		
		foreach(ModuleManager::getModules() as $module) {
			if($module->getReflection()->implementsInterface('vManager\\Application\\IMenuEnabledModule')) {				
				if($module->isEnabled()) {
					$c = $module->getConfig();

					if($module->getName() == 'System')
						$priorityGroup = 0;
					else {
						$priorityGroup = isset($c['menuPriority']) ? intval($c['menuPriority']) : 100;
						if($priorityGroup < 1) $priorityGroup = 100;
					}

					$m = $module->getMenuItems();
					if(count($m) > 0)
						$menuItems[$priorityGroup][] = $m;
				}
			}
		}
		
		ksort($menuItems);
		static::$menuItems = array();
		foreach($menuItems as $curr) {
			foreach($curr as $curr2)
				foreach($curr2 as $curr3)
					static::$menuItems[] = $curr3;
		}
		
		return static::$menuItems;
	}
	
}
