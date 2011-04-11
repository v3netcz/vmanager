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

use vManager, Nette,
  vBuilder\Orm\Repository,
  Nette\Application\AppForm;

/**
 * Sign in/out presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 10, 2011
 */
class UserPresenter extends SecuredPresenter {

	/**
	 * User settings form component factory.
   *
	 * @return AppForm
	 */
	protected function createComponentUserSettingsForm() {
    $user = Nette\Environment::getUser()->getIdentity();    
    
    $form = new AppForm;
    // $form->addFile('icon', 'Avatar:'); TODO: pridat podporu v db
    $form->addText('name', 'Name:')
      ->setValue($user->name);
    $form->addText('surname', 'Surname:')
      ->setValue($user->surname);
    $form->addText('username', 'Username:')
      ->setValue($user->username);
		$form->addText('email', 'E-mail:')
      ->setValue($user->email)
      ->addCondition(AppForm::FILLED)
        ->addRule(AppForm::EMAIL, 'E-mail is not valid');
    $form->addPassword('password', 'New password:');
    $form->addPassword('password2', 'Confirm new password:');

    $form['password2']
      ->addConditionOn($form['password'], AppForm::FILLED)
        ->addRule(AppForm::EQUAL, 'Confirmation password didnt match.', $form['password']);

    $form->addSubmit('send', 'Save');

		$form->onSubmit[] = callback($this, 'userSettingsFormSubmitted');
		return $form;
  }


  /**
	 * User settings form subbmited action handler
	 *
   * @param AppForm
	 */
  public function userSettingsFormSubmitted($form) {
		try {
			$values = $form->getValues();
      $user = Nette\Environment::getUser()->getIdentity();

      $user->name = $values->name;
      $user->surname = $values->surname;
      $user->username = $values->username;           
      $user->email = $values->email;
      
      if ($values->password != '') {
        $user->setPassword($values->password);
      }

      $user->save();
      $this->flashMessage('All changes are saved.');
      $this->redirect('Homepage:');
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
  }
}