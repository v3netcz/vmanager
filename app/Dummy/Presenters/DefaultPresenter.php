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

namespace vManager\Modules\Dummy;

use vManager, vBuilder, Nette;

/**
 * Default presenter dummy presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 5, 2011
 */
class DefaultPresenter extends vManager\Modules\System\SecuredPresenter {
	
	public function renderDefault() {
		
	}
	
	protected function createComponentDummyForm() {
		$form = new vManager\Form;
		$form->addDatePicker('datePicker1');
	
		$form->addText('username', 'Normal text field:')
				  ->setRequired('Please provide a some text.');

		$form->addPassword('password', 'Password field:')
				  ->setRequired('Please provide a password.');
		
		$form->addSelect('color', 'Favourite color', array('red', 'green', 'blue'));
		
		$form->addRadioList('gender', 'Gender', array('Male', 'Female'));
		
		$form->addTextArea('text', 'Some note');
		
		$form->addFile('f', 'File attachment');		
		
		$form->addCheckbox('ch1', 'I agree check');
		
		$form->addSubmit('send', 'Send');

		$form->onSuccess[] = callback($this, 'dummyFormSubmitted');
		
		return $form;
	}

	public function dummyFormSubmitted($form) {
		//$values = $form->getValues();
		
		$this->flashMessage('Form submitted');
	}
	
}
