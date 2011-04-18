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
	 Nette,
	 vBuilder\Orm\Repository,
	 Nette\Application\AppForm;

/**
 * Sign in/out presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 10, 2011
 */
class UserSettingsPresenter extends SecuredPresenter {

	/**
	 * User profile form component factory.
	 *
	 * @return AppForm
	 */
	protected function createComponentUserProfileForm() {
		$user = Nette\Environment::getUser()->getIdentity();

		$form = new AppForm;
		$form->setRenderer(new vManager\Application\DefaultFormRenderer());
		// $form->addFile('icon', 'Avatar:'); TODO: pridat podporu v db
		$form->addText('name', __('Name:'))
				  ->addRule(AppForm::FILLED, __('Name cannot be empty.'))
				  ->setValue($user->name);
		$form->addText('surname', __('Surname:'))
				  ->addRule(AppForm::FILLED, __('Surname cannot be empty.'))
				  ->setValue($user->surname);
		$form->addText('username', __('Username:'))
				  ->setValue($user->username)
				  ->addRule(AppForm::FILLED, __('Username cannot be empty.'))
				  ->addRule(function ($control) {
					  $user = Nette\Environment::getUser()->getIdentity();
					  
					  if($control->value == $user->username) return true;
					
					  $users = Repository::findAll('vBuilder\Security\User')->where('[username] = %s', $control->value)->fetchSingle();
					  return ($users === false);
				  }, __('Desired username is already taken. Please use something else.'));
		
		$form->addText('email', 'E-mail:')
				  ->setValue($user->email)
				  ->addCondition(AppForm::FILLED)
				  ->addRule(AppForm::EMAIL, __('E-mail is not valid'));

		$form->addSubmit('send', __('Save'));

		$form->onSubmit[] = callback($this, 'userProfileFormSubmitted');
		return $form;
	}

	/**
	 * User settings form subbmited action handler
	 *
	 * @param AppForm
	 */
	public function userProfileFormSubmitted($form) {
		try {
			$values = $form->getValues();
			$user = Nette\Environment::getUser()->getIdentity();

			$user->name = $values->name;
			$user->surname = $values->surname;
			$user->username = $values->username;
			$user->email = $values->email;

			$user->save();
			$this->flashMessage(__('All changes are saved.'));
			$this->redirect('default');
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	/**
	 * User profile form component factory.
	 *
	 * @return AppForm
	 */
	protected function createComponentUserPasswordForm() {
		$user = Nette\Environment::getUser()->getIdentity();

		$form = new AppForm;
		$form->setRenderer(new vManager\Application\DefaultFormRenderer());

		$form->addPassword('oldPassword', __('Old password:'))
				  ->addRule(function ($control) {
								 $user = Nette\Environment::getUser()->getIdentity();
								 return isset($user) && $user->checkPassword($control->value);
							 }, __('Invalid password. Access denied.'));

		$form->addPassword('password', __('New password:'))
			->addRule(AppForm::FILLED, __('Please provide password.'));
		
		$form->addPassword('password2', __('Confirm new password:'))
				  ->addConditionOn($form['password'], AppForm::FILLED)
				  ->addRule(AppForm::EQUAL, __('Confirmation password have to be the same as password.'), $form['password']);

		$form->addSubmit('send', __('Change password'));

		$form->onSubmit[] = callback($this, 'userPasswordFormSubmitted');
		return $form;
	}

	/**
	 * User settings form subbmited action handler
	 *
	 * @param AppForm
	 */
	public function userPasswordFormSubmitted($form) {
		try {
			$values = $form->getValues();
			$user = Nette\Environment::getUser()->getIdentity();

			$user->password = $values->password;


			$user->save();
			$this->flashMessage(__('All changes are saved.'));
			$this->redirect('default');
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

}