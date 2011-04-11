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
  Nette\Mail\Mail,
  Nette\Mail\SendmailMailer,
  vBuilder\Orm\Repository,
  Nette\Application\AppForm,
  Nette\Templates\FileTemplate,
  Nette\Templates\LatteFilter;

/**
 * Sign in/out presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 4, 2011
 */
class SignPresenter extends BasePresenter {

	/**
	 * Sign in form component factory.
	 * @return AppForm
	 */
	protected function createComponentSignInForm() {
		$form = new AppForm;
	
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

  /**
	 * Password reset form component factory.
	 * @return AppForm
	 */
  protected function createComponentPwdResetForm() {
		$form = new AppForm;

		$form->addHidden('backlink', $this->getParam('backlink'));

		$form->addText('username', 'Username:');
    
		$form->addText('email', 'E-mail:')
      ->setEmptyValue('@')
      ->addCondition(AppForm::FILLED)
        ->addRule(AppForm::EMAIL, 'E-mail is not valid');

    $form['username']
      ->addConditionOn($form['email'], AppForm::EQUAL, '')
        ->addRule(AppForm::FILLED, 'Please provide your username or e-mail.');
    $form['email']
      ->addConditionOn($form['username'], AppForm::EQUAL, '')
        ->addRule(AppForm::FILLED, 'Please provide your username or e-mail.');

    $form->addSubmit('back', 'Back');
		$form->addSubmit('send', 'Send new password');

		$form->onSubmit[] = callback($this, 'pwdResetFormSubmitted');
		return $form;
  }

  /**
	 * Sign in form subbmited action handler
	 *
   * @param AppForm
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
						
			$this->getPresenter()->getApplication()->restoreRequest($values->backlink);
			$this->redirect('Homepage:');
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

  /**
	 * Reset password form subbmited action handler
	 *
   * @param AppForm
	 */
  public function pwdResetFormSubmitted($form) {
		try {
			$values = $form->getValues();
      $username = $values->username;
      $email = $values->email;
      $newPassword = $this->generatePwd(8);

      if (!isset($email) || $email == '' ) {
          $user = Repository::findAll('vBuilder\Security\User')->where('[username] = %s', $username)->fetch();
      } else if (!isset($username) || $username == '' ) {
          $user = Repository::findAll('vBuilder\Security\User')->where('[email] = %s', $email)->fetch();
      } 

      if ($user != false && $user->email != '') {
        $user->setPassword($newPassword);

        $emailTemplate = new FileTemplate;
        $emailTemplate->setFile(Nette\Environment::getVariable('appDir') . '/System/Templates/Emails/pwdReset.latte');
        $emailTemplate->registerFilter(new LatteFilter);
        $emailTemplate->username = $user->username;
        $emailTemplate->newPassword = $newPassword;

        $mail = new Mail;
        $mail->setFrom('vManagerTest@gmail.com','vManager'); //TODO: nacitat z globalniho nastaveni
        $mail->addTo($user->email);
        $mail->setSubject('vManager - new password');
        $mail->setHtmlBody($emailTemplate);
        $mailer = new SendmailMailer();
        $mailer->send($mail);

        $user->save();

        $this->flashMessage('A new password has been sent to your e-mail address.');
        $this->redirect('Sign:in');
      } else {
        $form->addError('User not found or didnt filled email!');
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
		$this->flashMessage('You have been signed out.');
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

    if ($length > $maxlength) {
      $length = $maxlength;
    }
    $i = 0;
    while ($i < $length) {
      $char = substr($possible, mt_rand(0, $maxlength-1), 1);

      if (!strstr($password, $char)) {
        $password .= $char;
        $i++;
      }

    }

    return $password;
  }
}
