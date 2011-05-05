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

use dibi, Nette;

/**
 * Simple Invoice data class. Data are loaded from XML file generated
 * by company's accounting tools. This class is for convinience only.
 * It will be replaced by complex Invoice management later.
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 2, 2011
 */
class Invoice extends Nette\Object {
	
	private $filename;
	private $xml;
	
	public function __construct($filename) {
		$this->filename = $filename;
		
		$this->loadFromFile();
	}
	
	public function getId() {
		return (int) preg_replace("/[^0-9]+/", "", $this->getFormatedId());
	}
	
	public function getIdPrefix() {
		return (int) mb_substr($this->getFormatedId(), 4);
	}
	
	public function getIdSulfix() {
		return (int) mb_substr($this->getFormatedId(), 5);
	}
	
	public function getFormatedId() {
		return (String) $this->xml['number'];
	}
	
	public function getDate() {
		return new \DibiDateTime($this->xml['created']);
	}
	
	public function getDeadline() {
		return new \DibiDateTime((String) $this->xml->payment[0]->deadline);
	}
	
	public function getTotal() {
		$total = 0.0;
		
		foreach($this->xml->items[0]->item as $curr) {
			$amount = isset($curr['amount']) ? (int) $curr['amount'] : 1;
			$total += $amount * (float) $curr['price'];
		}
		
		return $total;
	}
	
	public function getPaymentDate() {
		$results = dibi::query("SELECT [date] FROM [accounting_invoicePayments] WHERE [invoiceId] = %i AND [date] <> 'cancel'", $this->getId())->fetch();
		if($results == false) return null;
		else return new \DibiDateTime($results['date']);
	}
	
	public function isPaid() {
		$results = dibi::query("SELECT 1 FROM [accounting_invoicePayments] WHERE [invoiceId] = %i AND [date] <> 'cancel'", $this->getId())->fetch();
		return $results !== false;
	}
	
	public function isCanceled() {
		$results = dibi::query("SELECT 1 FROM [accounting_invoicePayments] WHERE [invoiceId] = %i AND [date] = 'cancel'", $this->getId())->fetch();
		return $results !== false;
	}
	
	public function isOverdue() {
		return !$this->isPaid() && $this->getDeadline() < (new \DibiDateTime('now'));
	}
	
	public function getCustomerName() {
		return (String) $this->xml->customer[0]->invoicename[0];
	}
	
	public function getCustomerId() {
		return (int) $this->xml->customer[0]['ic'];
	}
	
	private function loadFromFile() {
		if($this->xml !== null) return ;
		
		if(!file_exists($this->filename)) throw new \FileNotFoundException("Invoice XML file '$this->filename' was not found");
		
		$xml = simplexml_load_file($this->filename);
		if($xml === false) throw new \Exception("Error parsixng invoice XML file '$this->filename'");
		
		$this->xml = $xml;
	}
	
}