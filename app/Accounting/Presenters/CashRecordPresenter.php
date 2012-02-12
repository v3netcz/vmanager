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
 * Presenter for cash records
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 6, 2012
 */
class CashRecordPresenter extends RecordPresenter {
	
	private $_total;
	
	public function renderDefault() {
		$this->template->registerHelper('currency', 'vBuilder\Latte\Helpers\FormatHelpers::currency');
		
		$this->template->total = $this->getTotal();
	}
	
	protected function createComponentGeneralRecordGrid($name) {
		$presenter = $this;
		$grid = parent::createComponentGeneralRecordGrid($name);
	
		$grid->setRowClass(function ($iterator, $row) use ($presenter) {
			$classes = array();
			
			if($row->md->id == $presenter->getBillingClass()) $classes[] = 'income';
			else $classes[] = 'expense';

			return empty($classes) ? null : implode(" ", $classes);
		});
		
		return $grid;
	}
	
	public function getBillingClass() {
		return '211001';
	}

	protected function getDataSource() {
		$ds = parent::getDataSource();
		
		$ds->and('([d] LIKE %like~ OR [md] LIKE %like~)', $this->getBillingClass(), $this->getBillingClass());
		
		return $ds;
	}
	
	public function getTotal() {
		if(!isset($this->_total)) {
			$this->_total = $this->context->connection->query('SELECT SUM(IF([md] = %s, [value], 0 - [value])) AS `v` FROM ('.strval($this->getDataSource()).') AS [a]', $this->getBillingClass())
				->setType('v', \dibi::FLOAT)->fetchSingle();
			
		}
		
		return $this->_total;
	}
	
	public function createComponentRecordForm($name) {		
		$context = $this->context;
		$form = new Form($this, $name);
		$this->setupRecordForm($form);
		
		$form->addText('subjectEvidenceId', __('Bound evidence ID:'))
			->setAttribute('autocomplete-src', $this->link('CashRecord:suggestSubjectEvidenceId'))
			->setAttribute('title', __('To which receipt assign this transaction?'))
			->addRule(function ($control) use ($context) {
					return $context->repository->findAll(RecordPresenter::ENTITY_RECORD)
						->where('[evidenceId] = %s', $control->value)->fetch() !== false;
					
			}, __('Bound evidence ID does not exists.'));
		
		$this->loadRecordForm($form);
		
		return $form;
	}
	
	/**
	 * Queries suggestion item for subject evidence id
	 *
	 * @return void
	 */
	public function actionSuggestSubjectEvidenceId() {
		$typedText = $this->getParam('term', '');
		$suggestions = array();

		$ids = $this->context->connection->query('SELECT [evidenceId] FROM [accounting_records] WHERE [evidenceId] LIKE %like~ LIMIT 10', $typedText)
			->fetchAll();
			
		foreach($ids as $curr)
			$suggestions[] = $curr['evidenceId'];
			
		$this->sendResponse(new Nette\Application\Responses\JsonResponse($suggestions));
	}
	
}
