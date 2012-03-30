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

use vManager,
	Nette,
	Nette\Application\Responses\JsonResponse;

/**
 * Global search presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 10, 2011
 */
class JsonConnectorPresenter extends /* SecuredPresenter */ BasePresenter {

	private $_decodedRequestData;
	private $_responseData;

	public function startup() {
		parent::startup();
		
		// Pokud klient poslal prihlasovaci udaje
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			try {
				$this->user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
			} catch(Nette\Security\AuthenticationException $e) { }			
		}
		
		if(!$this->user->isAllowed($this->name, $this->action)) {
			
			// Unauthorized
			if(!$this->user->isLoggedIn()) {
				$this->context->httpResponse->addHeader('WWW-Authenticate', 'Basic realm="Login required"');
			
				$this->context->httpResponse->setCode(Nette\Http\Response::S401_UNAUTHORIZED);
				$this->sendResponse(new Nette\Application\Responses\TextResponse("Unauthorized. Access denied."));
			}
			
			// Forbidden
			else {
				$this->context->httpResponse->setCode(Nette\Http\Response::S403_FORBIDDEN);
				$this->sendResponse(new Nette\Application\Responses\TextResponse("Permission denied."));
			}
		}
	}

	/**
	 * Returns request data
	 *
	 * @return StdClass|null
	 */
	public function getRequestData() {
		if(!isset($this->_decodedRequestData)) {
			$input = file_get_contents('php://input');
			if($input != "") {
				$decodedInput = json_decode($input);
				
				$this->_decodedRequestData = ($decodedInput instanceof \StdClass)
					? $decodedInput
					: false;
									
			} else
				$this->_decodedRequestData = false;
		}
		
		return $this->_decodedRequestData === false
			? null
			: $this->_decodedRequestData;
		
	}
	
	/**
	 * Returns object containing response data
	 *
	 * @return StdClass
	 */
	public function getResponseData() {
		if(!isset($this->_responseData)) {
			$this->_responseData = new \StdClass;
		}
		
		return $this->_responseData;
	}
	
	/**
	 * Sends JSON response
	 *
	 * @return void
	 */
	protected function afterRender() {
		// Kontrola, jestli akci obslouzila nejaka metoda
		if(!isset($this->_responseData)) {
			if(!$this->reflection->hasMethod($this->formatActionMethod($this->getAction()))) {
				throw new Nette\Application\BadRequestException("Action " . var_export($this->getAction(), true) . " not found", Nette\Http\IResponse::S404_NOT_FOUND);
			}
		}
	
		$this->sendResponse(new JsonResponse($this->responseData));
	}
	
	/**
	 * Handles throwed exceptions
	 *
	 * @param  Nette\Application\Request
	 * @return Nette\Application\IResponse
	 */
	public function run(Nette\Application\Request $request) {
		try {
			$response = parent::run($request);
		} catch(\Exception $e) {
			$errorObj = new \StdClass;
			$errorObj->error = new \StdClass;
			$errorObj->error->class = get_class($e);
			$errorObj->error->code = $e->getCode();
			$errorObj->error->message = $e->getMessage();
		
			$this->context->httpResponse->setCode(Nette\Http\IResponse::S500_INTERNAL_SERVER_ERROR);
			$response = new JsonResponse($errorObj);
			
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		
		return $response;
	}
	
}