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
		$ds = $this->connection->select('COUNT(*) AS [value], DATE_FORMAT([timestamp], \'%Y-%m-%d\') AS [date]')->from('shop_orders')
				// ->where('[state] = 1')
				->where('[timestamp] >= %s', $this->getSince()->format('Y-m-d'))
				->and('[timestamp] <= %s', $this->getUntil()->format('Y-m-d'))
				->groupBy('[date]')->fetchAll();
	
		$this->template->chartData = $this->createDateArray($ds);
	}
	
	protected function createComponentProductGrid($name) {
		$grid = new vManager\Grid($this, $name);
		//$grid->setTemplateFile(__DIR__ . '/../Templates/Gridito/productGrid.latte');
		
		$ds = $this->connection
				->select('SUM([amount]) amount, SUM([amount]*[price]) revenue, [productId], [name]')
				->from('shop_orderItems')
				->where('SUBSTRING([orderId], 1, 6) = %s', $this->getSince()->format('Ym'))
				->and('[productId] > 0')
				->groupBy('[productId]');
		
		$model = new \Gridito\DibiFluentModel($ds);

		$grid->setModel($model);
		$grid->setItemsPerPage(20);
		$grid->sortColumn = 'revenue';
		$grid->sortType = 'desc';

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
	
	protected function getMonthsGatherer() {
		$d = $this->connection->query(
			"SELECT DISTINCT DATE_FORMAT(`timestamp`, '%Y-%m') AS `m` FROM [shop_orders]"
		)->fetchAll();
	
		$m = array();
		foreach($d as $curr) $m[] = $curr->m;
		return $m;
	}
	
}