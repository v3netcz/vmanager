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

use vManager, vBuilder, Nette, vManager\Form, Gridito;

/**
 * Presenter for listing of expenses
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 31, 2012
 */
class ExpensePresenter extends vManager\Modules\System\SecuredPresenter {
	
	const ENTITY_EXPENSE = 'vManager\Modules\Accounting\Expense';
	
	/** @persistent */
	public $month;
	
	private $_months;
	
	public function actionDefault() {
		$this->setupParams();
	}
	
	protected function createComponentExpenseGrid($name) {
		$grid = new vManager\Grid($this, $name);
		$grid->setTemplateFile(__DIR__ . '/../Templates/Gridito/expenseGrid.latte');
		
		$model = new vManager\Grid\OrmModel($this->context->repository->findAll(self::ENTITY_EXPENSE));

		$grid->setModel($model);
		$grid->setItemsPerPage(10);
		$grid->sortColumn = 'date';
		$grid->sortType = 'desc';

		// columns
		//$grid->addColumn("id", "ID")->setSortable(true);
		
		$grid->addColumn("date", __("Date"), array(
			 "renderer" => function ($row) {
				 echo $row->date->format('j. n.');
			 },
			 "sortable" => true,
		))->setCellClass('day');
		
		$grid->addColumn('description', __('Expense description'));
		
		$grid->addColumn("supplier", __("Supplier"), array(
			 "renderer" => function ($row) {
				 echo Nette\Utils\Html::el("a")
				 			->href("#")
				 			->setText($row->supplier ? $row->supplier->name : '???');
			 },
			 "sortable" => true,
		));
		
		$grid->addColumn('supplierEvidenceId', __('Supplier evidence'))->setCellClass('evidenceId');
		
		$grid->addColumn("dueDate", __("Due date"), array(
			 "renderer" => function ($row) {
				if($row->date->format('Y') == $row->dueDate->format('Y'))
					echo $row->dueDate->format('j. n.');
				else
					echo $row->dueDate->format('j. n. Y');
			 },
			 "sortable" => true,
		))->setCellClass('day dueDate');
		
		$grid->addColumn("cost", __("Cost"), array(
			 "renderer" => function ($row) {
				 echo vBuilder\Latte\Helpers\FormatHelpers::currency($row->cost);
			 },
			 "sortable" => true,
		))->setCellClass('cost');
		
		$grid->addButton("btnRemove", __('Remove'), array(
			"class" => "button_orange",
			"confirmationQuestion" => function ($row) use ($grid) {
				return __('Are you sure you want to remove this expense?');
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

	}
	
	public function createComponentExpenseFilterForm($name) {
		$form = new Form($this, $name);
		
		$months = array();
		foreach($this->getMonths() as $m)
			$months[$m] = vManager\Application\Helpers::monthInWords($m);
		
		$form->addSelect('month', __('Month:'), $months);
		$form->addSubmit('send', __('Filter'));
		
		$presenter = $this;
		$form->onSuccess[] = function () use ($presenter, $form) {
			$presenter->redirect("default", (array) $form->getValues());
		}; 
		
		return $form;
	}
	
	protected function getMonths() {
		if(!isset($this->_months)) {
			$this->_months = array();
			
			$d = $this->context->connection->query(
				"SELECT DISTINCT DATE_FORMAT(`date`, '%Y-%m') AS `m` FROM [".Expense::getMetadata()->getTableName()."]"
			)->fetchAll();
			
			foreach($d as $curr) $this->_months[] = $curr->m;
		}
		
		return $this->_months + array(date('Y-m'));
	}
	
	protected function setupParams() {
		$this->month = $this->getParam('month');
		if(!isset($this->month)) $this->month = date('Y-m');
	}
	
	public function getSince() {
		return \DateTime::createFromFormat('Y-m-d', $this->month . '-01');
	}
	
	public function getUntil() {
		$d = clone $this->getSince();
		return $d->add(\DateInterval::createFromDateString(($d->format('t') - 1) . ' days'));
	}
	
	public function createComponentNewExpenseForm($name) {
		$now = new \DateTime('now');
		$firstDay = $this->getSince();
		$context = $this->context;
		
		$form = new Form($this, $name);
		
		$form->addText('supplier', __('Supplier:'))->setRequired()
				->setAttribute('autocomplete-src', $this->link('Subject:suggestSubject'))
			  	->setAttribute('title', __('From whom did you get the receipt?'))
			  	->addRule(function ($control) use ($context) {
					return $context->repository->findAll(SubjectPresenter::ENTITY_SUBJECT)
						->where('[name] = %s', $control->value)->fetch() !== false;
					
				}, __('Supplier does not exist in subject database. Please add him first.'));
			  	
		$form->addText('supplierEvidenceId', __('Supplier evidence ID:'))->setRequired();
		
		$form->addDatePicker('date', __('Date'));
		if($now->format('Y-m') == $firstDay->format('Y-m'))				  
			$form['date']->setDefaultValue($now->format("d.m.Y"));
		else
			$form['date']->setDefaultValue($firstDay->format("d.m.Y"));	
		
		$dd = clone $form['date']->getValue();
		$form->addDatePicker('dueDate', __('Due date'))->setDefaultValue($dd->add(\DateInterval::createFromDateString('14 days')));
		
		$form->addText('description', __('Description:'))->setRequired();
		$form->addText('cost', __('Cost:'))->setRequired()
			->addRule(Form::FLOAT, __('Cost has to be a number'));
				
		$form->addSubmit('s', __('Add expense'));
		
		$form->onSuccess[] = callback($this, 'processNewExpenseForm');
		
		return $form;
	}

	public function processNewExpenseForm($form) {
		$values = $form->getValues();
		
		$expense = $this->context->repository->create(self::ENTITY_EXPENSE);
		
		$supplier = $this->context->repository->findAll(SubjectPresenter::ENTITY_SUBJECT)
						->where('[name] = %s', $values->supplier)->fetch();
						
		if($supplier === false) throw new Nette\InvalidStateException('Supplier does not exist');
		
		$expense->date = $values->date;
		$expense->dueDate = $values->dueDate;
		$expense->description = $values->description;
		$expense->cost = $values->cost;
		$expense->supplierEvidenceId = $values->supplierEvidenceId;
		$expense->supplier = $supplier;
		
		$expense->save();
		
		$this->flashMessage(__('Expense has been successfully recorded.'));	
		$this->redirect('this');
	}	
	
}
