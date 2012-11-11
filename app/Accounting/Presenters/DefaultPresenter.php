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
 * Presenter for some quick reports
 *
 * @author Adam Staněk (V3lbloud)
 * @since Nov 10, 2012
 */
class DefaultPresenter extends vManager\Modules\System\SecuredPresenter {
	
	public function renderDefault() {
		$db = $this->context->database->connection;
		
		$year = 2012;
		
		// --------------------------

		$bankAccount = $db->query('SELECT SUM(IF(md = %i, [value], 0 - [value])) FROM [accounting_records] WHERE [d] = %i OR [md] = %i', 221001, 221001, 221001)->fetchSingle();
		$cash = $db->query('SELECT SUM(IF(md = %i, [value], 0 - [value])) FROM [accounting_records] WHERE [d] = %i OR [md] = %i', 211001, 211001, 211001)->fetchSingle();
		$cashOnTheWay = $db->query('SELECT SUM(IF(md = %i, [value], 0 - [value])) FROM [accounting_records] WHERE [d] = %i OR [md] = %i', 261001, 261001, 261001)->fetchSingle();
		
		// --------------------------
		
		$invoiceExpenses = $db->select('SUM([value])')->from('accounting_records')
			->where('[d] BETWEEN 321000 AND 322000')->fetchSingle();
		
		$salaryExpenses = $db->select('SUM([value])')->from('accounting_records')
			->where('[md] BETWEEN 366000 AND 367000 OR [md] BETWEEN 331000 AND 332000')->fetchSingle();
			
		$salaryTaxExpenses = $db->select('SUM([value])')->from('accounting_records')
			->where('[md] BETWEEN 342000 AND 343000')->fetchSingle();
		
		// --------------------------
		
		$monthlyIncome = $db
			->select('SUM([value]) [val], MONTH([date]) [month]')
			->from('[accounting_issuedInvoiceSummary]')
			->where('YEAR([date]) = %i', $year)
			->groupBy('MONTH([date])')
			->orderBy('[date]')
			->fetchAssoc('month=val');
		
		// --------------------------
		
		$issuedInvoicesSummary = $db
			->select('COUNT(*) AS [numInvoices], SUM([value]) [sumAllInvoices], SUM([paidValue]) [sumPaidInvoices], SUM([value]) - SUM([paidValue]) [expectedInvoiceIncome]')
			->from('[accounting_issuedInvoiceSummary]')
			->where('YEAR([date]) = %i', $year)
			->fetch();
		
		
		
		$issuedInvoicesChart = array(
			'categories' => array(),
			'series' => array(
				0 => array('name' => 'Uhrazené faktury', 'values' => array()),
				1 => array('name' => 'Faktury čekající na úhradu', 'values' => array())
			)
		);
		
		$issuedInvoicesClasses = $db
			->select('[bc.name], [d], COUNT(*) AS [numInvoices], SUM([value]) [sumAllInvoices], SUM([paidValue]) [sumPaidInvoices], SUM([value]) - SUM([paidValue]) [expectedInvoiceIncome]')
			->from('[accounting_issuedInvoiceSummary]')
			->join('[accounting_billingClasses]')->as('bc')->on('[d] = [bc.id]')
			->where('YEAR([date]) = %i', $year)
			->groupBy('[d]')
			->fetchAll();
			
		foreach($issuedInvoicesClasses as $curr) {
			$issuedInvoicesChart['categories'][] = $curr->name . "<br /><span style=\"color: #cc3300\">" . vManager\Modules\Accounting\Helpers::currency($curr->sumAllInvoices)."</span>";
			$issuedInvoicesChart['series'][0]['values'][] = $curr->sumPaidInvoices;
			$issuedInvoicesChart['series'][1]['values'][] = $curr->expectedInvoiceIncome;
		}
		
		// ----------------------
		
		$this->template->issuedInvoicesChart = $issuedInvoicesChart;
		foreach($issuedInvoicesSummary as $key => $val) $this->template->{$key} = $val;
		$this->template->invoiceExpenses = $invoiceExpenses;
		$this->template->salaryExpenses = $salaryExpenses;
		$this->template->salaryTaxExpenses = $salaryTaxExpenses;
		
		$this->template->cashOnTheWay = $cashOnTheWay;
		$this->template->cash = $cash;
		$this->template->bankAccount = $bankAccount;
		
		$this->template->monthlyIncome = $monthlyIncome;
		
		$this->template->registerHelper('currency', 'vManager\Modules\Accounting\Helpers::currency');
	}
	
}
