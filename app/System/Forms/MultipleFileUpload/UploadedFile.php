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

use Nette;

/**
 * @author Jirka Vebr
 * 
 * @property-read mimeType
 * @property-read extension
 * @property-read filename
 * @property-read uploadedPath
 * @property-read errorCode
 */
class UploadedFile extends Nette\Object
{
	private $mimeType;
	private $extension;
	private $filename;
	private $uploadedPath;
	private $errorCode;
	
	
	private $saver;
	
	public function __construct(Nette\Http\FileUpload $file) {
		$this->saver = new FileSaver($file);
		$parts = \pathinfo($file->getName());
		$this->errorCode = $file->getError();
		$this->filename = $parts['filename'];
		$this->extension = isset($parts['extension']) ? $parts['extension'] : null;
		$this->mimeType = $file->getContentType();
	}
	
	public function getErrorCode() {
		return $this->errorCode;
	}
	
	public function getMimeType() {
		return $this->mimeType;
	}
	
	public function getExtension() {
		return $this->extension;
	}
	
	public function getFilename() {
		return $this->filename;
	}
	
	public function getUploadedPath() {
		return $this->uploadedPath;
	}
	
	public function setRelativeDir($dirname) {
		FileSaver::setRelativeDir($dirname);
		return $this;
	}
	
	public  function setFilenamePrefix($prefix) {
		FileSaver::setFilenamePrefix($prefix);
		return $this;
	}
	
	public function setFilename($filename) {
		$this->filename = $filename;
		$this->saver->setFilename($filename);
		return $this;
	}
	
	/**
	 *
	 * @param string $destination rlative path to where to save the file
	 * @param bool $autoRenameDuplicates generate a new filename if the one set is already taken?
	 * @return string relative path to the saved file 
	 */
	public function save($destination = null, $autoRenameDuplicates = true) {
		if ($destination) {
			$this->setRelativeDir($destination);
		}
		$fileInfo = $this->saver->save((bool)$autoRenameDuplicates);
		$this->filename = $fileInfo['filename'];
		return $this->uploadedPath = $fileInfo['uploadedPath'];
	}
}