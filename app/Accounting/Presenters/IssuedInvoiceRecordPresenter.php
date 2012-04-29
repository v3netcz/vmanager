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
 * Presenter for issued invoices
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 6, 2012
 */
class IssuedInvoiceRecordPresenter extends RecordPresenter {

	public function renderDefault() {
		$balance = $this->context->connection->query(
			'SELECT id, name, ' .
				'(SELECT SUM(IF(md = a.id, value, 0 - value)) ' .
				'FROM accounting_records WHERE (md = a.id OR d = a.id) ' .
				// 'AND [date] BETWEEN %s AND %s',  $this->getSince()->format('Y-m-d'), $this->getUntil()->format('Y-m-d 23:59:59'),
				') AS balance ' .
			'FROM accounting_billingClasses AS a ' .
			'WHERE [id] LIKE %like~ OR [id] LIKE %like~ ' .
			'HAVING balance IS NOT NULL ',
			$this->getDPrefix(), $this->getMdPrefix()
		);
		
		$totalPaid = 0;
		$totalAwaiting = 0;
		foreach($balance as $curr) {
			if(Strings::startsWith($curr->id, $this->getDPrefix()))
				$totalPaid += abs($curr->balance);
			else
				$totalAwaiting += abs($curr->balance);
		}
		
		$this->template->totalPaid = $totalPaid - $totalAwaiting;
		$this->template->totalAwaiting = $totalAwaiting;
	}

	protected function isSubjectEvidendceIdNeeded() {
		return false;
	}
		
	protected function getDPrefix() {
		return '602';	// Tržby z prodeje služeb
	}
	
	protected function getMdPrefix() {
		return '311';	// Odběratelé
	}
	
	protected function createComponentGeneralRecordGrid($name) {
		$presenter = $this;
		$grid = parent::createComponentGeneralRecordGrid($name);
	
		$grid->addColumn("subject", __("Customer"), array(
			 "renderer" => function ($row) {
				 echo Nette\Utils\Html::el("a")
				 			->href("#")
				 			->setText($row->subject ? $row->subject->name : '???');
			 },
			 "sortable" => true,
			 "orderColumnWeight" => 3
		));
		
		if($this->isSubjectEvidendceIdNeeded()) {
			$grid->addColumn('subjectEvidenceId', __('C. evidence ID'))
				->setCellClass('evidenceId')
				->setOrderColumnWeight(4);
		}
		
		$grid['columns']->getComponent('description')->setOrderColumnWeight(8);
		$grid['columns']->getComponent('value')->setOrderColumnWeight(9);	
		$grid['columns']->getComponent('md')->setOrderColumnWeight(10);
		$grid['columns']->getComponent('d')->setOrderColumnWeight(11);
				
		$linkedRecords = array();
		$grid['columns']->getComponent('evidenceId')->setRenderer(function ($row) use(&$linkedRecords) {
			 echo Helpers::evidenceId($row->evidenceId);
			 
			 if(isset($linkedRecords[$row->id]) && count($linkedRecords[$row->id])) {
			 	echo Nette\Utils\Html::el("span")
			 				->class("evidenceLink")
			 				->title(_x('Linked with evidence n. %s', array(implode(', ', $linkedRecords[$row->id]) )))
				 			->add(Nette\Utils\Html::el('span')->setText('L'));
			 }
			 
		});
		
		
		$issued = ($this->getDPrefix() == '602');
		$grid->setRowClass(function ($iterator, $row) use ($presenter, &$linkedRecords, $issued) {
			$classes = array();
			
			//if($presenter->template->totalAwaiting >= $row->value) {
			
				if($issued) {
					$e = $presenter->context->connection->query(
						'SELECT [evidenceId], [value] FROM [accounting_records]',
						'WHERE [d] = %s', $row->d->id,
						// 'AND [date] >= %s', $row->date->format('Y-m-d'),
						'AND [subjectEvidenceId] = %s', $row->evidenceId
					)->fetchAll();
				} else {
					$e = $presenter->context->connection->query(
						'SELECT [evidenceId], [value] FROM [accounting_records]',
						'WHERE [md] = %s', $row->d->id,
						// 'AND [date] >= %s', $row->date->format('Y-m-d'),
						'AND [subjectEvidenceId] IN %in AND [subjectEvidenceId] <> \'\'', array($row->subjectEvidenceId, $row->evidenceId)
					)->fetchAll();
				}
				
				Nette\Diagnostics\Debugger::barDump($e);
				
				if($e !== false) {

					$sum = 0;
					$linkedRecords[$row->id] = array();
					foreach($e as $curr) {
						$sum += $curr->value;
						$linkedRecords[$row->id][] = Helpers::evidenceId($curr['evidenceId']);
					}
					
					if($sum == $row->value)
						$classes[] = 'paid';
					
				}	
			/* } else {
				$classes[] = 'paid';
			} */

			return empty($classes) ? null : implode(" ", $classes);
		});
		

		
		return $grid;
	}
	
	public function createComponentRecordForm($name) {		
		$context = $this->context;
		$form = new Form($this, $name);
		$this->setupRecordForm($form);
		
		$form->addText('subject', __('Customer:'))->setRequired()
				->setAttribute('autocomplete-src', $this->link('Subject:suggestSubject'))
			  	->setAttribute('title', __('From whom did you get the receipt?'))
			  	->addRule(function ($control) use ($context) {
					return $context->repository->findAll(SubjectPresenter::ENTITY_SUBJECT)
						->where('[name] = %s', $control->value)->fetch() !== false;
					
				}, __('Supplier does not exist in subject database. Please add him first.'));
		
		if($this->isSubjectEvidendceIdNeeded())	  	
			$form->addText('subjectEvidenceId', __('Customer evidence ID:'));
		
		$this->loadRecordForm($form);
		
		return $form;
	}
	
	protected function loadRecordForm(Form $form) {
		$record = parent::loadRecordForm($form);
	
		if($record) {
			if($record->subject) {
				$form['subject']->setDefaultValue($record->subject->name);
			}
		}
	
		return $record;
	}
	
	protected function saveRecordForm(Form $form, vBuilder\Orm\ActiveEntity $record) {
		parent::saveRecordForm($form, $record);
		
		$subject = $this->context->repository->findAll(SubjectPresenter::ENTITY_SUBJECT)
						->where('[name] = %s', $form->values->subject)->fetch();
						
		if($subject === false) throw new Nette\InvalidStateException('Subject does not exist');
		else $record->subject = $subject->id;
	}
	
}
