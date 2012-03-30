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

namespace vManager\Modules\Tickets;

use vManager,
	Nette,
	Nette\Application\Responses\JsonResponse;

/**
 * Presenter for serving JSON RPC calls
 *
 * @author Adam Staněk (V3lbloud)
 * @since Mar 30, 2012
 */
class JsonConnectorPresenter extends vManager\Modules\System\JsonConnectorPresenter {

	public function actionNewTicket() {
		// Vytvorim novy ticket
		$ticket = $this->context->repository->create('vManager\Modules\Tickets\Ticket');
		
		// Implicitni stav ticketu
		$ticket->state = $this->module->defaultTicketState->id;
				
		$ticket->name = $this->requestData->title;
		$ticket->author = $this->context->user->getIdentity();
				
		// Ostatni polozky ...
		foreach(array('description') as $curr) {
			if(isset($this->requestData->{$curr}))
				$ticket->{$curr} = trim($this->requestData->{$curr});
		}
		
		// Projekt
		if(isset($this->requestData->projectName) && $this->requestData->projectName != "") {
			$proj = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Project')
					  ->where('[revision] > 0 AND [name] = %s', $this->requestData->projectName)->fetch();
					  
			if(!$proj) {
				$proj = $this->context->repository->create('vManager\Modules\Tickets\Project');
				$proj->name = $this->requestData->projectName;
				$proj->author = Nette\Environment::getUser()->getIdentity();
			}
			
			$ticket->project = $proj;
		}	
		
		// Ulozim ticket
		$ticket->save();
		
		// Zaslu ID vytvoreneho ticketu v odpovedi
		$this->responseData->ticket = new \StdClass;
		$this->responseData->ticket->id = $ticket->id;
	}
	
	public function actionGetProjects() {
		 $projects = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Project')
				  ->where('[revision] > 0');
		
		 $this->responseData->projects = array();		  
		 foreach($projects as $curr) {
		 	$proj = new \StdClass;
		 	$proj->name = $curr->name;
		 
		 	$this->responseData->projects[] = $proj;
		 }
	}
	
}