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

namespace vManager\Modules\System;

use vManager, Nette;

/**
 * Father of all application presenters.
 * 
 * Overloads template file names.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class BasePresenter extends Nette\Application\UI\Presenter {

	/**
	 * Formats view template file names.
	 * @param  string
	 * @param  string
	 * @return array
	 */
	public function formatTemplateFiles() {
		$presenter = $this->getName();
		//$presenter = substr($name, strrpos(':' . $name, ':'));
		
		if(($path = Nette\Utils\Strings::replace($presenter, "/^([^\\:]+)\\:(.+)$/", '/${1}/${2}')) === NULL)
			throw new \LogicException('Something wrong with presenter name');

		$appDir = Nette\Environment::getVariable('appDir');
		$pathP = substr_replace($path, '/Templates', strrpos($path, '/'), 0);
		$path = substr_replace($path, '/Templates', strrpos($path, '/'));
		return array(
			 "$appDir$pathP/$this->view.latte",
			 "$appDir$pathP.$this->view.latte",
			 "$appDir$pathP/$this->view.phtml",
			 "$appDir$pathP.$this->view.phtml",
			 "$appDir$path/@global.$this->view.phtml", // deprecated
		);
	}

	/**
	 * Formats layout template file names.
	 * @param  string
	 * @param  string
	 * @return array
	 */
	public function formatLayoutTemplateFiles() {		
		$list = array();
		$name = $this->getName();
		$presenter = substr($name, strrpos(':' . $name, ':'));
		$layout = $this->layout ? $this->layout : 'layout';
		
		foreach(array($presenter, "System:Default") as $curr) {
			if(($path = Nette\Utils\Strings::replace($curr, "/^([^\\:]+)\\:(.+)$/", '/${1}/${2}')) === NULL)
				throw new \LogicException('Something wrong with presenter name');

			$appDir = Nette\Environment::getVariable('appDir');
			$pathP = substr_replace($path, '/Templates', strrpos($path, '/'), 0);
			$list = array_merge($list, array(
				 "$appDir$pathP/@$layout.latte",
				 "$appDir$pathP.@$layout.latte",
				 "$appDir$pathP/@$layout.phtml",
				 "$appDir$pathP.@$layout.phtml",
			));
		}
		
		while(($path = substr($path, 0, strrpos($path, '/'))) !== FALSE) {
			$list[] = "$appDir$path/Templates/@$layout.latte";
			$list[] = "$appDir$path/Templates/@$layout.phtml";
		}
		
		return $list;
	}

}
