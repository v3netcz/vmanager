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
	private $data;
	private $paymentDate;
	
	public function __construct($filename) {
		$this->filename = $filename;
		
		$cache = $this->getCache();
		if(isset($cache[$filename]) && $cache[$filename]['timestamp'] >= filemtime($filename)) {
			$this->data = $cache[$filename]['data'];
			if($cache[$filename]['paymentDate'] !== null && $cache[$filename]['paymentDate'] !== false)
				$this->paymentDate = $cache[$filename]['paymentDate'];
		} else {
			$this->loadFromFile();
			unset($cache[$filename]);
		}
	}
	
	public function __destruct() {
		if($this->data !== null && !isset($cache[$this->filename])) {
			$cache = $this->getCache();
			$cache[$this->filename] = array(
				 'timestamp' => time(),
				 'data' => $this->data,
				 'paymentDate' => $this->paymentDate
			);
		}
	}
	
	public function getId() {
		return $this->data['id'];
	}
	
	public function getIdPrefix() {
		return mb_substr((String) $this->data['id'], 0, 4);
	}
	
	public function getIdSulfix() {
		return mb_substr((String) $this->data['id'], 4);
	}
	
	public function getFormatedId() {
		return $this->getIdPrefix() . '/' . $this->getIdSulfix();
	}
	
	public function getDate() {
		return new \DibiDateTime($this->data['created']);
	}
	
	public function getDeadline() {
		return new \DibiDateTime($this->data['due']);
	}
	
	public function getTotal() {
		return $this->data['total'];
	}
	
	public function getPaymentDate() {
		if($this->paymentDate === null) $this->loadPaymentRecord();
		
		if($this->paymentDate != 'cancel' && $this->paymentDate !== false)
			return new \DibiDateTime($this->paymentDate);
		
		return null;
	}
	
	public function isPaid() {
		if($this->paymentDate === null) $this->loadPaymentRecord();
		
		return $this->paymentDate != 'cancel' && $this->paymentDate !== false;
	}
	
	public function isCanceled() {
		if($this->paymentDate === null) $this->loadPaymentRecord();
		
		return $this->paymentDate == 'cancel';
	}
	
	public function isOverdue() {
		return !$this->isPaid() && $this->getDeadline() < (new \DibiDateTime('now'));
	}
	
	public function getCustomerName() {
		return $this->data['customerName'];
	}
	
	public function getCustomerId() {
		return $this->data['customerId'];
	}
	
	private function loadFromFile() {
		if($this->data !== null) return ;
		
		if(!file_exists($this->filename)) throw new \FileNotFoundException("Invoice XML file '$this->filename' was not found");
		
		$xml = simplexml_load_file($this->filename);
		if($xml === false) throw new \Exception("Error parsixng invoice XML file '$this->filename'");
		
		$this->data = array();
		$this->data['id'] = (int) preg_replace("/[^0-9]+/", "", (String) $xml['number']);
		
		$this->data['customerId'] = (int) $xml->customer[0]['ic'];
		$this->data['customerName'] = (String) $xml->customer[0]->invoicename[0];
		
		$this->data['created'] = (String) $xml['created'];
		$this->data['due'] = (String) $xml->payment[0]->deadline;
		
		$this->data['total'] = 0.0;
		foreach($xml->items[0]->item as $curr) {
			$amount = isset($curr['amount']) ? (int) $curr['amount'] : 1;
			$this->data['total'] += $amount * (float) $curr['price'];
		}
	}
	
	protected function loadPaymentRecord() {
		$this->paymentDate = Nette\Environment::getContext()->connection->query("SELECT [date] FROM [accounting_invoicePayments] WHERE [invoiceId] = %i", $this->getId())->fetchSingle();
	}
	
	protected function & getCache() {
		$cache = Nette\Environment::getCache('Accounting.Invoice');
		return $cache;
	}
	
}