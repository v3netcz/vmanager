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

use vManager, vBuilder,
	 Nette,
	 Nette\Application\Routers\Route,
	 Nette\Application\BadRequestException;

/**
 * Presenter for providing files outside of document root through user authorization.
 * 
 * Presenter offers universal access based on registred handlers. Handlers are simple functions
 * returning matching Nette\Application\IResponse for filename. If handler doesn't return
 * anything, script will fallback to another handler. Finally if no handler responded, #404
 * error will be thrown.
 * 
 * <code>
 *		vManager\Modules\System\FilesPresenter::$handers[] = function ($filename) {
 *			
 *			// !! Secure file id to filepath translation
 *			// Be aware, that given filename is non-escaped string, wchich can contain special sequences (such as ../, //, etc.)
 *			if($filename == '/myfile.txt') {
 *				$filepath = FILES_DIR . $filename;
 *				
 *				if(file_exists($filepath))
 *					return new vBuilder\Application\Responses\FileResponse($filepath);
 *			}
 *		};
 * </code>
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jun 11, 2011
 */
class FilesPresenter extends BasePresenter {
	
	const PARAM_NAME = 'path';
	
	static public $handers = array();

	public function actionDefault() {
		$file = $this->getParam(self::PARAM_NAME);
		
		if(empty($file))
			throw new \InvalidArgumentException("Missing ".self::PARAM_NAME." parameter");
		
		foreach(self::$handers as $handler) {
			$result = callback($handler)->invokeArgs(array($file));
			if($result) {
				if($result instanceof Nette\Application\IResponse)
					$this->sendResponse($result);
				
				return ;
			}
		} 

		throw new BadRequestException("Data file '$file' not found", 404);
	}

	/**
	 * Creates link for viewing file
	 * 
	 * @param string file path
	 * @return string URL 
	 */
	static public function getLink($file) {
		return Nette\Environment::getApplication()->getPresenter()->link(":System:Files:default", array(
			 self::PARAM_NAME => $file
		));
	}
	
	/**
	 * Sets up application routes for Files presenter
	 */
	static public function setupRoutes() {
		$application = Nette\Environment::getApplication();
		$router = $application->getRouter();
		
		$router[] = new Route('files[<'.self::PARAM_NAME.'>]', array(
						self::PARAM_NAME => array(
							 Route::PATTERN => '/([^/]*/)*[^/]+',
							 Route::FILTER_IN => null,
							 Route::FILTER_OUT => null
						),
						Route::PRESENTER_KEY => 'System:Files',
						'action' => 'default'
							 )
		);
	}

}
