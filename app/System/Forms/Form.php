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

use vBuilder, Nette, Nette\Forms\Container;

Container::extensionMethod('addDatePicker', function (Container $container, $name, $label = NULL) {
    return $container[$name] = new \JanTvrdik\Components\DatePicker($label);
});

/**
 * Class extending Nette Application Form and implementing default behavior for
 * vManager (templating, custom fields, etc.)
 *
 * @author Adam Staněk (V3lbloud)
 * @since May 5, 2011
 */
class Form extends Nette\Application\UI\Form {
	
	/**
	 * Application form constructor.
	 */
	public function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
		
		$this->getElementPrototype()->novalidate = 'novalidate';
		$this->setRenderer(new Forms\DefaultFormRenderer());
	}
	
	/**
	 * Loads values for form fields from entity
	 * 
	 * @param Entity $e
	 */
	public function loadFromEntity(vBuilder\Orm\Entity $e) {
		$form = $this;
		
		foreach($e->getMetadata()->getFields() as $key) {
			if(isset($form[$key])) $form[$key]->setDefaultValue($e->$key);
		}
	}
	
}
