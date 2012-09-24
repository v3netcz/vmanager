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

class UploadManager extends vBuilder\Object {
	
	
	const CACHE_KEY = 'vManager.UploadManager';
	
	const ORIGINAL_NAME = 'original';
	const FINAL_NAME = 'final';
	
	/**
	 * @var string
	 */
	protected $filesPoolDir;
	
	
	protected $context;
	
	protected $cache;
	
	protected $nonConflictDirectories = array ();
	
	/**
	 * @var string
	 * @see ::getLastFilename()
	 */
	protected $lastFilename;

	/**
	 * @param string $filesPoolDir relative to config: upload->dir
	 * @param Nette\DI\IContainer $context 
	 */
	public function __construct($filesPoolDir, Nette\DI\IContainer $context) {
		$this->context = $context;
		$this->filesPoolDir = $filesPoolDir;
		
		$filesPoolDir = $this->getFilesPoolDir();
		if (!file_exists($filesPoolDir)) {
			throw new Nette\InvalidStateException("The directory '$filesPoolDir' does not exist!");
		}
		if (!is_writable($filesPoolDir)) {
			throw new Nette\InvalidStateException("The directory '$filesPoolDir' is not writable!");
		}
	}
	
	
	public function getRelativeFilesPoolDir() {
		return $this->filesPoolDir;
	}
	
	public function getFilesPoolDir() {
		return $this->context->parameters['upload']['dir'] . '/' . $this->filesPoolDir;
	}
	
	/**
	 * @param string $token
	 * @return Nette\Caching\Cache 
	 */
	protected function getCache() {
		if (!$this->cache) {
			$this->cache = new Nette\Caching\Cache($this->context->cacheStorage, static::CACHE_KEY);
		}
		return $this->cache;
	}
	
	/**
	 * Adds a file to the list of files already added with the same $token.
	 * However, every file has to be unique on the list and consequently
	 * this method generates a new filename which it then returns. The new 
	 * filename will also be available at ::getLastFilename().
	 * @param string $token or a sort of *namespace*
	 * @param int $fileNumber - for overriding
	 * @param string $filename
	 * @return string the new filename OR ::getLastFilename() may be used
	 */
	public function addFile($token, $fileNumber, $filename) {
		$fileNumber = max(0, intval($fileNumber));
		$cache = $this->getCache(); // cache, not session, because we don't know
		// which user will submit the file
		
		$copy = $cache[$token] ?: array ();
		$inUse = false;
		$filenames = array ();
		foreach ($copy as $num => $nested) {
			if ($nested[static::FINAL_NAME] === $filename && $fileNumber !== $num) {
				$inUse = true;
			}
			$filenames[] = $nested[static::FINAL_NAME];
		}
		$foundInNonConflicts = array ();
		foreach ($this->nonConflictDirectories as $directory) {
			$foundInNonConflicts = array_merge($foundInNonConflicts, static::findAllFilesInADir($directory));
		}
		foreach ($foundInNonConflicts as $item) {
			if ($item === $filename) {
				$inUse = true;
			}
		}
		$filenames = array_merge($foundInNonConflicts, $filenames);
		if ($inUse) {
			$newFilename = static::generateNewUniqueFilename($filename, $filenames);
			$this->lastFilename = $newFilename['filename'];
		} else {
			$this->lastFilename = $filename;
		}
		$copy[$fileNumber] = array (
			static::ORIGINAL_NAME => $filename,
			static::FINAL_NAME => $this->lastFilename
		);
		$cache[$token] = $copy;
		return $this->lastFilename;
	}
	
	
	/**
	 * Generates a new possible filename (as similar to $filename as possible)
	 * for a new file to be moved to $dir. Will only be renamed if $autoRenameDuplicates
	 * is set to true
	 * @param string $dir
	 * @param string $filename
	 * @param bool $autoRenameDuplicates
	 * @return array
	 */
	public static function generateFileName($dir, $filename, $autoRenameDuplicates = true) {
		if (!file_exists($dir)) {
			throw new Nette\InvalidArgumentException("Directory '$dir' does not exist!");
		}
		if ($autoRenameDuplicates) {
			return static::generateNewUniqueFilename($filename, static::findAllFilesInADir($dir));
		} else {
			$withExtension = self::saveWithExtension();
			$webalized = $withExtension ? $filename : Strings::webalize(self::getFilenamePrefix().$filename,'.');
			$extension = pathinfo($webalized, PATHINFO_EXTENSION);
			return array (
				'filename' => $webalized,
				'extension' => $extension && self::saveWithExtension() ? $extension : null
			);
		}
	}
	
	/**
	 * @param string $dir
	 * @return array
	 */
	protected static function findAllFilesInADir($dir) {
		$dirContent = array_slice(scandir($dir), 2);
		$foundFiles = array ();

		foreach ($dirContent as $item) {
			if (is_file($dir.'/'.$item)) {
				$foundFiles[] = $item;
			}
		}
		return $foundFiles;
	}
	
	/**
	 * Does the same thing as generateFileName() but accepts $banlist, an array
	 * of unavailable filenames.
	 * @param string $filename
	 * @param array $banlist
	 * @see generateFileName()
	 * @return array
	 */
	public static function generateNewUniqueFilename($filename, $banlist) {
		$withExtension = self::saveWithExtension();
		$webalized = Strings::webalize(self::getFilenamePrefix().$filename,'.', false);
		$parts = pathinfo($webalized);
		$extension = isset($parts['extension']) ? $parts['extension'] : null;
		if (!$withExtension) {
			$webalized = $filename;
			$extension = null;
		}
		if(in_array($webalized, $banlist)) {
			$iteration = 0;
			do {
				$webalized = Strings::webalize(self::getFilenamePrefix().$parts['filename'] . '-' . $iteration . ($withExtension?('.' . $parts['extension']) : '') ,'.', false);
				$iteration++;
			} while (in_array($webalized, $banlist));
		}
		return array(
			'filename' => $webalized,
			'extension' => $extension
		);
	}
	
	// todo
	public static function getFilenamePrefix() {
		return '';
	}


	public static function saveWithExtension() {
		return Nette\Environment::getConfig('upload')->saveWithExtension;
	}
	
	/**
	 * After addFile() is called, this method returns the same until
	 * addFile() is called again.
	 * @return string|null
	 */
	public function getLastFilename() {
		return $this->lastFilename;
	}
	
	
	/**
	 * @param string $token
	 * @param integer $fileNumber
	 * @return array
	 */
	public function getFile($token, $fileNumber) {
		$files = $this->getAllFiles($token);
		return $files[max(0, intval($fileNumber))];
	}
	
	/**
	 * @param string $token
	 * @param integer $fileNumber
	 * @return string
	 */
	public function getOriginalFilename($token, $fileNumber) {
		$file = $this->getFile($token, $fileNumber);
		return $file[static::ORIGINAL_NAME];
	}
	
	/**
	 * @param string $token
	 * @param integer $fileNumber
	 * @return string
	 */
	public function getFinalFilename($token, $fileNumber) {
		$file = $this->getFile($token, $fileNumber);
		return $file[static::FINAL_NAME];
	}
	
	/**
	 * @param string $token
	 * @return array 
	 */
	public function getAllFiles($token) {
		$cache = $this->getCache();
		return $cache[$token]; // because PHP()[]...
	}
	
	/**
	 * Sets a directory of which content will be considered as a sort of
	 * banlist (the contained filenames) when determining the new filenames.
	 * This is particularly handy when one wants to move the files to this
	 * directory later and wants to prevent any potential name conflicts right 
	 * away.
	 * @param string $dir
	 * @return UploadManager 
	 */
	public function addNonConflictDirectory($dir) {
		if (!file_exists($dir)) {
			$dir = static::getBaseDir() . $dir;
			if (!file_exists($dir)) {
				throw new Nette\InvalidArgumentException("Directory '$dir' does not exist!");
			}
		}
		$this->nonConflictDirectories[] = $dir;
		return $this;
	}
	
	/**
	 * @return string 
	 */
	public static function getBaseDir() {
		return Nette\Environment::getConfig('upload')->dir;
	}
}