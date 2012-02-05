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
 * Presenter for issued invoices
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 6, 2012
 */
class IssuedInvoiceRecordPresenter extends RecordPresenter {

		
	protected function getDPrefix() {
		return '602';	// Tržby z prodeje služeb
	}
	
	protected function getMdPrefix() {
		return '311';	// Odběratelé
	}
	
	protected function createComponentGeneralRecordGrid($name) {
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
		
		$grid->addColumn('subjectEvidenceId', __('C. evidence ID'))
			->setCellClass('evidenceId')
			->setOrderColumnWeight(4);
		
		$grid['columns']->getComponent('description')->setOrderColumnWeight(8);
		$grid['columns']->getComponent('value')->setOrderColumnWeight(9);	
		$grid['columns']->getComponent('md')->setOrderColumnWeight(10);
		$grid['columns']->getComponent('d')->setOrderColumnWeight(11);
		
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
			  	
		$form->addText('subjectEvidenceId', __('Customer evidence ID:'));
		
		$this->loadRecordForm($form);
		
		return $form;
	}
	
	protected function saveRecordForm(Form $form, vBuilder\Orm\ActiveEntity $record) {
		parent::saveRecordForm($form, $record);
		
		$subject = $this->context->repository->findAll(SubjectPresenter::ENTITY_SUBJECT)
						->where('[name] = %s', $form->values->subject)->fetch();
						
		if($subject === false) throw new Nette\InvalidStateException('Subject does not exist');
		else $record->subject = $subject->id;
	}
	
}
