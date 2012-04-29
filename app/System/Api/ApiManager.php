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

class ApiManager extends vBuilder\Object {
	
	/**
	 * @var array|null
	 * @see getDocumentedObjects()
	 */
	protected $documentedObjects = null;


	/**
	 * @param string $obj Class, Interface, whatever
	 */
	public function isDocumented($obj) {
		return in_array($obj, $this->getDocumentedObjects());
	}
	
	
	/**
	 * @param string $class class name. The namespaces may be omitted provided it is specific enough.
	 * @param string $member
	 */
	public function generateApiLink($class, $member = null) {
		$class = Strings::replace($class, '~^(\\\\|/)+~', '');
		$class = $this->getFullClassName($class);
		$baseUrl = $this->getBaseApiUrl();
		
		$url = $baseUrl . Strings::replace($class, '~\\\\|/~', '.') . '.html';
		// also accepting '/' as a namespace separator
		
		if (isset($member)) {
			$url .= '#';
			if (!Strings::startsWith($member, '$')) {
				$url .= '_';
			}
			$url .= $member;
		}
		return $url;
	}
	
	/**
	 * @param string $query A part of a class name || interface
	 * @return array of classes / interfaces
	 */
	public function searchApi($query) {
		return $this->searchInArrayHelper($this->getDocumentedObjects(), $query);
	}
	
	/**
	 * @param string $class Full class name
	 * @param string $member The query 
	 * @return array results
	 */
	public function searchForMember($class, $query) {
		if ($this->isDocumented($class)) {
			// potential security loophole
					
			$properties = array_keys(get_class_vars($class)); // temporary
			array_walk($properties, function (&$str, $key, $pref) {
				$str = $pref.$str;
			}, '$');
			$methods = get_class_methods($class); // temporary

			$data = array_merge($properties, $methods);
			return $this->searchInArrayHelper($data, $query);
		}
		throw new Nette\InvalidArgumentException("Class $class is not documented");
	}
	
	
	/**
	 * This method is so unfortunate to have to make some really hard decisions.
	 *		If $class === 'C' and there is \D\C and \E\C, it will have to choose.
	 *		So far this method has no idea what to do in this case.
	 * 
	 * Also, forward compatibility is really an issue here because with such
	 * guessing going on, the links may once break.
	 * @param string $class 
	 */
	protected function getFullClassName($class) {
		$search = $this->searchApi($class);
		$count = count($search);
		if ($count === 0) {
			throw new Nette\InvalidArgumentException("Class '$class' is not documented.");
		} else if($count === 1) {
			return reset($search);
		} else { // more than one result
			// what are we supposed to do now?
			
			return $search[array_rand($search)];
			// outrageous *solution* preventing this issue from being forgotten
		}
	}
	
	/**
	 * @return string
	 */
	protected function getBaseApiUrl() {
		return 'http://api.vmanager.cz/internal/class-';
	}
	
	/**
	 * @return array
	 */
	protected function getDocumentedObjects() {
		if (!isset($this->documentedObjects)) {
			$this->documentedObjects = get_declared_classes(); // temporary
		}
		return $this->documentedObjects;
	}
	
	/**
	 * @param array $array
	 * @param string $query
	 * @return array
	 */
	protected function searchInArrayHelper($array, $query) { // Because array_search is stupid.
		$result = array ();
		foreach ($array as $item) {
			if (Strings::contains($item, $query, false)) { // false -> case insensitive
				$result[] = $item;
			}
		}
		return $result;
	}
}