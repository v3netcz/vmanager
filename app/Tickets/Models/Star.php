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

use vManager,
	 vBuilder,
	 Nette,
	 vBuilder\Orm;

/**
 * Star
 * 
 * This class makes it possible to mark any entity (a descendant of vBuilder\Orm\ActiveEntity)
 * with a star. All fields are mandatory however the usage may vary:
 * 
 * <code>
 * $star = $repository->create('vManager\Modules\Tickets\Star');
 * $star->user = $this->user->identity;
 * 
 * 
 * $star->entity = 'vManager\Modules\MyModule\MyEntity';
 * $star->entityId = 5; // Can't know the id from the string above...
 * 
 * // OR
 * 
 * 
 * $star->entity = $myEntityInstance;
 * // $star->entityId will be taken automatically from $star->entity.
 * 
 * 
 * 
 * $star2 = $repository->findAll('vManager\Modules\Tickets\Star')
 *					->where('[userId] = %i',$user->id)
 *					->fetchAll();
 * $entity = $star2->getEntity() // An instance is created automatically based on
 *								 // entityId.
 * 
 * @author Jirka
 * 
 * @Table(name="pm_stars")
 * 
 * @Column(id, realName="starId", pk, type="integer", generatedValue)
 * @Column(user, realName="userId", type="OneToOne", entity="vManager\Security\User", joinOn="user=id")
 * @Column(entity, type="string")
 * @Column(entityId, type="integer")
 * @Column(timestamp, type="DateTime")
 */
class Star extends Orm\ActiveEntity {	
	
	/**
	 * @var Orm\ActiveEntity
	 */
	protected $_entityInstance;
	

	public function __construct($data = array()) {
		call_user_func_array(array('parent', '__construct'), func_get_args());

		$this->onPreSave[] = callback($this, '_onPreSave');
	}
	
	/**
	 * Returns the entity instance
	 * @return Orm\ActiveEntity
	 */
	public function getEntity() {
		if (!isset($this->_entityInstance)) {
			if (!$this->getEntityId()) {
				throw new Nette\InvalidStateException("");
			}
			//$entityName = parent::getEntity();
			$entityName = $this->defaultGetter('entity'); // Because of PHP<5.3.4
			$this->_entityInstance = $this->createEntityInstance($entityName);
		}
		return $this->_entityInstance;
	}
	
	/**
	 * Sets the emtity that represents the starred object
	 * @param mixed $entity full class name or entity instance
	 */
	public function setEntity($entity) {
		if ($entity instanceof Orm\ActiveEntity) {
			$this->_entityInstance = $entity;
			list($id) = $entity->getMetadata()->getIdFields();
			$this->setEntityId($entity->$id);
			$entity = get_class($entity);
		} elseif (is_string($entity)) {
			if (!class_exists($entity)) {
				throw new Nette\InvalidArgumentException("Class '$entity' does not exist!");
			}
		} else {
			throw new Nette\InvalidArgumentException('You have to supply either an entity name or its instance!');
		}
		//parent::setEntity($entity);
		$this->defaultSetter('entity', $entity); // Because of PHP<5.3.4
	}
	
	/**
	 * Private helper for creating entities
	 * @param string $entityName
	 * @return Orm\ActiveEntity 
	 */
	protected function createEntityInstance($entityName) {
		if (is_subclass_of($entityName, '\vBuilder\Orm\ActiveEntity')) {	
			return $this->context->repository->create($entityName);
		}
		throw new Nette\InvalidArgumentException("An entity must be a subclass of vBuilder\Orm\ActiveEntity!");
	}
	
	/**
	 * Callback handler onPreSave
	 * @param Star $star 
	 */
	public function _onPreSave(Star $star) {
		list($entity, $id) = array ($this->defaultGetter('entity'), $this->defaultGetter('entityId')); // :-)
		if (!isset($entity) || !isset($id)) {
			throw new Nette\InvalidStateException("Either an instance of an entity or both its id and class must be provided. See ".__FILE__." for more information");
		}
	}
}