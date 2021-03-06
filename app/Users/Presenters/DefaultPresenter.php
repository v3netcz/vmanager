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

use vManager,
	 vBuilder,
	 Nette,
	 Gridito,
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

		$lastLoginInfo = $this->context->database->connection->query("SELECT * FROM [security_lastLoginInfo]")->fetchAssoc('userId');

		$ds = $this->context->repository->findAll('vManager\Security\User');
		// $ds->leftJoin('[security_lastLoginInfo]')->on('[userId] = [id]');

		$model = new Gridito\DibiFluentModel($ds, 'vManager\Security\User');
		$model->setPrimaryKey('id');

		$grid->setModel($model);
		$grid->setItemsPerPage(10);

		// columns
		$grid->addColumn("id", "ID")->setSortable(true);
		$grid->addColumn("username", __('Username'))->setSortable(true);
		$grid->addColumn("surname", __("Name"), array(
			"renderer" => function ($row) {
				echo Nette\Utils\Html::el('span', $row->surname)->class("surname");
				if($row->surname != "" && $row->name != "") echo ", ";
				echo Nette\Templating\Helpers::escapeHtml($row->name);
			},
			 "sortable" => true,
		));

		$grid->addColumn("email", __("E-mail"), array(
			 "renderer" => function ($row) {
			 	 if($row->email == "") {
			 	 	echo "<div class=\"na\">-</div>";
			 	 	return ;
			 	 }

				 echo Nette\Utils\Html::el("a")->href("mailto:$row->email")->setText($row->email);
			 },
			 "sortable" => true,
		));

		$grid->addColumn("roles", __("User groups"), array(
			 "renderer" => function ($row) {
				 $roles = count($row->roles) > 1 ? array_diff($row->roles, array('User')) : $row->roles;
				 echo Nette\Templating\Helpers::escapeHtml(implode($roles, ', '));
			 },
			 "sortable" => false,
		));

		$grid->addColumn("lastLogin", __("Recently logged in"), array(
			"renderer" => function ($row) use ($lastLoginInfo) {
				if(isset($lastLoginInfo[$row->id])) {
					$time = $lastLoginInfo[$row->id]->time;
					echo Nette\Utils\Html::el("abbr")->title($time->format("j. n. Y H:i:s"))->setText(vManager\Application\Helpers::timeAgoInWords($time));

				} else
					echo "<div class=\"na\">-</div>";
			},
			"sortable" => false,
		))->setCellClass('lastLogin');;

		$grid->addButton("btnEdit", __('Edit'), array(					  
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					Nette\Environment::getApplication()->getPresenter()->redirect('Edit:editUser', $row->id);
				}

				$grid->redirect("this");
			}
		));
			 
		$grid->addButton("btnRemove", __('Remove'), array(
			"class" => "button_orange",
			"confirmationQuestion" => function ($row) use ($grid) {
				return _x('Are you sure you want to remove user %s?', array($row->username));
			},
					  
			"handler" => function ($row) use ($grid) {
				if(!$row) Nette\Environment::getApplication()->getPresenter()->flashMessage(__('Record not found'), 'warn');
				else {
					Helpers::deleteUserAvatar($row->id);
					$row->delete();
					Nette\Environment::getApplication()->getPresenter()->flashMessage(_x("User %s has been removed.", array($row->username)));
				}

				$grid->redirect("this");
			}
		));
	}

}
