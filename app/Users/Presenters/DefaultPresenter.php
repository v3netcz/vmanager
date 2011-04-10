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

use vManager, vBuilder, Nette;

/**
 * Default presenter of users module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class DefaultPresenter extends vManager\Modules\System\SecuredPresenter {
	
	/**
	 * Sign in form component factory.
	 * @return Nette\Application\AppForm
	 */
	protected function createComponentUserForm() {
		$form = new Nette\Application\AppForm;
	
		$form->addText('username', 'Username:')
				  ->setRequired('Please provide a username.');

		$form->addPassword('password', 'Password:')
				  ->setRequired('Please provide a password.');

		$form->addSubmit('send', 'Create user');

		$form->onSubmit[] = callback($this, 'userFormSubmitted');
		return $form;
	}

	public function userFormSubmitted($form) {
		$values = $form->getValues();
		
		$user = new vBuilder\Security\User;
		$user->username = $values['username'];
		$user->password = $values['password'];
		$user->save();
		
		$this->flashMessage('Uživatel č. ' . $user->id . ' byl úspěšně vytvořen');
	}
	
}
