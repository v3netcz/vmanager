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


use Nette,
	 Nette\Utils\Strings;

/**
 * 
 *
 * @author Jirka Vebr
 */
class FileSaver extends Nette\Object
{	
	private $file;
	
	private static $dirname;
	private static $relativeDir;
	private static $prefix;


	public function __construct(Nette\Http\FileUpload $file) {
		$this->file = $file;
	}
	
	public function getFile() {
		return $this->file;
	}
	
	public function save($autoRenameDuplicates = true) {
		if($this->getFile()->isOk()) {
			$file = $this->generateFileName($autoRenameDuplicates);
			$path = $file['filename'].($file['extension']?'.'.$file['extension']:'');
			if ($this->getFile()->move(self::getFilesDir().$path)) {
				$file['mimeType'] = $this->getFile()->getContentType();
				$file['uploadedPath'] = self::$relativeDir . '/' . $path;
				return $file;
			} else {
				throw new FileUploadException('An error has occured while saving the image, please try again.');
			}
		} else {
			throw new FileUploadException('You have uploaded an invalid file. Please try again.');
		}
	}
	
	/**
	 * Generates an untaken file name for a new image. If the file is saved without an extension, 
	 * an array is returned instead.
	 * @param bool generate a new filename if the one set is already taken?
	 * @return array
	 */
	private function generateFileName($autoRenameDuplicates)
	{
		$withExtension = self::saveWithExtension();
		$webalized = Strings::webalize(self::getFilenamePrefix().$this->getFile()->getName(),'.');
		$parts = \pathinfo($webalized);
		$extension = isset($parts['extension']) ? $parts['extension'] : null;
		if (!$withExtension) {
			$webalized = $parts['filename'];
			$extension = null;
		}
		$file = self::getFilesDir() . $webalized;
		if(is_file($file) && $autoRenameDuplicates) {
			$iteration = 0;
			do {
				$webalized = Strings::webalize(self::getFilenamePrefix().$parts['filename'] . '-' . $iteration . ($withExtension?('.' . $parts['extension']) : '') ,'.');
				$iteration++;
			} while (\is_file(self::getFilesDir() . $webalized));
		}
		return array(
			'filename' => \pathinfo($webalized, \PATHINFO_FILENAME),
			'extension' => $extension
		);
	}
	
	public static function getFilesDir() {
		return self::$dirname ?: self::getBaseDir();
	}
	
	public static function setFilesDir($dirname) {
		$dirname =  \preg_replace('~/+~', '/', $dirname);
		if (!\preg_match('~/$~', $dirname)) {
			$dirname .= '/';
		}
		
		// Do I really hate @ that much...? :-|
		if (!\is_dir($dirname)) {
			$dirs = array();
			while (!\is_dir($dirname)) {
				\preg_replace_callback('~([\w\-\d]+[/]?)$~', function ($arr) use (&$dirname, &$dirs) {
					$dirs[] = $arr[1];
					$dirname = \substr($dirname, 0, -1*\strlen($arr[1]));
					return '';
				}, $dirname);
			}
			$dirs = \array_reverse($dirs);
			foreach ($dirs as $dir) {
				if (!\mkdir($dirname.$dir, 0777)) {
					throw new InvalidStateException('Unable to create directory "'.$dirname.$dir.'".');
				}
				$dirname .= $dir;
			}
		}
		if (!\is_writable($dirname)) {
			if(!\chmod($dirname, 0777)) {
				throw new Nette\InvalidStateException('Directory "'.$dirname.'" is not writable!');
			}
		}
		self::$dirname = $dirname;
		return true;
	}

	public static function saveWithExtension() {
		return Nette\Environment::getConfig('upload')->saveWithExtension;
	}
	
	public static function getBaseDir() {
		return Nette\Environment::getConfig('upload')->dir;
	}
	
	public static function setRelativeDir($dirname) {
		if (self::setFilesDir(self::getBaseDir().$dirname)) {
			self::$relativeDir = preg_replace('~/+$~', '', $dirname);
		}
	}
	
	public static function setFilenamePrefix($prefix) {
		self::$prefix = $prefix;
	}
	
	public static function getFilenamePrefix() {
		return self::$prefix ?: '';
	}
}

class FileUploadException extends \Exception {}