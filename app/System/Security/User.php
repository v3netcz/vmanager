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

namespace vManager\Security;

use vManager, vBuilder, Nette;

/** 
 * Overloaded user entity
 *
 * Addtional fields:
 *
 * @Column(name)
 * @Column(surname)
 * @Column(email)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 28, 2011
 */
class User extends vBuilder\Security\User {
	
	/** avatars directory name */
	const AVATAR_DIR = 'avatars';
	
	/**
	 * Returns user salutation
	 * 
	 * @return string
	 */
	function getSalutation() {
		global $context;
		
		$config = $context->userConfig;
		return $config->get('system.salutation', _x('Hi %s', array($this->getName())));
	}
	
	/**
	 * Returns avatar picture URL
	 * 
	 * @param bool true if URL should be absolute URI (starts with http://)
	 * @return string URL
	 */
	function getAvatarUrl($absolute = false) {
		foreach(array('jpg', 'png', 'gif') as $ext) {
			$filepath = '/'. self::AVATAR_DIR .'/'. $this->getId() . '.' . $ext;
			if(file_exists(FILES_DIR . $filepath))
				return vManager\Modules\System\FilesPresenter::getLink($filepath);
		}
				
		return vManager\Modules\System::getBasePath($absolute)
				  . '/images/profile.jpg';
	}
	
	/**
	 * Registers URL file handler for avatars
	 * 
	 * @return void
	 */
	static function registerAvatarFileHandler() {
		vManager\Modules\System\FilesPresenter::$handers[] = function ($filename) {
			
			// Filtering exact match, for security reasons
			if(Nette\Utils\Strings::match($filename, '/^\/'.User::AVATAR_DIR.'\/[0-9]+\.(png|jpg|gif)$/')) {
				$filepath = FILES_DIR . $filename;
				
				if(file_exists($filepath))
					return new vBuilder\Application\Responses\FileResponse($filepath);
			}
		};
	}
	
}
