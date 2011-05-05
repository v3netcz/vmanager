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

use vManager, vBuilder, Nette, vManager\Form;

/**
 * Presenter for listing and viewing invoices
 *
 * @author Adam Staněk (V3lbloud)
 * @since May 5, 2011
 */
class InvoicePresenter extends vManager\Modules\System\SecuredPresenter {
	
	/** @var array of invoices */
	private $invoices;
	
	protected function getInvoices() {
		if($this->invoices !== null) return $this->invoices;
		
		$ic = $this->getParam('ic');
		$year = $this->getYear();		
				
		$this->invoices = InvoiceManager::getInvoices($ic === null ? $year : null, $ic);
		$this->invoices = array_reverse(InvoiceManager::getOrderedInvoices($this->invoices));
		
		return $this->invoices;
	}
	
	protected function getYear() {
		$year = $this->getParam('year');
		
		if($year === null) {
			$now = new \DateTime('now');
			$year = (int) $now->format("Y");
		}
		
		return $year;
	}
	
	public function renderDefault() {
		$this->template->registerHelper('currency', 'vManager\Modules\Accounting\Helpers::currency');
		
		if($this->getParam('ic') === null) $this->template->years = InvoiceManager::getYears();
		
		$this->template->total = 0.0;
		$this->template->totalPaid = 0.0;
		$this->template->year = $this->getYear();
		$this->template->paidThisYear = InvoiceManager::getPaidInYear($this->getYear());
		
		foreach($this->getInvoices() as $curr) {
			if(!$curr->isCanceled())
				$this->template->total += $curr->getTotal();
			
			if($curr->isPaid())
				$this->template->totalPaid += $curr->getTotal();
			
			$paymentDate = $curr->getPaymentDate();
			
			if(!isset($this->template->lastPaymentDate) || $this->template->lastPaymentDate < $paymentDate)
				$this->template->lastPaymentDate = $paymentDate;
		}
		
		$this->template->totalExpecting = $this->template->total - $this->template->totalPaid; 
	}
	
	protected function createComponentInvoiceGrid($name) {
		// Musim zkonvertovat faktury pro model
		// TODO: udelat to nejak inteligentnejs
		$data = array();
		foreach($this->getInvoices() as $curr) {
			$data[] = array(
				 'id' => $curr->getId(),
				 'formatedId' => $curr->getFormatedId(),
				 'customer' => $curr->getCustomerName(),
				 'customerId' => $curr->getCustomerId(),
				 'date' => $curr->getDate()->format("d. m. Y"),
				 'deadline' => $curr->getDeadline()->format("d. m. Y"),
				 'total' => str_replace(" ", "\xc2\xa0", number_format($curr->getTotal(), 0, "", " ")) . "\xc2\xa0Kč",
				 
				 'canceled' => $curr->isCanceled(),
				 'paid' => $curr->isPaid(),
				 'overdue' => $curr->isOverdue()
			);
		}
		
		
		$grid = new vManager\Grid($this, $name);		
		$grid->setModel(new vManager\Grid\ArrayModel($data));
		
		$grid->setRowClass(function ($iterator, $row) {
			$classes = array();
			
			if($row->paid) $classes[] = 'paidInvoice';
			elseif($row->canceled) $classes[] = 'canceledInvoice';
			elseif($row->overdue) $classes[] = 'overdueInvoice';

			return empty($classes) ? null : implode(" ", $classes);
		});
		
		$grid->addColumn("formatedId", __('ID'))->setCellClass("id");
		$grid->addColumn("customer", __('Customer'), array(
			 "renderer" => function ($row) {
				 $link = Nette\Environment::getApplication()->getPresenter()->link('this', array('ic' => $row->customerId));
				 echo Nette\Utils\Html::el("a")->href($link)->setText($row->customer);
			 },
		))->setCellClass("customer");
				  
		$grid->addColumn("date", __('Date of issuance'))->setCellClass("issuance");
		$grid->addColumn("deadline", __('Due date'))->setCellClass("deadline");
		$grid->addColumn("total", __('Value'))->setCellClass("price");
		
		$grid->addButton("btnCancel", __('Cancel'), array(
			//"icon" => "icon-tick",
			"class" => "button_orange",
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					InvoiceManager::cancelInvoice($row->id);
					Nette\Environment::getApplication()->getPresenter()->flashMessage(_x("Invoice %s has been marked as canceled.", array($row->formatedId)));
				}
				
				$grid->redirect("this");
			}
		));
		
		$grid->addButton("btnPay", __('Pay'), array(
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					Nette\Environment::getApplication()->getPresenter()->redirect('pay', $row->id);
				}
			} 
		));
	}
	
	// Payment form -------------------------------------------------------------
	
	public function renderPay($id) {
		
	}
	
	public function createComponentPayForm() {
		$now = new \DateTime('now');
		
		$form = new Form;
		$form->addDatePicker('date')
				  ->addRule(Form::FILLED)
				  ->setDefaultValue($now->format("d.m.Y"));
		
		/*$form->addText('date', __('Date of payment recieve:'), 60, 100)
				  ->addRule(Form::FILLED)
				  ->addRule(Form::REGEXP, __('Date has to be in format of (d)d.(m)m.yyyy'),  '/^[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}$/')
				  ->setDefaultValue($now->format("d.m.Y")); */
		
		$form->addSubmit('save', __('Save'));
		//$form->addSubmit('back', 'Zpět')->setValidationScope(NULL);

		$form->onSubmit[] = callback($this, 'processPayForm');

		return $form;
	}
	
	public function processPayForm($form) {
		if($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			
			$this->flashMessage(__('Payment has been recorded.'));
			$values = $form->getValues();

			InvoiceManager::payInvoice($id, new \DateTime($values["date"]));
		}
		
		$this->redirect('Invoice:default');
	}
	
}
