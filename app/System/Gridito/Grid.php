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

namespace vManager;

use vManager,
	Nette,
	Gridito,
	vBuilder,
	vBuilder\Utils\Csv;

/**
 * Extended Gridito implementation
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class Grid extends Gridito\Grid {

	/** @var string template file path */
	private $tplFile;

	/** @var bool */
	private $allowExports = false;

	public function handleExportToExcelCsv() {

		$data = array();
		$this->model->setOffset(0);
		$this->model->setLimit($this->model->count());
		
		foreach($this->model->getItems() as $record) {
			$line = array();
			foreach($this["columns"]->getComponents() as $key=>$column) {
				ob_start();
				$column->renderCell($record);
				$line[] = strip_tags(ob_get_clean());
			}

			$data[] = $line;
		}


		$data = Csv::fromData($data, "\t", "\n");
		$data = iconv('UTF-8', 'UTF-16', $data);
		// $data = chr(255) . chr(254) . $data;

		$filename = $this->getName(). "-" .date("Y-m-d") . ".csv";

		$httpResponse = $this->getPresenter()->getContext()->httpResponse;
		$httpResponse->setHeader('Content-Length', strlen($data));
		$httpResponse->setContentType('text/csv');
		$httpResponse->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');

		$response = new Nette\Application\Responses\TextResponse($data);
		$this->getPresenter()->sendResponse($response);
	}

	/**
	 * Sets absolute filename for template to render
	 * 
	 * @param string file apth
	 */
	public function setTemplateFile($filepath) {
		$this->tplFile = $filepath;
	}

	/**
	 * Create template
	 * @return Template
	 */
	protected function createTemplate($class = NULL) {
		$tpl = parent::createTemplate()->setFile(isset($this->tplFile) ? $this->tplFile
								 : __DIR__."/Templates/grid.latte");

		$tpl->allowExports = $this->allowExports;

		return $tpl;
	}
	
	public function getOrderedColumns() {
		$it = $this['columns']->getComponents();
				
		$it2 = new vBuilder\Utils\SortingIterator($it, function ($item1, $item2) {
			return $item1->getOrderColumnWeight() >= $item2->getOrderColumnWeight();
		});

	
		return $it2;
	}
	
	public function addColumn($name, $label = null, array $options = array()) {
		if(!isset($options['orderColumnWeight'])) $options['orderColumnWeight'] = count($this['columns']->getComponents());
	
		return parent::addColumn($name, $label, $options);
	}

	public function setExport($allowExports) {
		$this->allowExports = $allowExports;
	}

	public function isExportEnabled() {
		return $this->allowExports;
	}

}
