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

namespace vManager;

use vManager, vBuilder, Nette,
	vBuilder\Utils\Strings;

class TexylaUploadControl extends MultipleFileUploadControl {
	
	protected $context;


	/**
	 * @param Nette\Application\UI\Form $form
	 * @param string $name
	 * @return TexylaUploadControl 
	 */
	public static function addMultipleFileUpload(Nette\Application\UI\Form $form, $name, $label = null) {
		$control = parent::addMultipleFileUpload($form, $name); // no label
		
		$form->addHidden($control->formatHiddenInputName($name));
		
		return $control;
	}
	
	/**
	 * There has to be a hidden input with a token. This method generates its name.
	 * @param string $fileInputName 
	 */
	protected function formatHiddenInputName($fileInputName) {
		return $fileInputName . 'Token';
	}
	
	/**
	 * @see TexylaUploadedFile::move()
	 * @return array of TexylaUploadedFile 
	 */
	public function getValue() {
		$files = $this->getFiles();
		$context = $this->form->presenter->context;
		$uploadManager = $context->uploadManager;
		$token = $this->form[$this->formatHiddenInputName($this->name)]->getValue();
		FileSaver::setRelativeDir('/'.$uploadManager->getRelativeFilesPoolDir().'/'.$token);
		$savedFiles = array();

		foreach ($files as $fileNumber => $file) {
			if (!$file->isOk()) { // $file->getError() !== UPLOAD_ERR_OK
				// bummer
				continue; // temporary
			}
			$info = pathinfo($uploadManager->getFinalFilename($token, $fileNumber));
			$saver = new FileSaver($file);
			$saver->setFilename($info['filename']);
			$path = $saver->save();
			
			$savedFiles[$fileNumber] = new TexylaUploadedFile(
				$path['uploadedPath'], 
				$info['extension'], 
				$file->getContentType(), 
				$file->getSize()
			);
		}
		
		return $savedFiles;
	}
	
	public static function getExtensionGroups() {
		return parent::$extensions;
	}
}