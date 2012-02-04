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
 * Presenter for listing and managing employees
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 28, 2012
 */
class EmployeePresenter extends vManager\Modules\System\SecuredPresenter {
	
	const ENTITY_EMPLOYEE = 'vManager\Modules\Accounting\Employee';
	const ENTITY_WORKHOUR = 'vManager\Modules\Accounting\WorkHour';
	
	/** @var vManager\Modules\Accounting\Employee */
	protected $employee;
	
	/** @persistent */
	public $id;
	
	/** @persistent */
	public $month;
	
	protected $_hours;
	
	protected function setupParams($id) {
		$this->employee = $this->context->repository->get(self::ENTITY_EMPLOYEE, $id);
		if(!$this->employee->exists()) throw new \InvalidArgumentException('Employee not found');
		
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
	
	public function getHours() {
		if(!isset($this->_hours)) {
			$this->_hours = $this->context->repository->findAll(self::ENTITY_WORKHOUR)
				->where('[employeeId] = %s', $this->employee->id)
				->and('DATE_FORMAT([date], \'%Y-%m\') = %s', $this->month);
		}
		
		return $this->_hours;
	}
	
	// <editor-fold defaultstate="collapsed" desc="Employee listing (default)">
	
	protected function createComponentEmployeeGrid($name) {
		$grid = new vManager\Grid($this, $name);

		$model = new vManager\Grid\OrmModel($this->context->repository->findAll(self::ENTITY_EMPLOYEE));

		$grid->setModel($model);
		$grid->setItemsPerPage(10);

		// columns
		//$grid->addColumn("id", "ID")->setSortable(true);
		$grid->addColumn("name", __("Name"))->setSortable(true);
		$grid->addColumn("surname", __("Surname"))->setSortable(true);
		$grid->addColumn("in", __("National Identification Number"))->setSortable(true);
		$grid->addColumn("email", __("E-mail"), array(
			 "renderer" => function ($row) {
				 echo Nette\Utils\Html::el("a")->href("mailto:$row->email")->setText($row->email);
			 },
			 "sortable" => true,
		));


		$grid->addButton("btnShow", __('View employee card'), array(					  
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					Nette\Environment::getApplication()->getPresenter()->redirect('Employee:detail', $row->id);
				}

				$grid->redirect("this");
			}
		));
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Employee card (detail)">
	
	public function actionDetail($id) {
		$this->setupParams($id);		
	}
	
	public function renderDetail() {
		$this->template->employee = $this->employee;
		
		$this->template->sum = 0;
		foreach($this->getHours() as $curr) $this->template->sum += $curr->hours;
		
		$this->template->payPerHour = $this->employee->getCurrentPay($this->getSince());
		$this->template->pay = $this->template->payPerHour * $this->template->sum;
	}
	
	protected function createComponentWorkHoursGrid($name) {
		$grid = new vManager\Grid($this, $name);
		$grid->setTemplateFile(__DIR__ . '/../Templates/Gridito/workHoursGrid.latte');

		$ds = $this->getHours();
		$model = new vManager\Grid\OrmModel($ds);

		$grid->setModel($model);
		$grid->setItemsPerPage(31);
		$grid->sortColumn = $grid->sortColumn ?: 'date';
		$grid->sortType = $grid->sortType ?: 'desc';

		// columns		
		$grid->addColumn("date", __("Date"), array(
			 "renderer" => function ($row) {
				 echo $row->date
				 		? ( $row->date->format('Y') == date('Y') ? $row->date->format('d. n.') : $row->date->format('j. n. Y') )
				 		: '-';
			 },
			 "sortable" => true,
		));
		
		$grid->addColumn("minutes", __("# Hours"), array(
			 "renderer" => function ($row) {
				 echo $row->hours . ' h';
			 },
			 "sortable" => true,
		));

		$grid->addColumn("description", __("Job description"))->setSortable(true);

	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Report">
	
	public function actionReport($id) {
		$this->setupParams($id);
		
		$renderer = new vManager\Reporting\PdfRenderer($this->context);
		$renderer->setTemplateFile(__DIR__ . '/../Templates/Reports/employeeWorksheet.latte');
		
		$renderer->template->since = $this->getSince();
		$renderer->template->until = $this->getUntil();		
		$renderer->template->employee = $this->employee;
		
		$renderer->template->workHours = $this->getHours()->orderBy('[date]');
				
		$renderer->template->sum = 0;
		foreach($renderer->template->workHours as $curr) {
			$renderer->template->sum += $curr->hours;
		}
		
		$renderer->template->sum = ceil($renderer->template->sum);
		$renderer->template->payPerHour = $this->employee->getCurrentPay($renderer->template->since);
		
		$renderer->template->pay = $renderer->template->sum * $renderer->template->payPerHour;
		$renderer->template->tax = $renderer->template->pay * 0.15;
		
		$renderer->render();
		exit;
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Add work hours form">
	
	public function createComponentAddWorkHoursForm($name) {
		$now = new \DateTime('now');
		$firstDay = $this->getSince();
		$context = $this->context;
		
		$form = new Form;
		$form->addDatePicker('date', __('Date:'))
			->addRule(Form::FILLED)
			->addRule(function ($control) use ($context) {
				return $context->repository->findAll(EmployeePresenter::ENTITY_WORKHOUR)
					->where('[date] = %s', $control->value->format('Y-m-d'))->fetch() == false;
					
			}, __('Work hours for this date have already been set'));
				  
		if($now->format('Y-m') == $firstDay->format('Y-m'))				  
			$form['date']->setDefaultValue($now->format("d.m.Y"));
		else
			$form['date']->setDefaultValue($firstDay->format("d.m.Y"));	
				  
		$form->addText('hours', __('Hours:'))
			->addRule(function ($control) {
				return ($control->value * 60) % 15 == 0 && $control->value >= 0.25;
			}, __('Work hours have to be decimal number. Minimum step is 0.25 hour (15 minutes).'));
							 
		$form->addText('description', __('Job description:'))->setRequired(__('Job description is mandatory'));
		
		$form->addSubmit('save', __('Report work hours'));

		$form->onSuccess[] = callback($this, 'processWorkHoursForm');

		return $form;
	}
	
	public function processWorkHoursForm($form) {
		$this->flashMessage(__('Work hours has been reported'));
		$values = $form->getValues();

		$e = $this->context->repository->create(EmployeePresenter::ENTITY_WORKHOUR);
		$e->minutes = $values->hours * 60;
		$e->date = $values->date; 
		$e->description = $values->description;
		$e->employeeId = $this->employee->id;
		$e->save();
		
		$this->redirect('this');
	}
	
	// </editor-fold>
	
}
