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

namespace vManager\Modules\Accounting;

use vManager, vBuilder, Nette, vManager\Form, Gridito, Nette\Utils\Strings;

/**
 * Presenter for records of accounting book
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 6, 2012
 */
class RecordPresenter extends vManager\Modules\System\SecuredPresenter {
	
	const ENTITY_RECORD = 'vManager\Modules\Accounting\Record';
	const ENTITY_BILLING_CLASS = 'vManager\Modules\Accounting\BillingClass';

	/** @persistent */
	public $editId;

	private $_billingClasses;

	function getSince() {
		return \DateTime::createFromFormat('Y-m-d', '2011-01-01');
	}
	
	function getUntil() {
		return \DateTime::createFromFormat('Y-m-d', '2013-01-01');
	}
	
	protected function getDataSource() {
		$ds = $this->context->repository->findAll(self::ENTITY_RECORD)
				->where('[date] >= %s', $this->getSince()->format('Y-m-d'))
				->and('[date] <= %s', $this->getUntil()->format('Y-m-d 23:59:59'));
				
		if($this->getDPrefix() !== null)
			$ds->and('[d] LIKE %like~', $this->getDPrefix());
		
		if($this->getMdPrefix() !== null)
			$ds->and('[md] LIKE %like~', $this->getMdPrefix());
			
			
		return $ds;
	}
	
	protected function createComponentGeneralRecordGrid($name) {
		$presenter = $this;
		$grid = new vManager\Grid($this, $name);
		//$grid->setTemplateFile(__DIR__ . '/../Templates/Gridito/expenseGrid.latte');

		$model = new vManager\Grid\OrmModel($this->getDataSource());

		$grid->setModel($model);
		$grid->setItemsPerPage(20);
		$grid->sortColumn = $grid->sortColumn ?: 'date';
		$grid->sortType = $grid->sortType ?: 'asc';
		
		$grid->setRowClass(function ($iterator, $row) use ($presenter) {
			$classes = array();
			
			if(Strings::match($row->evidenceId, '/^20[0-9]{5}$/'))
				$classes[] = 'invoice';
			elseif(Strings::match($row->evidenceId, '/^[0-9]{2}BP[0-9]{3}$/'))
				$classes[] = 'bankIncome';
			elseif(Strings::match($row->evidenceId, '/^[0-9]{2}BV[0-9]{3}$/'))
				$classes[] = 'bankExpense';
			elseif(Strings::match($row->evidenceId, '/^[0-9]{2}PP[0-9]{3}$/'))
				$classes[] = 'cashIncome';
			elseif(Strings::match($row->evidenceId, '/^[0-9]{2}PV[0-9]{3}$/'))
				$classes[] = 'cashExpense';

			return empty($classes) ? null : implode(" ", $classes);
		});
		
		$grid->addColumn("date", __("Date"), array(
			 "renderer" => function ($row) {
				 echo $row->date->format('j. n. \'y');
			 },
			 "sortable" => true,
		))->setCellClass('day');
		
		$grid->addColumn("evidenceId", __("Evidence ID"), array(
			 "renderer" => function ($row) {
				 echo Helpers::evidenceId($row->evidenceId);
			 },
			 "sortable" => true,
		))->setCellClass('evidenceId myEvidenceId');
		
		$grid->addColumn('description', __('Subject'))->setCellClass('subject');
				
		$grid->addColumn("value", __("Value"), array(
			 "renderer" => function ($row) {
				 echo vBuilder\Latte\Helpers\FormatHelpers::currency($row->value);
			 },
			 "sortable" => true,
		))->setCellClass('value');

		
		$grid->addColumn("md", __("MD"), array(
			 "renderer" => function ($row) {
			 	 echo Helpers::billingClass($row->md);
			 },
			 "sortable" => true,
		))->setCellClass('billingClass');
		
		$grid->addColumn("d", __("D"), array(
			 "renderer" => function ($row) {
			 	 echo Helpers::billingClass($row->d);
			 },
			 "sortable" => true,
		))->setCellClass('billingClass');
		
		// -------
		
		$grid->addButton("btnEdit", __('Edit'), array(					  
			"handler" => function ($row) use ($grid, $presenter) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					$presenter->redirect("this", array('editId' => $row->id));
					return ;
				}

				$grid->redirect("this");
			}
		));
		
		$grid->addButton("btnRemove", __('Remove'), array(
			"class" => "button_orange",
			"confirmationQuestion" => function ($row) use ($grid) {
				return __('Are you sure you want to remove this record?');
			},
					  
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					$row->delete();
					Nette\Environment::getApplication()->getPresenter()->flashMessage(__("Record has been removed."));
				}

				$grid->redirect("this");
			}
		));
		
		return $grid;

	}
	
	public function createComponentRecordForm($name) {		
		$form = new Form($this, $name);
		
		$this->setupRecordForm($form);
		$this->loadRecordForm($form);
		
		return $form;
	}
	
	public function recordFormSubmitted($form) {
			
		if($this->getParam('editId') !== null) {
			$record = $this->context->repository->findAll(self::ENTITY_RECORD)->where('[id] = %i', $this->getParam('editId'))->fetch();
			if(!$record) {
				$this->flashMessage(__('Record not found'), 'warn');	
				$this->redirect('this', array('editId' => null));
			}
		} else {
			$record = $this->context->repository->create(self::ENTITY_RECORD);
		}
		
		$this->saveRecordForm($form, $record);
		$record->save();
		
		$this->flashMessage($this->getParam('editId') !== null ? __('Record has been saved.') : __('Record has been created.'));	
		$this->redirect('this', array('editId' => null));
	}
	
		
	public function recordFormCanceled() {
		$this->redirect('this', array('editId' => null));
	}
	
	protected function saveRecordForm(Form $form, vBuilder\Orm\ActiveEntity $record) {
		$form->fillInEntity($record);
	}
	
	protected function loadRecordForm(Form $form) {
		if($this->getParam('editId') !== null) {
			$record = $this->context->repository->findAll(self::ENTITY_RECORD)->where('[id] = %i', $this->getParam('editId'))->fetch();
			
			if(!$record) {
				$this->flashMessage(__('Record not found'), 'warn');	
				$this->redirect('this', array('editId' => null));
			}
						
			$form->loadFromEntity($record);
			
			if($record->d && $record->d->id != '') $form['d']->setDefaultValue($record->d->id);
			if($record->md && $record->md->id != '') $form['md']->setDefaultValue($record->md->id);
			
			return $record;
		}
	}
	
	protected function setupRecordForm(Form $form) {
		$now = new \DateTime('now');
		$form->addDatePicker('date', __('Date'));
		//	->setAttribute('title', __('When did you get the reciept?'));
			
		if($now <= $this->getUntil())				  
			$form['date']->setDefaultValue($now->format("d.m.Y"));
		else
			$form['date']->setDefaultValue($this->getSince()->format("d.m.Y"));	
		
		$form->addText('evidenceId', __('Evidence ID:'));
		
		$form->addText('description', __('Subject:'))->setRequired(__('Subject can\'t be empty.'));
		$form->addText('value', __('Value:'))->setRequired(__('Value can\'t be empty.'))
			->addRule(Form::FLOAT, __('Value has to be a number'));
		
		$this->addBillingClassFormField($form, 'd', 'Dal', $this->getDPrefix());
		$this->addBillingClassFormField($form, 'md', 'Má dáti', $this->getMdPrefix());
		
		$form->addSubmit('c', __('Cancel'))
			->setValidationScope(false)
			->onClick[] = callback($this, 'recordFormCanceled');
			
		$form->addSubmit('s', __('Save record'));
		
		$form->onSuccess[] = callback($this, 'recordFormSubmitted'); 
	}

	
	protected function getBillingClasses() {
		if(!isset($this->_billingClasses)) {
			$data = $this->context->repository->findAll(self::ENTITY_BILLING_CLASS)
						->orderBy('id')->fetchAll();
			
			$this->_billingClasses = array();
			foreach($data as $curr) $this->_billingClasses[$curr->id] = $curr->id . ' - ' . $curr->name;
		}
		
		return $this->_billingClasses;
	}
	
	protected function addBillingClassFormField(Form $form, $name, $label, $prefix) {
		$values = $this->getBillingClasses();
	
		if($prefix !== null) {
			$values = array_filter($values, function ($value) use (&$values, $prefix) {
				$id = key($values);							
				next($values);
				
				if(Strings::endsWith($id, '0')) return false;
				
				return Strings::startsWith($id, $prefix);
			});
		}
	
		$select = $form->addSelect($name, $label, $values)->setPrompt('-');
		if(count($values) == 1) {
			reset($values);
			$select->setDefaultValue(key($values));
		}
	}
	
	protected function getDPrefix() {
		return null;
	}
	
	protected function getMdPrefix() {
		return null;
	}
	
}
