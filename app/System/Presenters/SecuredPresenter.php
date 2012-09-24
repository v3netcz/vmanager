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

namespace vManager\Modules\System;

use vManager,
	 Nette;

/**
 * Base presenter for implementing secured pages. User
 * have to be logged in order to view page based on this presenter.
 * Otherwise he is redirected to login. 
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class SecuredPresenter extends BasePresenter {

	/**
	 * This method is called before any action is performed.
	 * The authorization logic is implemented in here.
	 */
	public function startup() {
		parent::startup();		
		
		$user = $this->getUser();

		if(!$user->isLoggedIn()) {
			if($user->getLogoutReason() === Nette\Security\User::INACTIVITY) {
				if ($this->isAjax()) {
					return; // ignore for ajax
				}
				$this->flashMessage(__('You have been signed off during long inactivity.'), 'warning');
			}

			$backlink = $this->getApplication()->storeRequest();
			$this->redirect(':System:Sign:in', array('backlink' => $backlink));
		} elseif(!$user->isAllowed($this->name, $this->action)) {	
			Nette\Diagnostics\Debugger::log('Access denied for UID:'. $user->getId().' when accessing resource "'. $this->name .'": "'. $this->action .'"', Nette\Diagnostics\Debugger::WARNING);
			$this->flashMessage(__('You don\'t have enough privileges to perform this action.'), 'warning');
			
			$backlink = $this->getApplication()->storeRequest();
			$this->redirect(':System:Sign:in', array('backlink' => $backlink));
		}
	}

}
