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

use vManager, Nette, vBuilder\Orm\Entity;

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
	/** @var string name id column */
	protected $idField;

	/** @var array of verisons of entity */
	protected $data;
	/** @var array of file attachments */
	protected $attachments;

	const ASC = 1;
	const DESC = 2;

	/** @var int method of ordering comments */
	protected $order = VersionableEntityView::DESC;

	protected $context;
	
	/**
	 * Component constructor.
	 * 
	 * @param string $entityName
	 * @param int $id 
	 * @param string $idField
	 */
	function __construct($entityName, $id) {
		$this->entityName = $entityName;
		$this->id = $id;
		$this->context = Nette\Environment::getContext();
		$this->load();
	}

	/**
	 * Loads data from DB
	 *
	 */
	protected function load() {           
		$entity = self::getEntityClass($this->entityName);		
	    $metadata = $entity::getMetadata();
    	$idFields = $metadata->getIdFields();
		$fluent = $this->context->repository->findAll($this->entityName)
				  ->where('[' . $metadata->getFieldColumn($idFields[0]) . '] = %i', $this->id)
				  ->clause('ORDER BY ABS([revision])' . ($this->order == self::DESC ? ' DESC' : ''));

		$this->data = $fluent->fetchAll();

		// Docasne reseni, dokud nejsou joiny (prednacteni)
		$commentIds = array();
		foreach($this->data as $curr)
			if($curr->comment !== null)
				$commentIds[] = $curr->comment->id;
		if(count($commentIds))
			$this->context->repository->findAll('vManager\Modules\Tickets\Comment')->where('[commentId] IN %in', $commentIds)->orderBy('[commentId] DESC')->fetchAll();

		// Vsechny soubory (nesmim prochazet data z predchoziho dotazu, protoze to jsou jine objekty, nez davaji revize -> 2 dotazy)
		$this->attachments = array();
		foreach($this->data as $revision) {
			if(!$revision->comment) continue;
			
			foreach($revision->comment->attachments as $curr) {
				$key = $curr->getName().'@'.$curr->getType();

				if(array_key_exists($key, $this->attachments)) {
					if(is_array($this->attachments[$key]))
						$this->attachments[$key][] = $curr;
					else
						$this->attachments[$key] = array($this->attachments[$key], $curr);
				} else
					$this->attachments[$key] = $curr;
			}
		}
	}

	/**
	 * Returns template
	 * 
	 * @return Nette\Templates\ITemplate
	 */
	function createTemplate($class = NULL) {
		$tpl = parent::createTemplate();
		$tpl->setFile(__DIR__.'/../Templates/VersionableEntityView/default.latte');

		$texy = new \Texy();
		$texy->encoding = 'utf-8';
		$texy->allowedTags = \Texy::NONE;
		$texy->allowedStyles = \Texy::NONE;
		$texy->setOutputMode(\Texy::XHTML1_STRICT);

		$tpl->registerHelper('texy', callback($texy, 'process'));
		$tpl->registerHelper('timeAgoInWords', 'vManager\Application\Helpers::timeAgoInWords');
		$tpl->registerHelper('timeDiffInWords', 'vManager\Application\Helpers::timeDiffInWords');

		$tpl->order = $this->order;
		$tpl->data = $this->order == self::DESC ? reset($this->data) : end($this->data);
		$tpl->history = $this->data;
		$tpl->attachments = $this->attachments;

		if(!$tpl->data)
			throw new Nette\InvalidStateException("No data for " . $this->entityName . " with id " . $this->id);

		return $tpl;
	}

	/**
	 * Renders control to standard output
	 */
	function render() {
		$this->getTemplate()->render();
	}
	
	/**
	 * Helper function for getting class from entity name
	 * 
	 * @param string entity name
	 * @return string|bool return false, if no such class has been found 
	 */
	protected function getEntityClass($entity) {
		if(class_exists($entity)) {
			return $entity;
		}
		
		return false;
	}	
}
