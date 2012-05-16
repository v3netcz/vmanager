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

use vBuilder,
	vManager,
	Nette;

/**
 * Owner info data class
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 11, 2011
 */
class OwnerInfo extends vBuilder\Object {

	/**
	 * Returns URL for owner logo
	 *
	 * @return string|null
	 */
	public function getLogoUrl() {
		$matchingFiles = vBuilder\Utils\FileSystem::findFilesWithBaseName(
				FILES_DIR . '/logo',
				array('jpg', 'png', 'gif')
		);
		
		if(count($matchingFiles) == 0) return null;
		list($logoFile) = $matchingFiles;
		
		return vManager\Modules\System\FilesPresenter::getLink('/' . pathinfo($logoFile, PATHINFO_BASENAME));
	}
	
	/**
	 * Registers URL file handler for owner logo
	 * 
	 * @return void
	 */
	static function registerLogoFileHandler() {
		vManager\Modules\System\FilesPresenter::$handers[] = function ($filename) {
						
			if(($matches = Nette\Utils\Strings::match($filename, '/^\/logo\.(png|jpg|gif)$/i')) !== null) {
				$path = FILES_DIR . '/logo.' . $matches[1];
				
				return new vBuilder\Application\Responses\FileResponse($path);
			}
		};
	}
	
}