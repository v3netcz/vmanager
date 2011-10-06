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

use vManager, vBuilder, Nette, vManager\Form, vStore;

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
			"class" => "button_orange btnCancel",
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
			"class" => "btnPay",
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					Nette\Environment::getApplication()->getPresenter()->redirect('pay', $row->id);
				}
			} 
		));
		
		$grid->addButton("btnNewTpl", __('Use as template'), array(
			"class" => "btnNewTpl",
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					Nette\Environment::getApplication()->getPresenter()->redirect('newInvoice', array('fromInvoice' => $row->id));
				}
			} 
		));
		
		$grid->addButton("btnEmail", __('E-mail'), array(
			"class" => "btnEmail",
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					Nette\Environment::getApplication()->getPresenter()->redirect('email', $row->id);
				}
			} 
		));
		
		$presenter = $this;
		$grid->addButton("btnView", __('View'), array(
			"class" => "button_black btnView",
			"handler" => function ($row) use ($grid, $presenter) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					$mask = $presenter->getFilenamePrefix($row->id) . "*.pdf";
					
					foreach(Nette\Utils\Finder::findFiles($mask)->from(InvoicePresenter::getInvoiceDirPath()) as $curr) {
						 $filepath = '/invoices/' . substr($curr->getRealPath(), strlen(InvoicePresenter::getInvoiceDirPath()) + 1);
						 
						 Nette\Environment::getApplication()->getPresenter()->redirect(':System:Files:default', array(
								 vManager\Modules\System\FilesPresenter::PARAM_NAME => $filepath
						 ));
						return ;
					}
					
					Nette\Environment::getApplication()->getPresenter()->flashMessage(__('File not found'), 'warn');
				}
			} 
		));
		
		
		
		
	}
	
	// E-mail -------------------------------------------------------------------
	
	public function renderEmail($id) {
	
	}
	
	public function createComponentEmailForm() {
		$form = new Form;
		$id = (int) $this->getParam('id');
		
		if($id) {
			$mask = $this->getFilenamePrefix($id) . "*.xml";
			foreach(Nette\Utils\Finder::findFiles($mask)->from(InvoicePresenter::getInvoiceDirPath()) as $curr) {
				$invoiceFile = $curr->getRealPath(); break;
			}
			
			$invoice = vStore\Invoicing\XmlInvoice::fromFile($invoiceFile);
			$invoiceFile = substr($invoiceFile, 0, -3) . 'pdf';
		}

		if(!$invoice || !file_exists($invoiceFile)) {
			$this->flashMessage(__('Missing ID'), 'warn');
			$this->redirect('Invoice:default');
		}

		$form->addText('recipient', __('Recipient'));
		$form->addText('subject', __('Subject'))->setDefaultValue('Faktura c. ' . $invoice->id);
		
		$tpl = vManager\Mailer::createMailTemplate(__DIR__ . '/../Templates/E-mails/default.latte');
		$tpl->invoice = $invoice;
		
		$form->addTextArea('text')->setDefaultValue((String) $tpl);
		$form->addSubmit('save', __('Save'));

		$presenter = $this;
		$form->onSuccess[] = function ($form) use($invoiceFile, $presenter) {
		
			// Kvuli jmenu (dirty fix)
			$invoiceFile2 = TEMP_DIR . '/' . str_replace('.', '_', substr(basename($invoiceFile), 0, strlen('V3Net.cz_0000_0000'))) . '.pdf';
			copy($invoiceFile, $invoiceFile2);
		
			$values = $form->getValues();
			$msg = new Nette\Mail\Message;
			$msg->setFrom('info@v3net.cz', 'V3Net.cz');
			$msg->addTo($values->recipient);
			$msg->setSubject($values->subject);
			$msg->setHtmlBody(nl2br($values->text));
			$msg->addAttachment($invoiceFile2, null, 'application/pdf');
			
			vManager\Mailer::getMailer()->send($msg);
			unlink($invoiceFile2);
			
			$presenter->flashMessage(_x('Message has been sent. Recipient: %s', array($values->recipient)));	
			$presenter->redirect('Invoice:default');
		};

		return $form;
	}
	
	// New invoice --------------------------------------------------------------
	
	public function renderNewInvoice() {
		$this->template->nextId = InvoiceManager::getNextId();
	}
	
	public function createComponentNewInvoiceForm() {
		$form = new Form;
		
		$form->addCheckbox('replace', __('Replace?'))->controlPrototype->class('hidden');
		$form['replace']->labelPrototype->class('hidden');
		
		$form->addTextArea('xml');
		
		if(isset($this->params['fromInvoice'])) {
			$mask = $this->getFilenamePrefix($this->params['fromInvoice']) . "*.xml";
			foreach(Nette\Utils\Finder::findFiles($mask)->from(InvoicePresenter::getInvoiceDirPath()) as $curr) {
				$form['xml']->setDefaultValue(file_get_contents($curr->getRealPath()));

				break;
			}
		}
		
		$form->addSubmit('save', __('Save'));

		$form->onSuccess[] = callback($this, 'processNewInvoiceForm');

		return $form;
	}
	
	public function getFilenamePrefix($invoiceId) {
		if(strlen($invoiceId) == 8) {
			$invoiceId = substr($invoiceId, 0, 4) . '/' . substr($invoiceId, 4);
		}
	
		$id = trim(preg_replace('/[^0-9]+/', '-', $invoiceId), '-');
		return "V3Net.cz_${id}_";
	}
	
	public function processNewInvoiceForm($form) {
		$values = $form->getValues();
    
		try {
			libxml_use_internal_errors(true);
			$invoice = vStore\Invoicing\XmlInvoice::fromString($values->xml);
		} catch(\Exception $e) {
			$this->flashMessage(__('Malformed XML data.'), 'warn');
			
			foreach(libxml_get_errors() as $curr) {
				$this->flashMessage($curr->message . 'on line ' . $curr->line, 'warn');
			}
			
			return ;
		}
                
		$prefix = $this->getFilenamePrefix($invoice->id);
		
		$filename = iconv('UTF-8', 'ASCII//TRANSLIT', $invoice->customer->invoiceAddress->name);
    $filename = preg_replace('/ s\\.? *r\\. *o\\.?| spol\\.|[^A-Za-z0-9 \\.:\\-]/', '', $filename);
    $filename = trim(preg_replace('/[\\s\\-\\.:]+/', '_', $filename), '_');
    $filename = $prefix . $filename;
				
		foreach(Nette\Utils\Finder::findFiles("${prefix}*")->from(self::getInvoiceDirPath()) as $curr) {
			if(isset($values->replace) && $values->replace) {
				if(@unlink($curr->getRealPath()) === false)
						throw new Nette\IOException("Cannot delete file '".$curr->getRealPath()."'");
			} else {
				$form['replace']->controlPrototype->class('');
				$form['replace']->labelPrototype->class('');
				$this->flashMessage(_x('Invoice with ID %s already exists.', array($invoice->id)), 'warn');
				return;
			}
		} 
		
		file_put_contents(self::getInvoiceDirPath() . '/' . $filename . '.xml', $values->xml);
		
		$renderer = new vStore\Invoicing\InvoicePdfRenderer($this->context);
		$renderer->renderToFile($invoice, self::getInvoiceDirPath() . '/' . $filename . '.pdf');
		
		chmod(self::getInvoiceDirPath() . '/' . $filename . '.pdf', 0600);
		chmod(self::getInvoiceDirPath() . '/' . $filename . '.xml', 0600);
		
		$this->flashMessage(_x('Invoice with ID %s has been successfuly created.', array($invoice->id)));
                
		$this->redirect('Invoice:default');
	}
	
	// Payment form -------------------------------------------------------------
	
	public function renderPay($id) {
		
	}
	
	public function createComponentPayForm() {
		$now = new \DateTime('now');
		
		$form = new Form;
		$form->addDatePicker('date', __('Date of payment recieve:'))
				  ->addRule(Form::FILLED)
				  ->setDefaultValue($now->format("d.m.Y"));
		
		$form->addSubmit('save', __('Save'));
		//$form->addSubmit('back', 'Zpět')->setValidationScope(NULL);

		$form->onSuccess[] = callback($this, 'processPayForm');

		return $form;
	}
	
	public function processPayForm($form) {
		$id = (int) $this->getParam('id');

		$this->flashMessage(__('Payment has been recorded.'));
		$values = $form->getValues();

		InvoiceManager::payInvoice($id, $values["date"]);
		
		
		$this->redirect('Invoice:default');
	}
	
	public static function getInvoiceDirPath() {
		$config = \vManager\Modules\Accounting::getInstance()->getConfig();
		if(!isset($config['invoiceDir']))
			throw new \InvalidArgumentException("Missing 'Accounting.invoiceDir' configuration directive");
		
		return $config['invoiceDir'];
	}
	
}
