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

namespace vManager\Modules\Tickets;

use vManager, Nette, vBuilder\Orm\Repository;

/**
 * Visual component for rendering comment list
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 27, 2011
 */
class VersionableEntityView extends Nette\Application\UI\Control {
	
	/** @var string name of ORM entity */
	protected $entityName;
	
	/** @var int PK ID of entity */
	protected $id;
	
	/** @var array of verisons of entity */
	protected $data;
	
	/**
	 * Component constructor.
	 * 
	 * @param string $entityName
	 * @param int $id 
	 */
	function __construct($entityName, $id) {
		$this->entityName = $entityName;
		$this->id = $id;
	}
	
	/**
	 * Loads data from DB
	 */
	protected function load() {
		$fluent = Repository::findAll($this->entityName)
				  ->where('[ticketId] = %i', $this->id)
				  ->clause('ORDER BY ABS([revision])');

		$this->data = $fluent->fetchAll(); 
	}
	
	/**
	 * Returns template
	 * 
	 * @return Nette\Templates\ITemplate
	 */
	function createTemplate() {
		$tpl = parent::createTemplate();
		$tpl->setFile(__DIR__ . '/../Templates/VersionableEntityView/default.latte');
		
		$texy = new \Texy();
      $texy->encoding = 'utf-8';
      $texy->allowedTags = \Texy::NONE;
      $texy->allowedStyles = \Texy::NONE;
      $texy->setOutputMode(\Texy::XHTML1_STRICT);
		
		$tpl->registerHelper('texy', callback($texy, 'process'));
		
		$tpl->data = end($this->data);
		$tpl->history = $this->data; 
		
		return $tpl;
	}
	
	/**
	 * Renders control to standard output
	 */
	function render() {
		$this->load();
		$this->getTemplate()->render();
	}

}
