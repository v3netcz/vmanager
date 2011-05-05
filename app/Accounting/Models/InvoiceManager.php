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
	
	public static function getInvoices($year = null, $ic = null) {
		if(self::$invoices === null) self::loadInvoices();
		if($year === null && $ic === null) return self::$invoices;
		
		$invoices = array();
		foreach(self::$invoices as $curr) {
			if( ($year === null || $curr->getDate()->format("Y") == $year)
				 && ($ic === null || $curr->getCustomerId() == $ic)
					  )
							
						$invoices[] = $curr;
		}
		
		return $invoices;
	}
	
	public static function getOrderedInvoices($invoices = null) {
		if($invoices === null) $invoices = self::getInvoices();
		
		$ids = array();
		foreach($invoices as $key=>$curr) {
			$ids[$key] = $curr->getId();
		}
		
		asort($ids);
		$ordered = array();
		foreach($ids as $key=>$value) $ordered[] = $invoices[$key];

		return $ordered;
	}
	
	public static function getYears() {
		if(self::$invoices === null) self::loadInvoices();
		
		$years = array();
		foreach(self::$invoices as $curr) {
			$year = (int) $curr->getDate()->format('Y');
			
			if(!in_array($year, $years))
				$years[] = $year;
		}
		
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
		dibi::insert("accounting_invoicePayments", array(
			 "invoiceId" => $id,
			 "date" => 'cancel'
		))->execute();
	}
	
	public static function payInvoice($id, \DateTime $date = null) {
		if($date === null) $date = new DateTime('now');
		
		dibi::insert("accounting_invoicePayments", array(
			 "invoiceId" => $id,
			 "date" => $date->format("Y-m-d")
		))->execute();
	}
	
	private static function loadInvoices() {
		$files = Nette\Utils\Finder::findFiles("*.xml")->from(self::getInvoiceDirPath());
		self::$invoices = array();
		
		foreach($files as $curr) {
			if(!Nette\Utils\Strings::match($curr->getPath(), '/\\.AppleDouble/'))
				self::$invoices[] = new Invoice($curr->getPath() . '/' .$curr->getFilename());
		}
	}
	
}