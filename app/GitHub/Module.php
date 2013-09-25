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

namespace vManager\Modules;

use vManager, Nette,
	Nette\Application\Routers\Route;

/**
 * GitHub integration module
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 14, 2012
 */
class GitHub extends vManager\Application\Module implements vManager\Application\IAclEnabledModule,
			vManager\Application\ITimelineEnabledModule {

	public function __construct() {
		if(!$this->isEnabled()) return ;
	
		$application = Nette\Environment::getApplication();
		$application->onStartup[] = function() use ($application) {
			
			$router = $application->getRouter();	
			$router[] = new Route('github-push[/<token>]', 'GitHub:PushHook:default', Route::ONE_WAY);
		
		};
	}
	
	/**
	 * Initializes permission resources/roles/etc.
	 * 
	 * @param Nette\Security\Permission reference to permission class
	 */
	public function initPermission(Nette\Security\Permission & $acl) {
		$acl->addRole('Github user', 'User');
	}
	
	/**
	 * Returns array of timeline records
	 *
	 * @param DateTime since
 	 * @param DateTime until
 	 * @param array of user ids for query (defined by getTimelineUsers)
	 *
	 * @return array of vManager/Timeline/IRecord
	 */
	public function getTimelineRecords(\DateTime $since, \DateTime $until, array $forUids) {
		$context = Nette\Environment::getContext();
		
		if(count($forUids) == 0) return array();
		
		$commits = $context->repository->findAll('vManager\\Modules\\GitHub\\Commit')
				->where('[timestamp] BETWEEN %d AND %t', $since, $until)
				->fetchAll();
				
		$data = array();
		foreach($commits as $commit) {
			$data[] = new GitHub\TimelineRecord($commit);
		} 
		
		return $data;
	}
	
	/**
	 * Returns array of available user ids for timeline presentation.
	 *
	 * @return array of user ids
	 */
	public function getTimelineUsers() {
		$context = Nette\Environment::getContext();
	
		// Docasne reseni, povolujeme jen GH usery, ale tem zobrazujeme vsechno,
		// protoze uzivatelske ucty prozatim nejsou sparovane s Githubem.
		if($context->user->identity->isInRole('Github user'))
			return array($context->user->id);
			
		return array();
	}

}