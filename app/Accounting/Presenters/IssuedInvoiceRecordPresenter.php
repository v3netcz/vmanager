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

/*

SELECT r.id,r.evidenceId,r.date,r.description,r.value,SUM(r2.value) AS paidValue, MAX(r2.date) AS lastPaymentDate,r.md,r.d,r.subjectId,r.subjectEvidenceId,
GROUP_CONCAT(r2.id SEPARATOR ',') AS paymentRecordIds,
IF(r.d = r2.md AND r.md = r2.d,'canceled', IF(r.value = SUM(r2.value), 'paid', 'not paid')) AS state
FROM accounting_records r
LEFT JOIN accounting_records r2 ON (r.evidenceId = r2.subjectEvidenceId)
WHERE r.md = 311001
GROUP BY r.id
ORDER BY r.evidenceId

*/

	protected function isSubjectEvidendceIdNeeded() {
		return false;
	}
		
	protected function getDPrefix() {
		return '602';	// Tržby z prodeje služeb
	}
	
	protected function getMdPrefix() {
		return '311';	// Odběratelé
	}
	
	public function guessNextEvidenceId() {
		$db = $this->context->database->connection;
		$lastId = $db->select('MAX([evidenceId])')->from('[accounting_records]')
			->where('[d] LIKE %like~ AND [md] LIKE %like~', $this->getDPrefix(), $this->getMdPrefix())
			->fetchSingle();
		
		return $lastId + 1;
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
				
				// Storno faktury
				if($issued && !in_array('paid', $classes)) {
					if(!isset($linkedRecords[$row->id])) $linkedRecords[$row->id] = array();
					
					$cancelRecord = $presenter->context->connection->query(
						'SELECT [evidenceId] FROM [accounting_records]',
						'WHERE [md] = %s', $row->d->id,
						'AND [d] = %s', $row->md->id,
						'AND [value] = %f', $row->value,
						'AND [subjectId] = %i', $row->subject->id,
						'AND [subjectEvidenceId] = %s', $row->evidenceId
					)->fetch();
					
					\bd($cancelRecord);
					
					if($cancelRecord !== FALSE) {
						$classes[] = 'canceled';
						$linkedRecords[$row->id][] = $cancelRecord['evidenceId'];
					}
				}
				
			/* } else {
				$classes[] = 'paid';
			} */

			return empty($classes) ? null : implode(" ", $classes);
		});
		

		if($issued) {
			$presenter = $this;
		
			$grid->addButton("btnCancel", __('Cancel'), array(
				"class" => "button_black button_cancel",
				"confirmationQuestion" => function ($row) use ($grid) {
					return __('Are you sure you want to cancel this invoice?');
				},
						  
				"handler" => function ($row) use ($grid, $presenter) {
					if(!$row) $presenter->flashMessage(__('Record not found'), 'warn');
					else {
						if(!($row->d && $row->md && $row->evidenceId && $row->value && $row->description))
							$presenter->flashMessage(__('Cannot cancel incomplete record'), 'warn');
						else {
							// $evidenceId = $presenter->guessNextEvidenceId();
							$evidenceId = 0;
						
							$record = $presenter->getContext()->repository->create(IssuedInvoiceRecordPresenter::ENTITY_RECORD);
							
							$record->d = $row->md;
							$record->md = $row->d;
							$record->value = $row->value;
							$record->description = "Storno FA " . $row->evidenceId . " - " . $row->description;
							$record->date = new \DateTime;
							//$record->evidenceId = $evidenceId;
							$record->subjectEvidenceId = $row->evidenceId;
							$record->subject = $row->subject;
							
							$record->save();
							
							$presenter->flashMessage(_x("Canceling record %d has been added", array($evidenceId)));
						}
					}
	
					$grid->redirect("this");
				}
			));
		}

		
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
