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

use vManager, Nette, NetteTranslator;

/**
 * Gettext translator for this application
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 11, 2011
 */
class Translator extends NetteTranslator\Gettext {

	public static function getTranslator($options) {
		$dirs = array();
		foreach(ModuleManager::getModules() as $curr) {
			if($curr->isEnabled() && is_dir($curr->getTranslationsDir()))
				  $dirs[] = $curr->getTranslationsDir();
		}
		
		return new static($dirs, Nette\Environment::getVariable('lang', 'en'));
	}

}