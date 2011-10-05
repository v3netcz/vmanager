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
	 vManager\Form;

/**
 * Presenter for creating and editing users
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class EditPresenter extends vManager\Modules\System\SecuredPresenter {

	/** @var vManager\Security\User instance for user editing */
	private $user;
	
	public function actionEditUser($id) {
		$this->user = $this->context->repository->get('vManager\Security\User', $id);
		if(!$this->user->exists()) throw new \InvalidArgumentException('User not found');
	}
	
	/**
	 * Edit user form
	 * 
	 * @param int $id 
	 */
	public function renderEditUser($id) {				
		$form = $this['userForm'];
		$form->loadFromEntity($this->user);
		
		foreach($this->user->roles as $role) {
			$key = 'grp'.Nette\Utils\Strings::replace($role, '/\\s/', '');
			if(!isset($form[$key])) continue;
			
			$form[$key]->setDefaultValue(true);
		}
	}
	
	/**
	 * Sign in form component factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentUserForm() {
		$form = new Form;

		$repository = $this->context->repository;
		$action = $this->action;
		$editedUser = &$this->user;
		
		$form->addText('name', __('Name:'))
				  ->addRule(Form::FILLED, __('Name cannot be empty.'));

		$form->addText('surname', __('Surname:'))
				  ->addRule(Form::FILLED, __('Surname cannot be empty.'));

		$form->addText('username', __('Username:'))
				  ->addRule(Form::FILLED, __('Username cannot be empty.'))
				  ->addRule(Form::REGEXP, __('Username have to contain alpha-numeric chars only (with exception for chars ._@-). Nor spaces or diacritic chars are allowed.'), '/^[A-Z0-9\\.\\-_@]+$/i')
				  ->addFilter(function ($value) {
					  return Nette\Utils\Strings::lower($value);
				  })
				  ->addRule(function ($control) use ($action, $editedUser, $repository) {
								 if($action == 'editUser' && $control->value == $editedUser->username) return true;
						
								 $users = $repository->findAll('vBuilder\Security\User')->where('[username] = %s', $control->value)->fetchSingle();
								 return ($users === false);
							 }, __('Desired username is already taken. Please use something else.'));

		$form->addText('email', 'E-mail:')
				  ->addRule(Form::EMAIL, __('E-mail is not valid'));

		// TODO: Vyclenit to do specialniho formulare pri editu
		if($this->action != 'editUser') {
			$form->addPassword('password', __('Password:'))
					  ->addRule(Form::MIN_LENGTH, __('Password have to be at least 6 chars long.'), 6)
					  ->addRule(Form::FILLED, __('Please provide password.'));

			$form->addPassword('password2', __('Confirm password:'))
					  ->addRule(Form::EQUAL, __('Confirmation password have to be the same as password.'), $form['password']);
		}

		foreach($this->context->authorizator->getAllRegistredRoles() as $role)
			$form->addCheckbox('grp'.Nette\Utils\Strings::replace($role, '/\\s/', ''), $role);
		

		$form->addSubmit('send', __('Create user'));

		$form->onSuccess[] = callback($this, 'userFormSubmitted');
		return $form;
	}

	public function userFormSubmitted($form) {
		$values = $form->getValues();
		$user = $this->action == 'editUser' ? $this->user : $this->context->repository->create('vManager\Security\User');
		
		
		foreach($values as $key => $value) {
			if(isset($user->$key))
				$user->$key = $value;
		}

		$roles = array();
		foreach($this->context->authorizator->getAllRegistredRoles() as $role)
			if($values['grp'.Nette\Utils\Strings::replace($role, '/\\s/', '')])
				$roles[] = $role;

		$user->setRoles($roles);

		$user->save();

		$this->flashMessage(_x($this->action == 'editUser' ? 'Profile for user n. %d has been sucessfuly saved.' : 'User n. %d has been successfuly created.', array($user->id)));
	}

}
