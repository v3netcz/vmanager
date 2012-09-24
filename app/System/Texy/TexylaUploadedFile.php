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

class TexylaUploadedFile {
	
	protected $filePath;
	protected $extension;
	protected $mimeType;
	protected $size;
	
	protected $filename;

	/**
	 * @param string $filePath
	 * @param string $extension Because saving with exension may be disabled
	 * @param string $mimeType 
	 * @param int $size
	 */
	public function __construct($filePath, $extension, $mimeType, $size) {
		$this->filePath = $filePath;
		$this->extension = $extension;
		$this->mimeType = $mimeType;
		$this->size = $size;
	}
	
	/**
	 * @return string
	 */
	public function getBaseDir() {
		return Nette\Environment::getConfig('upload')->dir;
	}

	/**
	 * @return string
	 */
	public function getFilePath() {
		return $this->filePath;
	}
	
	/**
	 * @return string
	 */
	public function getFilename() {
		if (!$this->filename) {
			$info = pathinfo($this->getFilePath());
			$this->filename = $info['filename'];
		}
		return $this->filename;
	}
	
	/**
	 * @return string
	 */
	public function getExtension() {
		return $this->extension;
	}
	
	/**
	 * @return string
	 */
	public function getMimeType() {
		return $this->mimeType;
	}
	
	/**
	 * @return int 
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * @param string $destination - relative to the base dir
	 * @param bool $deleteTemporaryFile 
	 * @see ::getBaseDir()
	 * @return bool
	 * @throws FileUploadException
	 */
	public function move($destination, $deleteTemporaryFile = true) {
		$destination = $destination[0] === '/' ? $destination : ('/'.$destination); // prepend /
		$target = $this->getBaseDir() . $destination;
		$filePath = $this->getFilePath();
		@mkdir($target, 0777, true);
		
		$ext = Nette\Environment::getConfig('upload')->saveWithExtension ? '.'.$this->getExtension() : '';
		$filename = '/'.$this->getFilename().$ext;
		$destination .= $filename;
		$copy = copy($this->getBaseDir() . $filePath, $target.$filename);
		if ($copy) {
			if ($deleteTemporaryFile) {
				unlink($this->getBaseDir() . $filePath);
				$info = pathinfo($filePath);
				$dir = realpath($this->getBaseDir() . $info['dirname']);

				if (count(scandir($dir)) === 2) {
					rmdir($dir);
				}
			}
		} else {
			throw new FileUploadException("Copying of the file failed.");
		}
		return $destination;
	}
}