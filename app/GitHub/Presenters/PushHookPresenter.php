<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vManager is free software: you can redistribute it and/or modify
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

namespace vManager\Modules\GitHub;

use vManager, vBuilder, Nette, Nette\Utils\Strings;

/**
 * Receiver of push web hook
 * 
 * @see http://help.github.com/post-receive-hooks/
 * @see http://help.github.com/test-webhooks/
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 14, 2012
 */
class PushHookPresenter extends Nette\Application\UI\Presenter {

	private static $allowedRemotes = array(
		'127.0.0.1',
		
		// V3Net.cz - development
		'77.48.73.134',
		'10.10.',
	
		// GitHub public IPs
		'207.97.227.253',
		'50.57.128.197'
	);

	public function actionDefault() {
		
		// try {
		
		$allowed = false;
		foreach(self::$allowedRemotes as $curr) {
			if(Strings::startsWith($this->context->httpRequest->getRemoteAddress(), $curr)) {
				$allowed = true;
				break;	
			}
		}

		if(!$allowed) throw new Nette\Application\ForbiddenRequestException("Access denied");				
	
		$token = $this->getParam('token');
		if($token == "") throw new Nette\Application\ForbiddenRequestException("Missing security token");
		if(!isset($this->module->config['securityToken'])) throw new vBuilder\InvalidConfigurationException("Missing GitHub.securityToken configuration option");
		if($this->module->config['securityToken'] != $token) throw new Nette\Application\ForbiddenRequestException("Access denied");
		
		//$receivedData = file_get_contents('php://input');
		$receivedData = $this->context->httpRequest->getPost('payload');
		
		// Log support
		if(isset($this->module->config['log']['enabled']) && $this->module->config['log']['enabled'])
			$this->logData($receivedData);		
					
		$decodedData = json_decode($receivedData);
		
		// Kontrola, jestli jde o validni JSON data
		if($decodedData === NULL && json_last_error() != JSON_ERROR_NONE)
			throw new Nette\Application\BadRequestException("Malformed data received");
			
		if(!isset($decodedData->repository) || !isset($decodedData->commits))
			throw new Nette\Application\BadRequestException("Invalid data format received");

		$tz = new \DateTimeZone(date_default_timezone_get());
		$repo = $this->getGitHubRepository($decodedData->repository->url, $decodedData->repository->name);				
		foreach($decodedData->commits as $commit) {
		
			// Konverze casoveho pasma
			$timestamp = \DateTime::createFromFormat(\DateTime::W3C, $commit->timestamp);
			$timestamp->setTimezone($tz);
		
			// Commit
			$e = $this->context->repository->create('vManager\\Modules\\GitHub\\Commit');
			$e->id = $commit->id;
			$e->url = $commit->url;
			$e->repo = $repo;
			$e->author = $this->getGitHubUser($commit->author->name, $commit->author->email);
			$e->timestamp = $timestamp;
			$e->message = $commit->message;
			$e->save();
		}
		
		/* } catch(\Exception $e) {
			echo "Logged exception\n";
			Nette\Diagnostics\Debugger::log($e);
		} */

		// Poslu prazdnou odpoved s HTTP 200
		$this->sendResponse(new Nette\Application\Responses\TextResponse(""));
	}
	
	/**
	 * Returns github repository for given url
	 *
	 * @return Repository
	 */
	private function getGitHubRepository($url, $name) {
		$entity = 'vManager\\Modules\\GitHub\\Repository';
		
		$repo = $this->context->repository->findAll($entity)
					->where('[url] = %s', $url)->fetch();

		if($repo === false) {
			$repo = $this->context->repository->create($entity);
			$repo->url = $url;
			$repo->name = $name;			
		}
		
		return $repo;
	}
	
	/**
	 * Returns github user entity for name and e-mail address
	 *
	 * @return User
	 */
	private function getGitHubUser($name, $email) {
		$entity = 'vManager\\Modules\\GitHub\\User';
	
		$user = $this->context->repository->findAll($entity)
					->where('[name] = %s', $name)->and('[email] = %s', $email)->fetch();
					
		if($user === false) {
			$user = $this->context->repository->create($entity);
			$user->name = $name;
			$user->email = $email;
		}	
		
		return $user;
	}
	
	/**
	 * Logs given data into GitHub log file
	 */
	private function logData(&$data) {
		$hash = md5($data);
		$filename = 'github-push-' . @date('Y-m-d-H-i-s') . '-' . $hash . '.log';
		$dir = $this->context->parameters['logDir'];
		
		file_put_contents($dir . '/' . $filename, $data);
	}
	
	/**
	 * Returns instance of GitHub module
	 * @return vManager\Modules\GitHub
	 */
	public function getModule() {
		return vManager\Modules\GitHub::getInstance();
	}

}