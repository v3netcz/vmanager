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
class ReceivedInvoiceRecordPresenter extends IssuedInvoiceRecordPresenter {

	protected function isSubjectEvidendceIdNeeded() {
		return true;
	}
		
	protected function getDPrefix() {
		return '5';	// Náklady
	}
	
	protected function getMdPrefix() {
		return '321';	// Dodavatelé
	}
	
	protected function createComponentGeneralRecordGrid($name) {
		$grid = parent::createComponentGeneralRecordGrid($name);
	
		$grid['columns']->getComponent('subject')->setLabel(__('Supplier'));
		$grid['columns']->getComponent('subjectEvidenceId')->setLabel(__('S. evidence ID'));
		
		return $grid;
	}
	
	public function createComponentRecordForm($name) {		
		$form = parent::createComponentRecordForm($name);
		
		$form['subject']->caption = __('Supplier:');
		$form['subjectEvidenceId']->caption = __('Supplier evidence ID:');
		
		return $form;
	}
	
}
