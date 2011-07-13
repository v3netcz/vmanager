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
namespace vManager\Modules\Wiki\Controls;


use dibi;
/**
 * 
 *
 * @author Jirka Vebr
 */
class TreeCommentsModel extends \Nette\ComponentModel\Container
{
	/** @var array */
	private $conditions = array ();
	
	/** @var object TreeTraversalDbHelper */
	private $treeHelper;
	
	/** @var string TableName*/
	private $table;
	
	/* Fields */
	/** @var string Field Id*/
	private $fieldId = 'id';
	/** @var string Field Lft*/
	private $fieldLft = 'lft';
	/** @var string Field Rgt*/
	private $fieldRgt = 'rgt';
	/** @var string Field Level*/
	private $fieldLevel = 'level';
	/** @var string Field associated Id */
	private $fieldAssociatedId = 'associated_id';
	
	private $associatedId;
	
	
	public function __construct($parent = null, $name = null) {
		parent::__construct($parent, $name);
		$this->treeHelper = new \vBuilder\Utils\TreeTraversalDbHelper;
		$this->getTreeHelper()->setFieldLft($this->getFieldLft());
		$this->getTreeHelper()->setFieldRgt($this->getFieldRgt());
		$this->getTreeHelper()->setFieldLevel($this->getFieldLevel());
	}
	
	public function addCondition() {
		$args = \func_get_args();
		$this->conditions += $args;
		return $this;
	}
	
	private function getConditions() {
		return $this->conditions;
	}
	
	/**
	 * @return TreeTraversalDbHelper
	 */
	private function getTreeHelper() {
		return $this->treeHelper;
	}
	
	public function addReaction($data) {
		$parentId = $data['parentId'] ?: null;
		unset($data['parentId']);
		$data = (array) $data;
		$data += array (
			$this->getFieldAssociatedId() => $this->getAssociatedId(),
			'date' => time(),
			'user_id' => $this->getParent()->getPresenter()->getUser()->getIdentity()->id
		);
		return $this->getTreeHelper()->addRecord($data, $parentId);
	}
	
	public function getTreeData() {
		$result = dibi::select('*')
						->from($this->getTable());
		$cond = $this->getConditions()+array('['.$this->getFieldAssociatedId().'] = %i',$this->getAssociatedId());
		if (!empty($cond)) {
			$result = \call_user_func_array(array ($result, 'where'), $cond);
		}
		$result = $result->orderBy('['.$this->getFieldLft().']');
		
		return $result->fetchAll();
	}
	
	public function getSubjectById($id) {
		return dibi::select('[subject]')
					->from('['.$this->getTable().']')
					->where('['.$this->getFieldId().'] = %i',$id)
					->fetchSingle();
	}
	
	
	
	
	public function getFieldId() {
		return $this->fieldId;
	}
	public function setFiedId($value) {
		$this->fieldId = (string) $value;
		return $this;
	}
	
	public function getFieldLft() {
		return $this->fieldLft;
	}
	public function setFieldLft($value) {
		$this->fieldLft = (string) $value;
		$this->getTreeHelper()->setFieldLft($value);
		return $this;
	}
	
	public function getFieldRgt() {
		return $this->fieldRgt;
	}
	public function setFieldRgt($value) {
		$this->fieldRgt = (string) $value;
		$this->getTreeHelper()->setFieldRgt($value);
		return $this;
	}
	
	public function getFieldLevel() {
		return $this->fieldLevel;
	}
	public function setFieldLevel($value) {
		$this->fieldLevel = (string) $value;
		$this->getTreeHelper()->setFieldLevel($value);
		return $this;
	}
	
	public function getFieldAssociatedId() {
		return $this->fieldAssociatedId;
	}
	public function setFieldAssociatedId($value) {
		$this->fieldAssociatedId = (string) $value;
		return $this;
	}
	
	public function setAssociatedId($id) {
		$this->associatedId = (int) $id;
		return $this;
	}
	public function getAssociatedId() {
		return $this->associatedId;
	}
	
	public function setTable($value) {
		$this->table = (string) $value;
		$this->getTreeHelper()->setTable($value);
		return $this;
	}
	
	public function getTable() {
		return $this->table;
	}
}