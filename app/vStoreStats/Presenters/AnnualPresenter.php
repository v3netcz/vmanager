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
	vBuilder,
	Nette;

/**
 * Annual summary presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jun 27, 2012
 */
class AnnualPresenter extends MonthlyPresenter {

	/** @persistent */
	public $month;
	
	public function actionDefault() {
		$this->setupParams();
	}
	
	public function getYears() {
		$availableYears = range((int)$this->profile->getSince()->format('Y'), (int) $this->profile->getUntil()->format('Y'));
		return count($availableYears) > 3 ? array_slice($availableYears, -3, 3) : $availableYears;
	}
	
	public function renderDefault() {	
		$years = $this->getYears();
		$data = $this->gatherData($years);
		
		$this->template->chartData  = array();
		$this->template->chartLabels = array();
		for($month = 1; $month <= 12; $month++) {
			$this->template->chartLabels[] = vManager\Application\Helpers::trMonth($month);

			foreach(array_slice($years, 0, -1) as $y)
				$this->template->chartData[$y][$month] = $data['realMonthlyData'][$month][$y]['numOrders'];
				
			$this->template->chartData[$years[count($years) - 1]][$month] = round($data['thisYearMonthlyNumOrdersEstimation'][$month]);
		}
	}

	
	// <editor-fold defaultstate="collapsed" desc="Report">
	
	public function actionReport($id) {
		$this->setupParams($id);
		
		$years = $this->getYears();
		$data = $this->gatherData($years);
		
		$renderer = new vManager\Reporting\PdfRenderer($this->context);
		$renderer->setTemplateFile(__DIR__ . '/../Templates/Reports/yearSummary.latte');
		$renderer->template->profile = $this->profile;
		$renderer->template->years = $years;
		$renderer->template->data = $data['realMonthlyData'];
		$renderer->template->td = new \DateTime('now');
		$renderer->template->labels = array(
			'numOrders' => 'Počet objednávek',
			'revenue' => 'Tržba',
			'customers' => '# zákazníků',
			'newCustomers' => '# nových zákazníků'
		);
		$renderer->render();
		
		exit;
	}
	
	// </editor-fold>
	
	
	private function gatherData($years) {
		$td = new \DateTime('now');
		$td->setTime(0, 0, 0); // Dnesni objednavky uz nechceme, aby report byl po cely den konzistentni
		
		$data = array(0 => array());
		
		
		$geometricMeanData = array();		
		
		for($month = 1; $month <= 12; $month++) {
			foreach($years as $year) {
				$since = \DateTime::createFromFormat('Y-n-d H:i:s', $year . '-' . $month . '-01 00:00:00');
				$until = clone $since;
				$until->add(\DateInterval::createFromDateString('1 month'));
				$until->sub(\DateInterval::createFromDateString('1 second'));
				if($until > $td) {
					$until = clone $td; // Zariznuti dnesnich objednavek
					$until->sub(\DateInterval::createFromDateString('1 second'));
				}

				
				// Inicializace souhrnu -------------------------------
				if(!isset($data[0][$year])) {
					$firstDayOfYear = \DateTime::createFromFormat('Y-m-d H:i:s', $year . '-01-01 00:00:00');
					$ytd = \DateTime::createFromFormat('Y-m-d H:i:s', $year . '-' . $td->format('m-d') . ' 00:00:00')->sub(\DateInterval::createFromDateString('1 second'));
					
					$data[0][$year] = array(
						'numOrders' => 0,
						'revenue' => $ytd > $this->profile->since ? $this->profile->getTotalRevenue($firstDayOfYear, $ytd) : 0,
						'customers' => $ytd > $this->profile->since ? $this->profile->getUniqueCustomers($firstDayOfYear, $ytd) : 0
					);
				}
				
				// Inicializace mesicnich data ------------------------
				$data[$month][$year] = array(
					'numOrders' => 0,
					'revenue' => 0,
					'customers' => 0,
					'newCustomers' => 0
				);
				
				if($this->profile->getUntil() > $since && $until > $this->profile->getSince()) {
					$data[$month][$year]['revenue'] = $this->profile->getTotalRevenue($since, $until);
				
					$dailyOrders = $this->profile->getDailyOrders($since, $until);
					$ordersToDate = 0;
					foreach($dailyOrders as $dailyData) {
						$day = \DateTime::createFromFormat('Y-m-d H:i:s', $dailyData->date . ' 00:00:00');
						
						if($day->format('n') < $td->format('n') || ($day->format('n') == $td->format('n') && $day->format('j') < $td->format('j'))) {
							$data[0][$year]['numOrders'] += $dailyData->value;
							$ordersToDate += $dailyData->value;
						}
						
						$data[$month][$year]['numOrders'] += $dailyData->value;
					}
					
					$data[$month][$year]['customers'] = $this->profile->getUniqueCustomers($since, $until);
					$data[$month][$year]['newCustomers'] = $this->profile->getNewCustomers($since, $until);
					

					// Priprava dat pro geometricky prumer
					// U aktualniho mesice budeme brat stav ke dnesnimu dni, jinak budeme davat soucet vsech objednavek za dany mesic
					$geometricMeanData[$month][$year] = $ordersToDate > 0 ? $ordersToDate : $data[$month][$year]['numOrders'];
				}
			}
		}
		
		// Geometrický odhad pro víceroční data	
		if(count($years) > 1) {
			
			// Spočítáme průměry pro letošní rok, pro budoucí měsíce
			// použijeme k vytěžení výsledku differenci z loňských dat
			$geometricMeanBaseN = 0;
			$geometricMeanBaseUpToDate = 1;
			$monthlyNumOrdersEstimation = array();
			
			$lastYear = $years[count($years) - 2];
			$thisYear = $years[count($years) - 1];
			
			foreach($geometricMeanData as $m=>$monthlyData) {
				
				// Již uplynulý / aktuální měsíc
				if(isset($monthlyData[$thisYear]) && isset($monthlyData[$lastYear])) {
					$geometricMeanBaseN++;
					$geometricMeanBaseUpToDate *= $monthlyData[$thisYear] / $monthlyData[$lastYear];
					$monthlyNumOrdersEstimation[$m] = ($monthlyData[$thisYear] / $monthlyData[$lastYear]) * $data[$m][$lastYear]['numOrders'];
				}
				
				// Měsíce v budoucnosti
				else {				
					$thisYearGeometricMeanToDate = !isset($thisYearGeometricMeanToDate) ? pow($geometricMeanBaseUpToDate, 1/$geometricMeanBaseN) : $thisYearGeometricMeanToDate;
					
					$monthlyNumOrdersEstimation[$m] = $data[$m][$lastYear]['numOrders'] * $thisYearGeometricMeanToDate;
				}
			}
		} else {
			$monthlyNumOrdersEstimation = null;
		}
		
		return array('realMonthlyData' => $data, 'thisYearMonthlyNumOrdersEstimation' => $monthlyNumOrdersEstimation);
	}
	
}