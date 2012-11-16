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
	vManager\Form,
	vManager\MultipleFileUploadControl,
	vBuilder;

/**
 * System settings presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 11, 2011
 */
class SettingsPresenter extends SecuredPresenter {

	/**
	 * Settings form component factory.
	 *
	 * @return Form
	 */
	protected function createComponentSettingsForm($name) {
		$user = $this->user->identity;

		$form = new Form($this, $name);
		
		$form->addMultipleFileUpload('logo', __('Select your logo'))
				->addRule(MultipleFileUploadControl::VALID, __('You may only upload PNG images!'), array (
					'jpg' => 'image/jpg',
					'jpg' => 'image/jpeg',
					'JPG' => 'image/jpeg',
					'png' => 'image/png',
					'gif' => 'image/gif',
					'svg' => 'image/svg+xml'
				))
				->addRule(MultipleFileUploadControl::FILES_COUNT, __('You can upload one file only'), 1);
		

		$form->addSubmit('send', __('Save'));

		$form->onSuccess[] = callback($this, 'settingsFormSubmitted');
	}
	
	/**
	 * Settings form subbmited action handler
	 *
	 * @param Form
	 */
	public function settingsFormSubmitted($form) {
		$values = $form->getValues();
		$config = $this->context->userConfig->getGlobalScope();

		if (count($values->logo)) {
			
			vBuilder\Utils\FileSystem::tryDeleteFiles(
				vBuilder\Utils\FileSystem::findFilesWithBaseName(
					FILES_DIR . '/logo',
					array('jpg', 'png', 'gif', 'svg')
				)
			);			

			$logo = $values->logo[0];			
			$logo->setFilename('logo.' . $logo->getExtension());
			$path = FILES_DIR . $logo->save('/');
			
			/* $img = Nette\Image::fromFile($path);
			$img->resize(122, 122);
			$img->save($path); */
			
			//$config->set('company.logo', $path); 
		}
		
		$config->save();
		
		$this->flashMessage(__('All changes are saved.'));
		$this->redirect('default');
	}

}