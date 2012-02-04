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
 * Gettext translator for this application
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 11, 2011
 */
class Translator extends vBuilder\Localization\Translator {

	public static function createService(Nette\DI\Container $context) {
		$dictStorage = new vBuilder\Localization\Storages\Gettext('%dir%/%lang%.mo');
	
		$instance = new static();
		$instance->setStorage($dictStorage);

		$config = $context->userConfig;
		$lang = $config->get('system.language');
		if($lang === null) $lang = $context->httpRequest->detectLanguage((array) $context->parameters['languages']);
		if($lang != null) $instance->setLang($lang);
		
		foreach(ModuleManager::getModules() as $curr) {
			if($curr->isEnabled() && is_dir($curr->getTranslationsDir()))
				  $instance->addDictionary($curr->getName(), $curr->getTranslationsDir());
		}	
		
		return $instance;
	}

}