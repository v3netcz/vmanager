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

use vManager, vBuilder,
	 Nette,
	 vManager\Mailer,
	 vBuilder\Orm\Repository,
	 Nette\Application\UI\Form,
	 PavelMaca\Captcha\CaptchaControl;

/**
 * Sign in/out presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 4, 2011
 */
class SignPresenter extends BasePresenter {

	/**
	 * Sign in form component factory.
	 * @return Form
	 */
	protected function createComponentSignInForm() {
		$form = new Form;

		$form->addHidden('backlink', $this->getParam('backlink'));

		$form->addText('username', __('Username:'))
				  ->setRequired(__('Please provide a username.'));

		$form->addPassword('password', __('Password:'))
				  ->setRequired(__('Please provide a password.'));

		$form->addCheckbox('remember', __('Auto-login in future.'));

		$form->addSubmit('send', __('Sign in'));

		$form->onSuccess[] = callback($this, 'signInFormSubmitted');

		$request = $this->getHttpRequest();
		$value = $request->getCookie('vManagerLastLoggedUser');
		if($value !== NULL) {
			$form['username']->setValue($value);
			$form['password']->setAttribute('class', 'focus');
			$form['username']->setAttribute('class', '');
		} else {
			$form['password']->setAttribute('class', '');
			$form['username']->setAttribute('class', 'focus');
		}

		return $form;
	}

	/**
	 * Password reset form component factory.
	 * @return Form
	 */
	protected function createComponentPwdResetForm() {
		$form = new Form;

		$form->addHidden('backlink', $this->getParam('backlink'));

		$form->addText('username', __('Username:'));

		if(($username = $this->getHttpRequest()->getCookie('vManagerLastLoggedUser')) !== NULL)
			$form['username']->setValue($username);
		
		$form->addText('email', __('E-mail:'))
				  ->setEmptyValue('@')
				  ->addCondition(Form::FILLED)
				  ->addRule(Form::EMAIL, __('E-mail is not valid'));

		$captcha = new CaptchaControl();
		$form['captcha'] = $captcha;
		$form['captcha']->caption = (__('Security code:'));
		$form['captcha']->setTextColor(\Nette\Image::rgb(48, 48, 48));
		$form['captcha']->setBackgroundColor(\Nette\Image::rgb(232, 234, 236));
		$form['captcha']->addRule(Nette\Forms\Form::FILLED, __('Rewrite text from image.'));
		$form['captcha']->addRule($form["captcha"]->getValidator(), __('Security code is incorrect. Read it carefuly from image above.'));

		$form['username']
				  ->addConditionOn($form['email'], Form::EQUAL, '')
				  ->addRule(Form::FILLED, __('Please provide your username or e-mail.'));
		$form['email']
				  ->addConditionOn($form['username'], Form::EQUAL, '')
				  ->addRule(Form::FILLED, __('Please provide your username or e-mail.'));
		
		$form->addSubmit('back', 'Back');
		$form->addSubmit('send', __('Send new password'));

		$form->onSuccess[] = callback($this, 'pwdResetFormSubmitted');
		return $form;
	}

	/**
	 * Sign in form subbmited action handler
	 *
	 * @param Form
	 */
	public function signInFormSubmitted($form) {
		try {
			$values = $form->getValues();
			if($values->remember) {
				$this->getUser()->setExpiration('+ 14 days', FALSE);
			} else {
				$this->getUser()->setExpiration('+ 20 minutes', TRUE);
			}
			$this->getUser()->login($values->username, $values->password);

			$this->getHttpResponse()->setCookie('vManagerLastLoggedUser', $values->username, time() + 365 * 24 * 60 * 60);

			if($values->backlink) {
				$this->getPresenter()->getApplication()->restoreRequest($values->backlink);
			} else {
				$u = $this->getUser()->getIdentity();
				
				$config = $this->context->config;
				$userLang = $config->get('system.language');
				if($userLang !== null) Nette\Environment::getService('Nette\ITranslator')->setLang($userLang);
				
				if($u->getLastLoginInfo()->exists() && $u->getLastLoginInfo()->getTime() !== null) {
					$host = gethostbyaddr($u->getLastLoginInfo()->getIp());
					if($host != $u->getLastLoginInfo()->getIp()) $host .= ' ('.$u->getLastLoginInfo()->getIp().')';
					
					$this->flashMessage(_x('%s. Welcome back. Last time you logged in %s from %s.', array(
						 $u->getSalutation(), 
						 vManager\Application\Helpers::timeAgoInWords($u->getLastLoginInfo()->getTime()),
						 /*$u->getLastLoginInfo()->getTime()->format('d.m.Y'),
						 $u->getLastLoginInfo()->getTime()->format('h:i:s'), */
						 $host
					)));
				} else {
					$this->flashMessage(_x('%s. Since this is the first time you have logged in it is recommended to change your password.', array(
						 $u->getSalutation()
					)));
				}
			}
			
			$this->redirect('Homepage:');
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	/**
	 * Reset password form subbmited action handler
	 *
	 * @param Form
	 */
	public function pwdResetFormSubmitted($form) {
		try {
			$values = $form->getValues();
			$username = $values->username;
			$email = $values->email;
			$newPassword = $this->generatePwd(8);

			if(!isset($email) || $email == '') {
				$user = $this->context->repository->findAll('vManager\Security\User')->where('[username] = %s', $username)->fetch();
			} else if(!isset($username) || $username == '') {
				$user = $this->context->repository->findAll('vManager\Security\User')->where('[email] = %s', $email)->fetch();
			} else {
				$form->addError(__('Please provide your username or e-mail.'));
				return;
			}

			if($user != false && $user->email != '') {
				$user->setPassword($newPassword);

				$tpl = Mailer::createMailTemplate(__DIR__ . '/../Templates/Emails/pwdReset.latte');
				$tpl->username = $user->username;
				$tpl->newPassword = $newPassword;
		
				$mail = Mailer::createMail();
				$mail->setSubject(__('vManager - new password'));
				$mail->addTo($user->email);
				$mail->setHtmlBody($tpl);

				Mailer::getMailer()->send($mail);
				
				$user->save();

				$this->flashMessage(__('A new password has been sent to your e-mail address.'));
				$this->redirect('Sign:in');
			} else {
				$form->addError(__('User not found.'));
			}
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	/**
	 * Sign out action handler
	 *
	 */
	public function actionOut() {
		$this->getUser()->logout();
		$this->flashMessage(__('You have been signed out.'));
		$this->redirect('in');
	}

	/**
	 * Function for generating password
	 *
	 * @param $lngth password length
	 * @return string password
	 */
	public function generatePwd($length = 8) {
		$password = "";
		$possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";

		$maxlength = strlen($possible);

		if($length > $maxlength) {
			$length = $maxlength;
		}
		$i = 0;
		while($i < $length) {
			$char = substr($possible, mt_rand(0, $maxlength - 1), 1);

			if(!strstr($password, $char)) {
				$password .= $char;
				$i++;
			}
		}

		return $password;
	}

}
