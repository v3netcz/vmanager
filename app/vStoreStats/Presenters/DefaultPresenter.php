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
		$dailyOrders = $this->profile->getDailyOrders($this->getSince(), $this->getUntil());
		$chartData = $this->createDateArray($dailyOrders);
		$chartData2 = array();
		foreach($chartData as $k => $v) $chartData2[intval(mb_substr($k, 8))] = $v;
		
		$this->template->chartData = $chartData2;
		
		$this->template->totalRevenue = $this->profile->getTotalRevenue($this->getSince(), $this->getUntil());
		
		$this->template->totalCount = 0;
		foreach($dailyOrders as $dailyData) $this->template->totalCount += $dailyData->value;;		
		
		$this->template->avgOrderValue = $this->template->totalCount ? $this->template->totalRevenue / $this->template->totalCount : 0;
	}
	
	protected function createComponentProductGrid($name) {
		$grid = new vManager\Grid($this, $name);
		//$grid->setTemplateFile(__DIR__ . '/../Templates/Gridito/productGrid.latte');
		
		$data = $this->profile->getProductSellings($this->getSince(), $this->getUntil());
		$model = new vManager\Grid\ArrayModel($data);

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
	
	// </editor-fold>
	
}