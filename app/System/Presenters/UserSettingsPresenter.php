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
	 vManager\MultipleFileUploadControl,
	 vManager\Form;

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
	 * @return Form
	 */
	protected function createComponentUserProfileForm($name) {
		$user = $this->user->identity;

		$form = new Form($this, $name);

		$form->addText('salutation', __('Salutation: '))
				  ->addRule(Form::FILLED, __('Salutation cannot be empty.'))
				  ->setValue($user->getSalutation());

		$form->addText('name', __('Name:'))
				  ->addRule(Form::FILLED, __('Name cannot be empty.'))
				  ->setDefaultValue($user->name);
		$form->addText('surname', __('Surname:'))
				  ->addRule(Form::FILLED, __('Surname cannot be empty.'))
				  ->setDefaultValue($user->surname);
		$form->addText('username', __('Username:'))
				  ->setDefaultValue($user->username)
				  ->addRule(Form::FILLED, __('Username cannot be empty.'))
				  ->addRule(Form::REGEXP, __('Username have to contain alpha-numeric chars only (with exception for chars ._@-). Nor spaces or diacritic chars are allowed.'), '/^[A-Z0-9\\.\\-_@]+$/i')
				  ->addFilter(function ($value) {
					  return Nette\Utils\Strings::lower($value);
				  })
				  ->addRule(function ($control) {
					  $user = Nette\Environment::getUser()->getIdentity();
					  
					  if($control->value == $user->username) return true;
					
					  $users = Repository::findAll('vBuilder\Security\User')->where('[username] = %s', $control->value)->fetchSingle();
					  return ($users === false);
				  }, __('Desired username is already taken. Please use something else.'));
				  
		$form->addText('email', 'E-mail:')
				  ->setDefaultValue($user->email)
				  ->addRule(Form::EMAIL, __('E-mail is not valid'));
		
		$form->addMultipleFileUpload('avatar', __('Select your avatar'))
				->addRule(MultipleFileUploadControl::VALID, __('You may only upload images!'), array (
					'jpg' => 'image/jpg',
					'jpg' => 'image/jpeg',
					'JPG' => 'image/jpeg',
					'png' => 'image/png',
					'gif' => 'image/gif'
				))
				->addRule(MultipleFileUploadControl::FILES_COUNT, __('You can upload one file only'), 1);
		

		$form->addSubmit('send', __('Save'));

		$form->onSuccess[] = callback($this, 'userProfileFormSubmitted');
	}

	/**
	 * User settings form subbmited action handler
	 *
	 * @param Form
	 */
	public function userProfileFormSubmitted($form) {
		//try {
			$values = $form->getValues();
			$user = $this->user->identity;

			$user->name = $values->name;
			$user->surname = $values->surname;
			$user->username = $values->username;
			$user->email = $values->email;
			
			// Nechci ukladat defaultni osloveni, protoze zavisi na prekladu			
			if($values->salutation != $user->getSalutation()) {
				$config = $this->context->config;
				$config->set('system.salutation', $values->salutation); 
				$config->save();
			}
			$user->save();
			
			$avatar = $values->avatar;
			if (count($avatar)) { // ArrayHash doesn't support empty() :-P
				$uid = $user->id;
				$avatar = $avatar[0];
				// The user may be using a jpg avatar and upload a gif, for instance.
				// The $user->identity->getAvatarUrl() method, however, favores jpg's
				// so we have to delete any pictures that are there. Nette\Finder unfortunately
				// doesn't really help us here.
				$exts = array ('jpg','png','gif');

				// the file saver will overwrite this one
				unset($exts[array_search($avatar->extension, $exts)]);
				foreach ($exts as $ext) {
					$file = FILES_DIR.'/avatars/'.$uid.'.'.$ext;
					if (file_exists($file))
						unlink($file);
				}
				$avatar->setFilename($uid);
				$path = FILES_DIR.$avatar->save('/avatars/');
				$img = Nette\Image::fromFile($path);
				$img->resize(122, 122);
				$img->save($path);
			}
			$this->flashMessage(__('All changes are saved.'));
			$this->redirect('default');
		/*} catch(\Exception $e) {
			$form->addError($e->getMessage());
		} */
	}
	
	/**
	 * Environment settings form component factory.
	 *
	 * @return Form
	 */
	protected function createComponentEnvironmentForm() {
		$user = Nette\Environment::getUser()->getIdentity();

		$form = new Form;

		$selLanguages = array(null => __('Auto'));
		$languages = (array) Nette\Environment::getConfig('languages', array('en'));
		foreach($languages as $curr) {
			$selLanguages[$curr] = $curr;
		}
		
		$config = $this->context->config;
		$lang = $config->get('system.language'); 
		
		$form->addSelect('language', __('Language:'), $selLanguages)->setValue($lang);
		
		$form->addSubmit('send', __('Save'));

		$form->onSuccess[] = callback($this, 'environmentFormSubmitted');
		return $form;
	}
	
	public function environmentFormSubmitted($form) {
		$values = $form->getValues();
		$config = $this->context->config;
		$config->set('system.language', empty($values->language) ? null : $values->language); 
		$config->save();

		/* 
		$this->flashMessage(__('All changes are saved.'));
		$this->redirect('default'); */
		
		$this->redirect('changeSaved');
	}
	
	public function actionChangeSaved() {
		$this->flashMessage(__('All changes are saved.'));
		$this->redirect('default');
	}

	/**
	 * User profile form component factory.
	 *
	 * @return Form
	 */
	protected function createComponentUserPasswordForm() {
		$user = Nette\Environment::getUser()->getIdentity();

		$form = new Form;

		$form->addPassword('oldPassword', __('Old password:'))
				  ->addRule(function ($control) {
								 $user = Nette\Environment::getUser()->getIdentity();
								 return isset($user) && $user->checkPassword($control->value);
							 }, __('Invalid password. Access denied.'));

		$form->addPassword('password', __('New password:'))
			->addRule(Form::MIN_LENGTH, __('Password have to be at least 6 chars long.'), 6)
			->addRule(Form::FILLED, __('Please provide password.'));
		
		$form->addPassword('password2', __('Confirm new password:'))
				  ->addRule(Form::EQUAL, __('Confirmation password have to be the same as password.'), $form['password']);

		$form->addSubmit('send', __('Change password'));

		$form->onSuccess[] = callback($this, 'userPasswordFormSubmitted');
		return $form;
	}

	/**
	 * User settings form subbmited action handler
	 *
	 * @param Form
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