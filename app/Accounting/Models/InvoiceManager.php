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

use Nette, dibi;

/**
 * Class for loading invoices from directory and sorting them by year and id
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 2, 2011
 */
class InvoiceManager {
	
	private static $invoices;
	private static $invoicesByYear;
	
	public static function getInvoices($year = null, $ic = null) {
		if(self::$invoices === null) self::loadInvoices();
		if($year === null && $ic === null) return self::$invoices;
		
		if($year === null) $src = &self::$invoices;
		elseif(isset(self::$invoicesByYear[$year])) $src = &self::$invoicesByYear[$year];
		else return array();
		
		if($ic === null) return $src;
		
		$invoices = array();
		foreach($src as &$curr) {
			if($curr->getCustomerId() == $ic)				
				$invoices[] = &$curr;
		}
		
		return $invoices;
	}
	
	public static function getNextId() {
		$invoices = array_reverse(self::getOrderedInvoices());
		if(count($invoices)) {
			$lastInvoice = reset($invoices);
			return $lastInvoice->getIdPrefix() . '/' . str_pad(((int) $lastInvoice->getIdSulfix()) + 1, 4, '0', STR_PAD_LEFT);
		}
		
		return date('Y').'/0001';
	}
	
	public static function getOrderedInvoices($invoices = null) {
		if($invoices === null) $invoices = self::getInvoices();
		
		$ids = array();
		foreach($invoices as $key=>$curr) {
			$ids[$key] = $curr->getId();
		}
		
		asort($ids);
		$ordered = array();
		foreach($ids as $key=>$value) $ordered[] = &$invoices[$key];

		return $ordered;
	}
	
	public static function getYears() {
		if(self::$invoices === null) self::loadInvoices();
		
		$years = array_keys(self::$invoicesByYear);
		sort($years);
		return $years;
	}
	
	public static function getPaidInYear($year) {
		if(self::$invoices === null) self::loadInvoices();
		$total = 0;
		
		foreach(self::$invoices as $curr) {
			$paymentDate = $curr->getPaymentDate();
			if($paymentDate && $paymentDate->format('Y') == $year) {
				$total += $curr->getTotal();
			}
		}
		
		return $total;
	}
	
	public static function getInvoiceDirPath() {
		$config = \vManager\Modules\Accounting::getInstance()->getConfig();
		if(!isset($config['invoiceDir']))
			throw new \InvalidArgumentException("Missing 'Accounting.invoiceDir' configuration directive");
		
		return $config['invoiceDir'];
	}
	
	public static function cancelInvoice($id) {
		Nette\Environment::getContext()->connection->insert("accounting_invoicePayments", array(
			 "invoiceId" => $id,
			 "date" => 'cancel'
		))->execute();
	}
	
	public static function payInvoice($id, \DateTime $date = null) {
		if($date === null) $date = new DateTime('now');
		
		Nette\Environment::getContext()->connection->insert("accounting_invoicePayments", array(
			 "invoiceId" => $id,
			 "date" => $date->format("Y-m-d")
		))->execute();
	}
	
	private static function loadInvoices() {
		$files = Nette\Utils\Finder::findFiles("*.xml")->from(self::getInvoiceDirPath());
		self::$invoices = array();
		self::$invoicesByYear = array();
		
		foreach($files as $curr) {
			if(!Nette\Utils\Strings::match($curr->getPath(), '/\\.AppleDouble/') && !Nette\Utils\Strings::match($curr->getPath(), '/workspace/') && !Nette\Utils\Strings::startsWith($curr->getFilename(), '.')) {
				$invoice = new Invoice($curr->getPath() . '/' .$curr->getFilename());
				
				if(!isset(self::$invoicesByYear[$invoice->getDate()->format("Y")]))
					self::$invoicesByYear[$invoice->getDate()->format("Y")] = array();	  
				
				$index = count(self::$invoices);
				self::$invoices[$index] = $invoice;
				self::$invoicesByYear[$invoice->getDate()->format("Y")][] = &self::$invoices[$index];
			}
		}
	}
	
}