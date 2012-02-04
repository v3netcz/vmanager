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

namespace vManager\Modules\vStoreStats;

use vManager,
	Nette,
	vManager\Form;

/**
 * Presenter of monthly statistics
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 4, 2011
 */
class MonthlyPresenter extends BasePresenter {

	/** @persistent */
	public $month;
	
	private $_months;
	
	public function actionDefault() {
		$this->setupParams();
	}
	
	public function createComponentMonthSelectionForm($name) {
		$form = new Form($this, $name);
		
		$months = array();
		foreach($this->getMonths() as $m)
			$months[$m] = vManager\Application\Helpers::monthInWords($m);
		
		$form->addSelect('month', __('Month:'), $months)->setDefaultValue($this->month);
		$form->addSubmit('send', __('Filter'));
		
		$presenter = $this;
		$form->onSuccess[] = function () use ($presenter, $form) {
			$presenter->redirect("default", (array) $form->getValues());
		}; 
		
		return $form;
	}
	
	protected function getMonths() {
		if(!isset($this->_months)) {
			$this->_months = $this->getMonthsGatherer();;
		}
		
		if(!in_array(date('Y-m'), $this->_months))
			$this->_months[] = date('Y-m');
		
		return $this->_months;
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
	
	protected function createDateArray($results, $mCol = 'date', $vCol = 'value') {
		$data = array();		
		$d = clone $this->getSince();
		
		do {
			$data[$d->format('Y-m-d')] = 0;
			$d->add(\DateInterval::createFromDateString('+1 day'));
		} while($d <= $this->getUntil());
				
		foreach($results as $curr) {
			if(isset($data[$curr->$mCol])) $data[$curr->$mCol] += $curr->$vCol;
		}
		
		return $data;
	}
	
}