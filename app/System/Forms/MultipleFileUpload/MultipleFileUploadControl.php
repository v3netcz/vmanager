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
	 Nette\Forms,
	 Nette\InvalidStateException,
	 Nette\IOException;

/**
 * 
 *
 * @author Jirka Vebr
 */
class MultipleFileUploadControl extends Forms\Controls\UploadControl
{
	const ALL		= 1;
	const IMAGES	= 2;
	const ARCHIVES	= 4;
	const OFFICE	= 8;
	const PHP		= 16;
	
	private $files = array();
	
	private $extensions = array(
		self::IMAGES	=>	array ('jpg','jpeg','png','gif','ico','psd','pdf','bmp'),
		self::ARCHIVES	=>	array ('zip','rar','tar'),
		self::OFFICE	=>	array ('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'wpd', 'odt', 'ods', 'odp'),
		self::PHP		=>	array ('phtml','pwml','php5','php4','php3','php2','php','inc')
	);
	
	
	private $mimes = array ();
	
	private $modes = array(
		self::IMAGES, self::ARCHIVES, self::OFFICE, self::PHP
	);
	
	const VALID = ':file';
	const FILLED = ':filled';
	const SIZE = ':size';
	const AGGREGATE_SIZE = ':aggregateSize';
	
	private static $registered = false;
	private static $valid = array();


	public function __construct($label) {
		parent::__construct($label);
		$this->control->multiple = true;
		
		$cache = Nette\Environment::getCache("vBuilder.Download");
		if(!isset($cache["mime-types"])) {
			$ini = parse_ini_file(LIBS_DIR.'/vBuilderFw/vBuilderFw/mime.ini');
			if($ini != false) $cache["mime-types"] = $ini;
		}
		foreach ($this->modes as $mode) {
			$this->mimes[$mode] = array ();
			foreach ($this->extensions[$mode] as $extension) {
				if (\array_key_exists($extension, $cache['mime-types'])) {
					$this->mimes[$mode][] = $cache['mime-types'][$extension];
				}
			}
		}
		array_unshift($this->modes, self::ALL);
	}
	
	
	public static function register() {
		if (self::$registered) {
			throw new Nette\InvalidStateException('Multiple file upload has already been registered!');
		}
		Forms\Container::extensionMethod('addMultipleFileUpload', callback(__CLASS__, 'addMultipleFileUpload'));
		self::$registered = true;
		
		if (\preg_match('~^([\d]+)(\w)~', \ini_get('upload_max_filesize'), $matches)) {
			$limit = (int) $matches[1];
			if ($limit < 3 and $matches[2] === 'M') {
				throw new Nette\InvalidStateException('Your "upload_max_filesize" is set to below 3MB');
			}
		}
		
		if (Nette\Environment::getConfig('upload') === null) {
			throw new Nette\InvalidStateException('Your config lacks the required "upload" section!');
		}
	}
	public static function addMultipleFileUpload(Nette\Application\UI\Form $form, $name, $label = null) {
		if (!$form->isAnchored()) {
			throw new Nette\InvalidStateException('MultipleFileUploadControl requires the form to be anchored. See Form::__construct.');
		}
		$form[$name] = new static($label);
		self::$valid[$form[$name]->lookupPath('Nette\Application\UI\Presenter')] = array();
		return $form[$name];
	}

	
	/**
	 * @param array | Nette\Http\FileUpload
	 * @return MultipleFileUpload - fluent
	 */
	public function setValue($files) {
		if ($files === null) {
			return;
		}
		if (!\is_array($files)) {
			$files = array($files);
		}
		self::$valid[$this->lookupPath('Nette\Application\UI\Presenter')] = array();
		foreach ($files as $key => $file) {
			if (!($file instanceof Nette\Http\FileUpload)) {
				throw new Nette\InvalidArgumentException('Multiple file upload control expects \Nette\Http\FileUpload or an array of more as a value!');
			}
			self::$valid[$this->lookupPath('Nette\Application\UI\Presenter')][] = false;
		}
		$this->files = $files;
		$this->value = null;
		return $this;
	}


	public function getControl() {
		$control = parent::getControl();
		$control->name .= '[]'; // allowing multiple files
		$control->class[] = 'multiple';
		return $control;
	}
	
	public function getValue() {
		$files = array();
		foreach ($this->getFiles() as $key => $file) {
			if (!isset(self::$valid[$this->lookupPath('Nette\Application\UI\Presenter')][$key])) {
				throw new \LogicException('Trying to get unvalidated files is prohibitted for security reasons. It was probably forgotten to add a validating rule.');
			}
			$files[] = new UploadedFile($file);
		}
		return $this->value = Nette\ArrayHash::from($files);
	}

	private function getFiles() {
		return $this->files;
	}
	
	public function addRule($operation, $message = null, $arg = null) {
		if ($operation === self::VALID && isset($arg)) {
			if (is_array($arg)) {
				$modes = $arg;
			} else {
				$modes = array();
				foreach ($this->modes as $val) {
					if ($val & $arg) {
						$modes[] = $val;
					}
				}
			}
			return parent::addRule(self::VALID, $message, $modes);
		} else {
			return parent::addRule($operation, $message, $arg);
		}
	}
	
	private static function isUploaded($control) {
		$files = $control->getFiles();
		$providedFiles = 0;
		foreach ($control->files as $key => $file) {
			if ($file->getError() === \UPLOAD_ERR_NO_FILE) {
				unset ($control->files[$key]);
			} else {
				$providedFiles++;
			}
		}
		return (bool) $providedFiles;
	}
	
	public static function validateFilled (Forms\IControl $control) {
		if ($control instanceof MultipleFileUploadControl) {
			return self::isUploaded($control);
		} else {
			return parent::validateFilled($control);
		}
	}
	
	public static function validateFile (MultipleFileUploadControl $control, $modes = array()) {
		if (self::isUploaded($control)) {
			$files = $control->getFiles();
			$all = false;
			$extensions = array();
			$mimes = array();
			foreach ($modes as $key => $mode) {
				if ($mode & self::ALL) {
					$all = true;
				} else {
					if (is_string($key) && is_string($mode)) {
						// array(extension => mime, ...)
						$ext = array($key);
						$mim = array($mode);
					} else {
						// regular groups
						$ext = $control->extensions[$mode];
						$mim = $control->mimes[$mode];
					}
					$extensions = array_merge($extensions, $ext);
					$mimes = array_merge($mimes, $mim);
				}
			}
			foreach ($files as $num => $file) {
				if (!$all) {
					$path = FileSaver::getFilesDir().$file->getName();
					$extension = \pathinfo($path, \PATHINFO_EXTENSION);

					if ($extension && !\in_array($extension, $extensions)) {
						return false;
					}

					$mime = $file->getContentType();
					if (!\in_array($mime, $mimes)) {
						return false;
					}
				}
				self::$valid[$control->lookupPath('Nette\Application\UI\Presenter')][$num] = true;
			}
		}
		return true;
	}
	
	public static function validateSize(MultipleFileUploadControl $control, $size) {
		if (self::isUploaded($control)) {
			foreach ($control->getFiles() as $file) {
				if ($file->getSize() >= $size) {
					return false;
				}
			}
		}
		return true;
	}
	
	public static function validateAggregateSize(MultipleFileUploadControl $control, $totalSize) {
		if (self::isUploaded($control)) {
			$total = 0;
			foreach ($control->getFiles() as $file) {
				$total += $file->getSize();
			}
			if ($total >= $totalSize) {
				return false;
			}
		}
		return true;
	}
}