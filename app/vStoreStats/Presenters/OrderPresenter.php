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
 * Presenter of order statistics
 *
 * @author Adam Staněk (V3lbloud)
 * @since Sep 12, 2012
 */
class OrderPresenter extends MonthlyPresenter {

	/** @persistent */
	public $month;
	
	private $_totalClasses;
	
	public function actionDefault() {
		$this->setupParams();
	}
	
	public function renderDefault() {		

	}
	
	protected function createComponentTotalClassesGrid($name) {
		$grid = new vManager\Grid($this, $name);

		$data = $this->getTotalClasses();
		$model = new vManager\Grid\ArrayModel($data);

		$grid->setModel($model);
		$grid->setItemsPerPage(20);
		$grid->sortColumn = $grid->sortColumn ?: 'min';
		$grid->sortType = $grid->sortType ?: 'asc';

		$grid->addColumn("min", __("Range"), array(
			 "renderer" => function ($row) {
				 if($row->min <= 0) echo "< " . $row->max;
				 elseif($row->max === NULL) echo ">= " . $row->min;
				 else echo $row->min . " - " . ($row->max - 1);
			 },
			 "sortable" => true,
		));

		$grid->addColumn("numOrders", __("Number of orders"))->setSortable(true)->setCellClass('amount');
	}
	
	protected function getTotalClasses() {
		if(!isset($this->_totalClasses)) {
			$this->_totalClasses = array();
			$data = $this->profile->getTotalClasses($this->getSince(), $this->getUntil());
			
			$sum = array_reduce($data, function ($tmp, $class) {
				$tmp += $class->numOrders;
				return $tmp;
			}, 0);
			
			$minorClassSum = 0;
			
			for($i = count($data) - 1; $i >= 0; $i--) {
				if(count($this->_totalClasses)) {
					array_unshift($this->_totalClasses, $data[$i]);
				}
				
				elseif($data[$i]->numOrders < $sum * 0.01) {
					$minorClassSum += $data[$i]->numOrders;
				} else {
					$obj = new \StdClass;
					$obj->min = $data[$i]->max;
					$obj->max = NULL;
					$obj->numOrders = $minorClassSum;
					
					$this->_totalClasses[] = $data[$i];
					$this->_totalClasses[] = $obj;
				}
			}
		}
		
		return $this->_totalClasses;
	}
	
}