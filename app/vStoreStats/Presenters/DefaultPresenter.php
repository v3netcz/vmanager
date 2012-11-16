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
 * Default presenter of vStore stats
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 4, 2011
 */
class DefaultPresenter extends MonthlyPresenter {

	/** @persistent */
	public $month;
	
	public function actionDefault() {
		$this->setupParams();
	}
	
	public function renderDefault() {	
		$this->title = __('Sellings');
		
		$dailyOrders = $this->profile->getDailyOrders($this->getSince(), $this->getUntil());
		$chartData = $this->createDateArray($dailyOrders);
		$chartData2 = array();
		foreach($chartData as $k => $v) $chartData2[intval(mb_substr($k, 8))] = $v;
		
		$this->template->chartData = $chartData2;
		
		$this->template->totalRevenue = $this->profile->getTotalRevenue($this->getSince(), $this->getUntil());
		
		$this->template->totalCount = 0;
		foreach($dailyOrders as $dailyData) $this->template->totalCount += $dailyData->value;;		
		
		$this->template->avgOrderValue = $this->template->totalCount ? $this->template->totalRevenue / $this->template->totalCount : 0;
		
		$this->template->numOfUniqueCustomers = $this->profile->getUniqueCustomers($this->getSince(), $this->getUntil());
		$this->template->numOfNewCustomers = $this->profile->getNewCustomers($this->getSince(), $this->getUntil());
		$this->template->totalOrdersFromNonRegisteredUsers = $this->profile->getTotalOrdersFromNonRegisteredUsers($this->getSince(), $this->getUntil());
	}
	
	protected function createComponentProductGrid($name) {
		$grid = new vManager\Grid($this, $name);
		//$grid->setTemplateFile(__DIR__ . '/../Templates/Gridito/productGrid.latte');
		
		$data = $this->profile->getProductSellings($this->getSince(), $this->getUntil());
		$model = new vManager\Grid\ArrayModel($data);
		
		$grid->setExport(true);

		$grid->setModel($model);
		$grid->setItemsPerPage(20);
		$grid->sortColumn = $grid->sortColumn ?: 'revenue';
		$grid->sortType = $grid->sortType ?: 'desc';

		// columns
		$grid->addColumn("productId", __("ID"))->setSortable(true);
		$grid->addColumn("name", __("Product name"))->setSortable(true);
		$grid->addColumn("amount", __("Amount"))->setSortable(true)->setCellClass('amount');
		$grid->addColumn("revenue", __("Revenue"), array(
			 "renderer" => function ($row) {
				 echo vBuilder\Latte\Helpers\FormatHelpers::currency($row->revenue);
			 },
			 "sortable" => true,
		))->setCellClass('price');
	}
	
	// <editor-fold defaultstate="collapsed" desc="Report">
	
	public function actionReport($id) {
		$this->setupParams($id);
		
		$renderer = new vManager\Reporting\PdfRenderer($this->context);
		$renderer->setTemplateFile(__DIR__ . '/../Templates/Reports/sellings.latte');
		
		$renderer->template->since = $this->since;
		$renderer->template->until = $this->until;
		$renderer->template->profile = $this->profile;
		
		$renderer->template->totalRevenue = $this->profile->getTotalRevenue($this->getSince(), $this->getUntil());
		
		$renderer->template->totalCount = 0;
		$dailyOrders = $this->profile->getDailyOrders($this->getSince(), $this->getUntil());
		foreach($dailyOrders as $dailyData) $renderer->template->totalCount += $dailyData->value;
		
		$renderer->template->avgOrderValue = $renderer->template->totalCount ? $renderer->template->totalRevenue / $renderer->template->totalCount : 0;
		
		$renderer->template->sellings = new vBuilder\Utils\SortingIterator(
			$this->profile->getProductSellings($this->getSince(), $this->getUntil()),
			function ($item1, $item2) {
				return ($item1->revenue <= $item2->revenue);
			}	
		);
		
		$renderer->render();
		exit;
	}
	
	public function actionReportYearSummary($id) {
		$this->setupParams($id);
		$years = array(date('Y') - 2, date('Y') - 1, (int) date('Y'));
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
						'revenue' => $this->profile->getTotalRevenue($firstDayOfYear, $ytd),
						'customers' => $this->profile->getUniqueCustomers($firstDayOfYear, $ytd)
					);
				}
				
				// Inicializace mesicnich data ------------------------
				$data[$month][$year] = array(
					'numOrders' => 0,
					'revenue' => 0,
					'customers' => 0,
					'newCustomers' => 0
				);
				
				if($this->profile->getUntil() > $since) {
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
					$monthlyNumOrdersEstimation[$m][] = ($monthlyData[$thisYear] / $monthlyData[$lastYear]) * $data[$m][$lastYear]['numOrders'];
				}
				
				// Měsíce v budoucnosti
				else {				
					$thisYearGeometricMeanToDate = !isset($thisYearGeometricMeanToDate) ? pow($geometricMeanBaseUpToDate, 1/$geometricMeanBaseN) : $thisYearGeometricMeanToDate;
					
					$monthlyNumOrdersEstimation[$m][] = $data[$m][$lastYear]['numOrders'] * $thisYearGeometricMeanToDate;
				}
			}
		}
		
		
					
		dd($monthlyNumOrdersEstimation);

		
		$renderer = new vManager\Reporting\PdfRenderer($this->context);
		$renderer->setTemplateFile(__DIR__ . '/../Templates/Reports/yearSummary.latte');
		$renderer->template->profile = $this->profile;
		$renderer->template->years = $years;
		$renderer->template->data = $data;
		$renderer->template->td = $td;
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
	
}