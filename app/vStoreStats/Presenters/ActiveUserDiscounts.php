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
 * Presenter of active user discounts
 *
 * @author Adam Staněk (V3lbloud)
 * @since Nov 9, 2012
 */
class ActiveUserDiscountsPresenter extends MonthlyPresenter {

	/** @persistent */
	public $month;
	
	private $_data;
	
	public function actionDefault() {
		$this->setupParams();
		
		try {
			$this->_data = $this->profile->getActiveUserDiscounts($this->getSince(), $this->getUntil());
			
		} catch(Nette\MemberAccessException $e) {
	
		}
	}
	
	public function renderDefault() {		
		$this->template->enabled = $this->_data !== NULL;
	}
	
	protected function createComponentUserDiscountsGrid($name) {
		$grid = new vManager\Grid($this, $name);

		$model = new vManager\Grid\ArrayModel($this->_data);

		$grid->setExport(TRUE);
		$grid->setModel($model);
		$grid->setItemsPerPage(20);
		$grid->sortColumn = $grid->sortColumn ?: 'surname';
		$grid->sortType = $grid->sortType ?: 'asc';

		$grid->addColumn("id", __("User ID"));
		$grid->addColumn("name", __("Name"));
		$grid->addColumn("surname", __("Surname"));
		
		$grid->addColumn("percentageDiscount", __("Percentage discount"), array(
			 "renderer" => function ($row) {
			 	echo $row->percentageDiscount . " %";
			 }
		))->setCellClass('amount');
		
		$grid->addColumn("until", __("Until"), array(
			 "renderer" => function ($row) {
			 	echo $row->until->format('j. n. Y');
			 }
		));
	}
	
}