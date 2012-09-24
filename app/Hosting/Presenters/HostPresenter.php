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

namespace vManager\Modules\Hosting;

use vManager, vBuilder, Nette, vManager\Form, vStore, Gridito;

/**
 * Presenter for listing of virtual hosts
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jul 10, 2011
 */
class HostPresenter extends vManager\Modules\System\SecuredPresenter {

	protected function createComponentHostGrid($name) {
		$grid = new vManager\Grid($this, $name);

		$db = $this->context->database->connection;
		$ds = $db->select(
				'[h.id], [d.dn],',
				"GROUP_CONCAT([d2.dn] ORDER BY [d2.dn] SEPARATOR ', ') AS altDn,",
				'(SELECT MAX(date) FROM hosting_bills WHERE hostId = h.id) AS lastBillingDate,',
				'[h.billable], [h.state]')
				->from('[hosting_hosts] h')
				->join('[hosting_domains] d')->on('[d.id] = [h.mainDomain]')
				->leftJoin('[hosting_domains] d2')->on('[d2.hostId] = [h.id] AND [d2.id] <> [h.mainDomain]')
				->groupBy('[h.id]');
				
		if($grid->sortColumn === null) {
			$ds->orderBy('[h.state], [h.billable] DESC, [lastBillingDate]');
		}		

		$model = new Gridito\DibiFluentModel($ds);
		$model->setPrimaryKey('h.id');

		$grid->setModel($model);
		$grid->setItemsPerPage(50);

		$today = new \DateTime;
		$grid->setRowClass(function ($iterator, $row) use($today) {
			$classes = array();
			
			if($row->state == 'CANCELED') $classes[] = 'canceled';
			if($row->billable == 0) $classes[] = 'notBillable';
		
			if(isset($row->lastBillingDate)) {
				if($row->lastBillingDate->format('Y') == date('Y')) $classes[] = 'billedThisYear';
			
				if((int) $row->lastBillingDate->format('Y') < (int) date('Y')) {
				 	$date = clone $row->lastBillingDate;
				 	$date->add(\DateInterval::createFromDateString('1 year'));
				 	
				 	if($date < $today) {
				 		$classes[] = 'overdue';
				 	}
			 	}
				
			} else
				$classes[] = 'notBilledYet';


			return empty($classes) ? null : implode(" ", $classes);
		});

		// columns
		// $grid->addColumn("dn", __('Domain name'))->setSortable(true);
		$grid->addColumn("dn", __("Domain name"), array(
			 "renderer" => function ($row) {
			 	echo Nette\Templating\Helpers::escapeHtml($row->dn);
			 	if($row->altDn) {
				 	echo "<div class=\"altDn\">" . $row->altDn . "</div>";
			 	}
			 },
			 "sortable" => false,
		))->setCellClass("dn");
		
		$grid->addColumn("lastBillingDate", __("Last billing date"), array(
			 "renderer" => function ($row) {
			 	if($row->billable)
				 	echo isset($row->lastBillingDate) ? $row->lastBillingDate->format('j. n. Y') : '-';
				else
					echo __("Not billable");
			 },
			 "sortable" => false,
		))->setCellClass("billingDate");
		
		$grid->addColumn("state", __("State"), array(
			 "renderer" => function ($row) {
			 	if(!$row->billable)
			 		echo __('OK');
			 	elseif($row->state == 'CANCELED')
			 		echo __('CANCELED');
			 	elseif(!isset($row->lastBillingDate))
			 		echo __('NOT PAID YET');
			 	else {
				 	if((int) $row->lastBillingDate->format('Y') < (int) date('Y')) {
					 	$date = clone $row->lastBillingDate;
					 	$date->add(\DateInterval::createFromDateString('1 year'));
					 	$today = new \DateTime;
					 	if($date < $today) {
					 		echo "OVERDUE";
					 		return;
					 	}
				 	}
				 	
				 	echo __('OK');
			 	}

			 }
		))->setCellClass("billingState");
		
		$grid->addButton("btnBill", __('Bill'), array(					  
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					Nette\Environment::getApplication()->getPresenter()->redirect('Host:bill', $row->id);
				}

				$grid->redirect("this");
			}
		));

	}
	
	public function actionBill($id) {
		$host = $this->context->database->connection
				->select('*')->from('hosting_hosts h')
				->leftJoin('hosting_domains d')->on('[h.mainDomain] = [d.id]')
				->where('[h.id] = %i', $id)->fetch();				
				
		if($host === false) {
			$this->flashMessage(__('Host not found'), 'warning');
			$this->redirect('Host:default');
		}
				
		$this->template->host = $host;
	}
	
	public function createComponentBillingForm() {
		$now = new \DateTime('now');
		
		$form = new Form;
		$form->addDatePicker('date', __('Billing date:'))
				  ->addRule(Form::FILLED)
				  ->setDefaultValue($now->format("d.m.Y"));
		
		$form->addText('evidenceId', __('Evidence ID:'));
		
		$form->addSubmit('save', __('Save'));
		//$form->addSubmit('back', 'Zpět')->setValidationScope(NULL);

		$form->onSuccess[] = callback($this, 'processBillingForm');

		return $form;
	}
	
	public function processBillingForm($form) {
		$hostId = (int) $this->getParam('id');

		$this->flashMessage(__('The bill has been bound'));
		$values = $form->getValues();

		$this->context->database->connection->insert('hosting_bills', array(
			'hostId' => $hostId,
			'date' => $values->date,
			'evidenceNumber' => $values->evidenceId
		))->execute();		
		
		$this->redirect('Host:default');
	}
	
}