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
	 vBuilder\Orm\Repository,
	 Nette,
	 Nette\Application\AppForm;

/**
 * Presenter for creating and editing users
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class EditPresenter extends vManager\Modules\System\SecuredPresenter {

	/**
	 * Sign in form component factory.
	 * @return Nette\Application\AppForm
	 */
	protected function createComponentUserForm() {
		$form = new Nette\Application\AppForm;
		$form->setRenderer(new vManager\Application\DefaultFormRenderer());

		$form->addText('name', __('Name:'))
				  ->addRule(AppForm::FILLED, __('Name cannot be empty.'));

		$form->addText('surname', __('Surname:'))
				  ->addRule(AppForm::FILLED, __('Surname cannot be empty.'));

		$form->addText('username', __('Username:'))
				  ->addRule(AppForm::FILLED, __('Username cannot be empty.'))
				  ->addRule(AppForm::REGEXP, __('Username have to contain alpha-numeric chars only (with exception for chars ._@-). Nor spaces or diacritic chars are allowed.'), '/^[A-Z0-9\\.\\-_@]+$/i')
				  ->addFilter(function ($value) {
					  return Nette\String::lower($value);
				  })
				  ->addRule(function ($control) {
								 $users = Repository::findAll('vBuilder\Security\User')->where('[username] = %s', $control->value)->fetchSingle();
								 return ($users === false);
							 }, __('Desired username is already taken. Please use something else.'));

		$form->addText('email', 'E-mail:')
				  ->addRule(AppForm::EMAIL, __('E-mail is not valid'));

		$form->addPassword('password', __('Password:'))
				  ->addRule(AppForm::MIN_LENGTH, __('Password have to be at least 6 chars long.'), 6)
				  ->addRule(AppForm::FILLED, __('Please provide password.'));

		$form->addPassword('password2', __('Confirm password:'))
				  ->addRule(AppForm::EQUAL, __('Confirmation password have to be the same as password.'), $form['password']);

		foreach(Nette\Environment::getUser()->getAuthorizationHandler()->getAllRegistredRoles() as $role)
			$form->addCheckbox('grp'.Nette\String::replace($role, '/\\s/', ''), $role);

		$form->addSubmit('send', __('Create user'));

		$form->onSubmit[] = callback($this, 'userFormSubmitted');
		return $form;
	}

	public function userFormSubmitted($form) {
		$values = $form->getValues();

		$user = new vManager\Security\User;
		foreach($values as $key => $value) {
			if(isset($user->$key))
				$user->$key = $value;
		}

		$roles = array();
		foreach(Nette\Environment::getUser()->getAuthorizationHandler()->getAllRegistredRoles() as $role)
			if($values['grp'.Nette\String::replace($role, '/\\s/', '')])
				$roles[] = $role;

		$user->setRoles($roles);

		$user->save();

		$this->flashMessage(_x('User n. %d has been successfuly created.', array($user->id)));
	}

}
