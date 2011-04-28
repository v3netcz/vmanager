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

namespace vManager\Modules\Users;

use vManager, vBuilder, Nette, Gridito,
	 vBuilder\Orm\Repository;

/**
 * Default presenter of users module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class DefaultPresenter extends vManager\Modules\System\SecuredPresenter {
	
	public function renderDefault() {
		
	}
	
	protected function createComponentUserGrid($name) {
		$grid = new vManager\Grid($this, $name);

		$grid->setModel(new Gridito\DibiFluentModel(Repository::findAll('vManager\Security\User')));
		$grid->setItemsPerPage(10);
		
		// columns
		$grid->addColumn("id", "ID")->setSortable(true);
		$grid->addColumn("username", __('Username'))->setSortable(true);
		$grid->addColumn("name", __("Name"))->setSortable(true);
		$grid->addColumn("surname", __("Surname"))->setSortable(true);
		$grid->addColumn("email", __("E-mail"), array(
			 "renderer" => function ($row) {
				 echo Nette\Web\Html::el("a")->href("mailto:$row->email")->setText($row->email);
			 },
			 "sortable" => true,
		));
			 
		/*
		$grid->addColumn("roles", __("User groups"), array(
			 "renderer" => function ($row) {
				echo count($row->roles) > 1 ? array_diff($row->roles, array('User')) : $row->roles;
			 },
			 "sortable" => true,
		)); */
	}
	
}
