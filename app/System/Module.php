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

use vManager, Nette;

/**
 * Base vManager system module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class System extends vManager\Application\Module implements vManager\Application\IMenuEnabledModule {
	
	/**
	 * Returns true if this module is enabled in current configuration
	 * 
	 * @return bool
	 */
	public function isEnabled() {
		return true; // System module has to be always enabled
	}
	
	/**
	 * Returns menu structure for this module
	 *
	 * @return array of menu items
	 */
	public function getMenuItems() {
		$menu = array();
		$menu[] = array(
			 'url' => Nette\Environment::getApplication()->getPresenter()->link(':System:Homepage:default'),
			 'label' => 'Homepage',
			 'icon' => self::getBasePath() . '/images/icons/small/grey/Home.png'
		);
		return $menu;
	}
	
	/**
	 * Returns base path to WWW directory
	 * 
	 * @return string
	 */
	public static function getBasePath() {
		$baseUri = rtrim(Nette\Environment::getVariable('baseUri', NULL), '/');
		return preg_replace('#https?://[^/]+#A', '', $baseUri);
	}
	
}