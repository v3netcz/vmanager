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

use vManager, Nette;

/**
 * Sign in/out presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 4, 2011
 */
class SignPresenter extends BasePresenter {

	/**
	 * Sign in form component factory.
	 * @return Nette\Application\AppForm
	 */
	protected function createComponentSignInForm() {
		$form = new Nette\Application\AppForm;
	
		$form->addHidden('backlink', $this->getParam('backlink'));
		
		$form->addText('username', 'Username:')
				  ->setRequired('Please provide a username.');

		$form->addPassword('password', 'Password:')
				  ->setRequired('Please provide a password.');

		$form->addCheckbox('remember', 'Auto-login in future.');

		$form->addSubmit('send', 'Sign in');

		$form->onSubmit[] = callback($this, 'signInFormSubmitted');
		return $form;
	}

	public function signInFormSubmitted($form) {
		try {
			$values = $form->getValues();
			if($values->remember) {
				$this->getUser()->setExpiration('+ 14 days', FALSE);
			} else {
				$this->getUser()->setExpiration('+ 20 minutes', TRUE);
			}
			$this->getUser()->login($values->username, $values->password);
						
			$this->getPresenter()->getApplication()->restoreRequest($values->backlink);
			$this->redirect('Homepage:');
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function actionOut() {
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('in');
	}

}
