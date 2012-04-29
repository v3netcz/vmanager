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

namespace vManager;

use vManager, vBuilder, Nette,
	vBuilder\Utils\Strings;

/**
 * Base vManager Texy
 * 
 * @author Jirka
 */
class Texy extends \Texy {
	
	/**
	 * We need ::link()
	 * @var Nette\Application\UI\Presenter
	 */
	protected $presenter;
	
	public function __construct(Nette\DI\IContainer $context) {
		parent::__construct();
		
		$this->encoding = 'utf-8';
		$this->allowedTags = static::NONE;
		$this->allowedStyles = static::NONE;
		$this->setOutputMode(static::XHTML1_STRICT);
		
		$this->addHandler('phrase', array($this, 'apiLinkHandler'));
		
		// We want ticket links only if the module is enabled...
		$modules = vManager\Application\ModuleManager::getModules();
		foreach ($modules as $module) {
			if ($module instanceof vManager\Modules\Tickets && $module->isEnabled()) {
				$this->presenter = $context->application->presenter;
				$this->addHandler('phrase', array($this, 'ticketLinkHandler'));
			}
		}
	}
	
	/** 
	 * "My link to API":api://namespace\namespace\Class
	 *		OR
	 * "My link to API":api://namespace\namespace\Class::method
	 *		OR
	 * "My link to API":api://namespace\namespace\Class::$property
	 *		OR
	 * "My link to API":api://namespace\namespace\Interface
	 * @param type $invocation
	 * @param type $phrase
	 * @param type $content
	 * @param type $modifier
	 * @param type $link 
	 */
	public function apiLinkHandler($invocation, $phrase, $content, $modifier, $link) {
		if (!$link) {
			return $invocation->proceed();
		}
		$url = $link->URL;

		if (Strings::startsWith($url, 'api://')) {
			$url = Strings::substring($url, 6);
			if (Strings::contains($url, '::')) { //class::method
				list($class, $method) = explode('::', $url);
			} else {
				$class = $url;
				$method = null;
			}
			$link->URL = $this->generateApiLink($class, $method);
		}

		return $invocation->proceed();
	}
	
	
	/**
	 * @param string $class class name with all namespaces
	 * @param string $method 
	 */
	protected function generateApiLink($class, $method = null) {		
		$baseUrl = 'http://api.vmanager.cz/internal/class-';
		
		if (Strings::startsWith($class, '\\')) {
			$class = Strings::substring($class, 1);
		}
		$url = $baseUrl . str_replace('\\', '.', $class) . '.html';
		if (isset($method)) {
			$url .= '#';
			if (!Strings::startsWith($method, '$')) { // property
				$url .= '_';
			}
			$url .= $method;
		}
		return $url;
	}
	
	/**
	 * "My link to ticket 123":#123
	 * -> Just like an ordinary Texy! link.
	 * @param type $invocation
	 * @param type $phrase
	 * @param type $content
	 * @param type $modifier
	 * @param type $link 
	 */
	public function ticketLinkHandler($invocation, $phrase, $content, $modifier, $link) {
		if (!$link) {
			return $invocation->proceed();
		}
		$url = $link->URL;

		if (Strings::match($url, '~^#\d+$~')) {
			$id = (int) Strings::substring($url, 1);
			$link->URL = $this->presenter->link(':Tickets:Ticket:detail', $id);
		}

		return $invocation->proceed();
	}
}
