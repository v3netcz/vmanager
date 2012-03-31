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
 * Presenter for billing class balance
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 6, 2012
 */
class BalancePresenter extends vManager\Modules\System\SecuredPresenter {
	
	public function renderDefault() {
		$balance = $this->context->connection->query(
			'SELECT id, name, ' .
				'(SELECT SUM(IF(md = a.id, value, 0 - value)) FROM accounting_records WHERE md = a.id OR d = a.id) AS balance ' .
			'FROM accounting_billingClasses AS a ' .
			'HAVING balance IS NOT NULL ' .
			'ORDER BY [id]'
		);
		
		$barData = array();
		$min = null; $max = null;
		foreach($balance as $curr) {
			
			if($curr->id == '211001' || $curr->id == '221001') {
				$b = array('y' => $curr->balance, 'color' => '#89A54E');
			} elseif($curr->balance > 0) {
				$b = array('y' => -$curr->balance, 'color' => '#AA4643');
			} else {
				$b = -$curr->balance;
			}
			
			$barData[$curr->id . '<br />' . wordwrap($curr->name, 30, '<br />')] = $b;
					
			if($curr->balance > $max) $max = $curr->balance;
			if($curr->balance < $min) $min = $curr->balance;
		}
		
		$this->template->barRangeMax = max($max, abs($min)) * 1.1;
		$this->template->barRangeMin = -$this->template->barRangeMax;
		$this->template->barData = $barData;
	}
	
	// <editor-fold defaultstate="collapsed" desc="Reporting - Balance sheet">
	
	/**
	 * Vytvoří bilanční rozvahu
	 * http://business.center.cz/business/pravo/zakony/ucto-v2002-500/priloha1.aspx
	 */
	public function actionBalanceSheet($id) {
		$year = 2012;
				
		$renderer = new vManager\Reporting\PdfRenderer($this->context);
		$renderer->setTemplateFile(__DIR__ . '/../Templates/Reports/balanceSheet.latte');
		
		$renderer->template->year = $year;
		if($year == date('Y')) $renderer->template->until = new \DateTime;
		
		// #################################################################################
		
		$assets = array();
		$assets[] = $this->createBilanceItem("Pohledávky za upsaný základní kapitál");
		
		// DLOUHODOBY MAJETEK --------------------------------------------------------------
				
		$dlouhodobyMajetek = array();
		
		$dlouhodobyNehmotnyMajetek = array();
		$dlouhodobyNehmotnyMajetek[] = $this->createBilanceItem("Zřizovací výdaje");
		$dlouhodobyNehmotnyMajetek[] = $this->createBilanceItem("Nehmotné výsledky výzkumu a vývoje");
		$dlouhodobyNehmotnyMajetek[] = $this->createBilanceItem("Software");
		$dlouhodobyNehmotnyMajetek[] = $this->createBilanceItem("Ocenitelná práva");
		$dlouhodobyNehmotnyMajetek[] = $this->createBilanceItem("Goodwill");
		$dlouhodobyNehmotnyMajetek[] = $this->createBilanceItem("Jiný dlouhodobý nehmotný majetek");
		$dlouhodobyNehmotnyMajetek[] = $this->createBilanceItem("Nedokončený dlouhodobý nehmotný majetek");
		$dlouhodobyNehmotnyMajetek[] = $this->createBilanceItem("Poskytnuté zálohy na dlouhodobý nehmotný majetek");
		$dlouhodobyMajetek[] = $this->createBilanceParent("Dlouhodobý nehmotný majetek", $dlouhodobyNehmotnyMajetek);
		
		$dlouhodobyHmotnyMajetek = array();
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Pozemky");
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Stavby");
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Samostatné movité věci a soubory movitých věcí");
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Pěstitelské celky trvalých porostů");
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Dospělá zvířata a jejich skupiny");
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Jiný dlouhodobý hmotný majetek");
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Nedokončený dlouhodobý hmotný majetek");
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Poskytnuté zálohy na dlouhodobý hmotný majetek");
		$dlouhodobyHmotnyMajetek[] = $this->createBilanceItem("Oceňovací rozdíl k nabytému majetku");
		$dlouhodobyMajetek[] = $this->createBilanceParent("Dlouhodobý hmotný majetek", $dlouhodobyHmotnyMajetek);
		
		$dlouhodobyFinancniMajetek = array();
		$dlouhodobyFinancniMajetek[] = $this->createBilanceItem("Podíly – ovládaná osoba");
		$dlouhodobyFinancniMajetek[] = $this->createBilanceItem("Podíly v účetních jednotkách pod podstatným vlivem");
		$dlouhodobyFinancniMajetek[] = $this->createBilanceItem("Ostatní dlouhodobé cenné papíry a podíly");
		$dlouhodobyFinancniMajetek[] = $this->createBilanceItem("Půjčky a úvěry – ovládaná nebo ovládající osoba, podstatný vliv");
		$dlouhodobyFinancniMajetek[] = $this->createBilanceItem("Jiný dlouhodobý finanční majetek");
		$dlouhodobyFinancniMajetek[] = $this->createBilanceItem("Pořizovaný dlouhodobý finanční majetek");
		$dlouhodobyFinancniMajetek[] = $this->createBilanceItem("Poskytnuté zálohy na dlouhodobý finanční majetek");
		$dlouhodobyMajetek[] = $this->createBilanceParent("Dlouhodobý finanční majetek", $dlouhodobyFinancniMajetek);
		
		$assets[] = $this->createBilanceParent("Dlouhodobý majetek", $dlouhodobyMajetek);
		
		// OBEZNA AKTIVA -------------------------------------------------------------------

		$obeznaAktiva = array();
		
		$zasoby = array();
		$zasoby[] = $this->createBilanceItem("Materiál");
		$zasoby[] = $this->createBilanceItem("Nedokončená výroba a polotovary");
		$zasoby[] = $this->createBilanceItem("Výrobky");
		$zasoby[] = $this->createBilanceItem("Mladá a ostatní zvířata a jejich skupiny");
		$zasoby[] = $this->createBilanceItem("Zboží");
		$zasoby[] = $this->createBilanceItem("Poskytnuté zálohy na zásoby");
		$obeznaAktiva[] = $this->createBilanceParent("Zásoby", $zasoby);		
		
		$dlouhodobePohledavky = array();
		$dlouhodobePohledavky[] = $this->createBilanceItem("Pohledávky z obchodních vztahů");
		$dlouhodobePohledavky[] = $this->createBilanceItem("Pohledávky – ovládaná nebo ovládající osoba");
		$dlouhodobePohledavky[] = $this->createBilanceItem("Pohledávky - podstatný vliv");
		$dlouhodobePohledavky[] = $this->createBilanceItem("Pohledávky za společníky, členy družstva a za účastníky sdružení");
		$dlouhodobePohledavky[] = $this->createBilanceItem("Dlouhodobé poskytnuté zálohy");
		$dlouhodobePohledavky[] = $this->createBilanceItem("Dohadné účty aktivní");
		$dlouhodobePohledavky[] = $this->createBilanceItem("Jiné pohledávky");
		$dlouhodobePohledavky[] = $this->createBilanceItem("Odložená daňová pohledávka");
		$obeznaAktiva[] = $this->createBilanceParent("Dlouhodobé pohledávky", $dlouhodobePohledavky);
		
		$kratkodobePohledavky = array();
		$kratkodobePohledavky[] = $this->createBilanceItem("Pohledávky z obchodních vztahů");
		$kratkodobePohledavky[] = $this->createBilanceItem("Pohledávky – ovládaná nebo ovládající osoba");
		$kratkodobePohledavky[] = $this->createBilanceItem("Pohledávky - podstatný vliv");
		$kratkodobePohledavky[] = $this->createBilanceItem("Pohledávky za společníky, členy družstva a za účastníky sdružení");
		$kratkodobePohledavky[] = $this->createBilanceItem("Sociální zabezpečení a zdravotní pojištění");
		$kratkodobePohledavky[] = $this->createBilanceItem("Stát – daňové pohledávky");
		$kratkodobePohledavky[] = $this->createBilanceItem("Krátkodobé poskytnuté zálohy");
		$kratkodobePohledavky[] = $this->createBilanceItem("Dohadné účty aktivní");
		$kratkodobePohledavky[] = $this->createBilanceItem("Jiné pohledávky");
		$obeznaAktiva[] = $this->createBilanceParent("Krátkodobé pohledávky", $kratkodobePohledavky);
		
		$kratkodobyFinancniMajetek = array();
		$kratkodobyFinancniMajetek[] = $this->createBilanceItem("Peníze");
		$kratkodobyFinancniMajetek[] = $this->createBilanceItem("Účty v bankách");
		$kratkodobyFinancniMajetek[] = $this->createBilanceItem("Krátkodobé cenné papíry a podíly");
		$kratkodobyFinancniMajetek[] = $this->createBilanceItem("Pořizovaný krátkodobý finanční majetek");
		$obeznaAktiva[] = $this->createBilanceParent("Krátkodobé pohledávky", $kratkodobePohledavky);
		
		$assets[] = $this->createBilanceParent("Oběžná aktiva", $obeznaAktiva);
		
		// CASOVE ROZLISENI ----------------------------------------------------------------
		
		$casoveRozliseni = array();
		$casoveRozliseni[] = $this->createBilanceItem("Náklady příštích období");
		$casoveRozliseni[] = $this->createBilanceItem("Komplexní náklady příštích období");
		$casoveRozliseni[] = $this->createBilanceItem("Příjmy příštích období");
		$assets[] = $this->createBilanceParent("Časové rozlišení", $casoveRozliseni);
		
		
		// #################################################################################
		
		$liabilities = array();
		
		$renderer->template->assets = $this->createBilanceParent("Aktiva celkem", $assets);
		$renderer->template->liabilities = $this->createBilanceItem("Pasiva celkem", $liabilities);
		
		
		
		// $assets[] = array("name" => "Pohledávky za upsaný základní kapitál", "value" =>);
		
		
		/* $renderer->template->since = $this->getSince();
		$renderer->template->until = $this->getUntil();		
		$renderer->template->employee = $this->employee;
		
		$renderer->template->workHours = $this->getHours()->orderBy('[date]');
				
		$renderer->template->sum = 0;
		foreach($renderer->template->workHours as $curr) {
			$renderer->template->sum += $curr->hours;
		}
		
		$renderer->template->sum = ceil($renderer->template->sum);
		$renderer->template->payPerHour = $this->employee->getCurrentPay($renderer->template->since);
		
		$renderer->template->pay = $renderer->template->sum * $renderer->template->payPerHour;
		$renderer->template->tax = $renderer->template->pay * 0.15;
		*/
		
		$renderer->render(); 
		exit;
	}

	private function createBilanceItem($name) {
		$t = array("name" => $name, "value" => array(0 => 0));
	
		for($i = 1; $i < func_num_args(); $i++) {
			$t[$i - 1] = func_get_arg($i);
		}
		
		return $t;
	}
	
	private function createBilanceParent($name, array $children = array()) {
		$t = array("name" => $name, "value" => array(0 => 0), "children" => $children);
		
		foreach($children as $child) {
			foreach($child["value"] as $k=>$v) {
				if(isset($t["value"][$k]))
					$t["value"][$k] += $v;
				else
					$t["value"][$k] = $v;
			}
		}
		
		return $t;
	}
	
	// </editor-fold>
	
}
