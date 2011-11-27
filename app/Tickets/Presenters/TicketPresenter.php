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
	 vBuilder,
	 Nette,
	 vBuilder\Orm\Repository,
	 Gridito,
	 vManager\Form;

/**
 * Presenter for viewing tickets
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 27, 2011
 */
class TicketPresenter extends vManager\Modules\System\SecuredPresenter {

	/** @var Ticket */
	protected $ticket;
	/** @var array of suggestions to Assign form field */
	protected $assignToSuggestions = array();

	// <editor-fold defaultstate="collapsed" desc="Ticket listing (default)">
	
	/**
	 * Ticket listing table grid component factory
	 * 
	 * @param string $name
	 * @return vManager\Grid
	 */
	protected function createComponentTicketListingGrid($name) {
		$grid = new vManager\Grid($this, $name);
		$grid->setTemplateFile(__DIR__ . '/../Templates/Gridito/ticketGrid.latte');

		$uid = Nette\Environment::getUser()->getId();
		$table = Ticket::getMetadata()->getTableName();

		$ds = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Ticket')
				  ->removeClause('select')
				  
				  // Deadline s project fallbackem
				  ->select('IFNULL([deadline], IF([projectId] IS NULL, NULL, (SELECT [deadline] FROM ['.
				  	Project::getMetadata()->getTableName().'] WHERE [revision] > 0 AND [projectId] = [d.projectId]) )) AS [deadline2]')
				  
				  ->select('[ticketId], [projectId], [revision], [author], [commentId], [name], [description], [priority], [assignedTo], [timestamp], [state],  [deadline]')
				  
				  ->removeClause('from')
				  ->from(Ticket::getMetadata()->getTableName())
				  ->as('d')
				  ->leftJoin(Priority::getMetadata()->getTableName())->as('p')->on('[priority] = [p.id]')
				  ->where('[revision] > 0');

		// Pokud to neni spravce ticketu, zobrazuju jen tickety, ktere uzivatel zalozil
		// nebo kterym je prirazen
		if(!Nette\Environment::getUser()->getIdentity()->isInRole('Project manager')) {
			$projectTable = Project::getMetadata()->getTableName();
			$owningProjectCondition = "[projectId] IS NOT NULL AND EXISTS (SELECT * FROM [$projectTable] WHERE [projectId] = [d.projectId] AND [revision] > 0 AND ([author] = %i OR [assignedTo] = %i))";
			$ds->and("([assignedTo] = %i OR [author] = %i OR ([revision] > 1 AND EXISTS (SELECT * FROM [$table] WHERE [author] = %i AND [revision] = -1 AND [ticketId] = [d.ticketId])) OR ($owningProjectCondition))", $uid, $uid, $uid, $uid, $uid);
		}
		
		$finalStateIds = array();
		foreach($this->module->finalTicketStates as $curr) $finalStateIds[] = $curr->id;
		
		// Filtery		
		if(($stateFilter = $this->getParam('state', -1)) != -1)
			$ds->and("[state] = %s", $this->getParam('state'));
		
		if($this->getParam('assignedTo') > 0)
			$ds->and("[assignedTo] = %i", $this->getParam('assignedTo'));
		
		if($this->getParam('projectId') > 0)
			$ds->and("[projectId] = %i", $this->getParam('projectId'));
		
		// Konec filteru
		
		if($grid->sortColumn === null)
			$ds->orderBy('IF([state] IN %in, 1, 0)', $finalStateIds,', IF([deadline2] IS NULL, 0, 1) DESC, [deadline2], [p.weight] DESC, [assignedTo], [ticketId]');
		
		$grid->setModel(new Gridito\DibiFluentModel($ds, 'vManager\\Modules\\Tickets\\Ticket'));
		$grid->setItemsPerPage(20);

		$grid->setRowClass(function ($iterator, $ticket) {
					  $classes = array();

					  if($ticket->state->isFinal())
						  $classes[] = 'closedTicket';
					  elseif($ticket->deadline != null && $ticket->deadline->format("U") < time())
						  $classes[] = 'overdueTicket';

					  if($ticket->priority !== null && $ticket->priority->exists()) {
						  if($ticket->priority->weight == Priority::getMaxPriorityWeight())
							  $classes[] = 'criticalTicket';
						  elseif($ticket->priority->weight == Priority::getMaxPriorityWeight() - 1)
							  $classes[] = 'highPriorityTicket';
					  }

					  return empty($classes) ? null : implode(" ", $classes);
				  });

		// =======================================================================
		// ID ticketu
		$grid->addColumn("id", __('ID'), array(
			 "renderer" => function ($ticket) {
				 $link = Nette\Environment::getApplication()->getPresenter()->link('detail', $ticket->id);
				 echo Nette\Utils\Html::el("a")->href($link)->setText('#'.$ticket->id);
			 },
			 "sortable" => true,
		))->setCellClass('id');;

		// Nazev ticketu
		$grid->addColumn("name", __('Ticket name'), array(
			 "renderer" => function ($ticket) {
				 $link = Nette\Environment::getApplication()->getPresenter()->link('detail', $ticket->id);
				 if($ticket->project)
						echo Nette\Utils\Html::el("span")->class('project')->setText('[' . $ticket->project->name .  ']');
				 
				 echo ' ';
				 echo Nette\Utils\Html::el("a")->href($link)->setText($ticket->name);
			 },
			 "sortable" => true,
		));

		// Resitel ukolu
		//if(Nette\Environment::getUser()->getIdentity()->isInRole('Project manager')) {
			$grid->addColumn("assignedTo", __('Assigned to'), array(
				 "renderer" => function ($ticket) {
					 echo $ticket->assignedTo !== null ? ($ticket->assignedTo->exists() ? $ticket->assignedTo->username
											  : _x('User n. %d', array($ticket->assignedTo->id))) : __('nobody');
				 },
				 "sortable" => true
			))->setCellClass('assignedTo');
		//}

		// Datum posledni zmeny
		$grid->addColumn("timestamp", __("Last change"), array(
			 "renderer" => function ($ticket) {
				 echo Nette\Utils\Html::el("abbr")->title($ticket->timestamp->format("d. m. Y"))->setText(vManager\Application\Helpers::timeAgoInWords($ticket->timestamp));
			 },
			 "sortable" => true
		))->setCellClass("date lastChange");

		// Deadline
		$grid->addColumn("deadline", __('Deadline'), array(
			 "renderer" => function ($ticket) {

			   if($ticket->deadline == null) {
       			if ($ticket->getProject() == null || $ticket->project->deadline == null) {
              		echo "-";
              		return;
            	} 
            
            	echo Nette\Utils\Html::el("abbr")
              		->title($ticket->getProject()->deadline->format("d. m. Y"))
                	->setText(vManager\Application\Helpers::timeAgoInWords($ticket->getProject()->deadline));
           		return;
			   }
			   
				echo Nette\Utils\Html::el("abbr")
				 	->title($ticket->deadline->format("d. m. Y"))
				 	->setText(vManager\Application\Helpers::timeAgoInWords($ticket->deadline));

			 },
			 "sortable" => true
		))->setCellClass("date deadline");
		
		// Priorita
		$grid->addColumn("priority", __('Priority'), array(
			 "renderer" => function ($ticket) {
				 if($ticket->priority == null) {
					 echo "-";
					 return;
				 }

				 echo Nette\Templating\DefaultHelpers::escapeHtml($ticket->priority->label);
			 },
			 "sortable" => true
		))->setCellClass("priority");

		// Stav ticketu
		$grid->addColumn("state", __('State'), array(
			 "renderer" => function ($ticket) {
				 echo Nette\Utils\Html::el("span")->setText($ticket->state->name);
			 },
			 "sortable" => true,
		))->setCellClass("state");
	}
	
	protected function createComponentTicketFilter() {
		$form = new Form;
		
		$states = array(-1 => __('Any'));
		foreach($this->module->availableTicketStates as $curr)
			$states[$curr->id] = $curr->name;
		
		$form->addSelect('state', __('State'), $states);
		
		if(Nette\Environment::getUser()->getIdentity()->isInRole('Project manager')) {
			$form->addSelect('assignedTo', __('Assigned to'), array(-1 => __('To anybody')) + $this->getAllAvailableUsernames(true));
		}
		
		$projects = $this->getAllAvailableProjects();
		if(count($projects) > 1) 
			$form->addSelect('projectId', __('Project'), array(-1 => __('Any')) + $projects);	
		
		$form->setValues($this->getParam());
		$form->addSubmit('send', __('Filter'));
		
		$presenter = $this;
		$form->onSuccess[] = function () use ($presenter, $form) {
			$presenter->redirect("default", (array) $form->getValues());
		}; 
		
		return $form;
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Ticket detail (detail)">
	
	/**
	 * Authorization of ticket render request before performing any action
	 * 
	 * @param int $id 
	 */
	public function actionDetail($id) {
		// TODO: Autorizace prav uzivatele pro zobrazeni/pridani apod. ticketu
		// pripadne to povesit do entity na event handlery (u verzovanych nemuze byt Secured)
	}

	/**
	 * Render ticket detail1
	 * 
	 * @param int $id 
	 */
	public function renderDetail($id) {
		$texy = new \Texy();
		$texy->encoding = 'utf-8';
		$texy->allowedTags = \Texy::NONE;
		$texy->allowedStyles = \Texy::NONE;
		$texy->setOutputMode(\Texy::XHTML1_STRICT);

		$this->template->registerHelper('texy', callback($texy, 'process'));

		$this->template->historyWidget = new VersionableEntityView('vManager\\Modules\\Tickets\\Ticket', $id);
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Ticket form helpers">
	
	/**
	 * Function helper for setting up form fields of addtional ticket info
	 * 
	 * @param vManager\Form reference to form
	 */
	protected function setupTicketDetailForm(Form & $form) {
		$form->addText('name', __('Task title:'))->setAttribute('title', __('Short task description. Please be concrete.'))
				  ->addRule(Form::FILLED, __('Task title has to be filled.'));


		$form->addDatePicker('deadline', __('Deadline:'))->setAttribute('title', __('When has to be task done?'));

		$context = $this->context;
		
		$form->addText('assignTo', __('Assign to:'))
				  ->setAttribute('autocomplete-src', $this->link('suggestAssignTo'))
				  ->setAttribute('title', __('Who will resolve this issue?'))
				  ->addCondition(Form::FILLED)
				  ->addRule(function ($control) use($context) {
								 $users = $context->repository->findAll('vManager\Security\User')->where('[username] = %s', $control->value)->fetchSingle();
								 return ($users !== false);
							 }, __('Responsible person does not exist.'));


		$form->addText('project', __('Project:'))
				  ->setAttribute('autocomplete-src', $this->link('suggestProject'))
				  ->setAttribute('title', __('Is this task part of greater project?'))
				  ->addRule(function ($control) use($context) {
                 if ($control->value == null || $control->value == '') return true;                 
								 $projects = $context->repository->findAll('vManager\\Modules\\Tickets\\Project')->where('[name] = %s', $control->value)->fetchSingle();
								 return ($projects !== false);
							 }, __('This project does not exist.'));				  
							 
		$prioritiesDs = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Priority');
		$priorities = array();
		$defaultValue = null;
		foreach($prioritiesDs as $curr) {
			if($curr->getWeight() == 1 && $defaultValue === null)
				$defaultValue = $curr->getId();
			$priorities[$curr->getId()] = __($curr->getLabel());
		}

		$form->addSelect('priority', __('Priority:'), $priorities)
				  ->setDefaultValue($defaultValue);
	}

	protected function saveTicket(Ticket $ticket, $values) {
		$changed = false;

		if($values['newState']) {
			if($values['newState'] === true)
				list($state) = $ticket->possibleStates;
			else
				$state = $this->module->getTicketState($values['newState']);
			
			$ticket->state = $state;
			$changed = true;
		}

		if(isset($values['comment']) && !empty($values['comment'])) {
			$ticket->comment = $this->context->repository->create('vManager\Modules\Tickets\Comment');
			$ticket->comment->text = $values['comment'];

			if (isset($values['private']) && !empty($values['private'])) {
        $ticket->comment->private = $values['private'];
			} else {
        $ticket->comment->private = false;

			}
			$changed = true;
		} else {
			$ticket->comment = null;
		}

		if(isset($values['assignTo'])) {
			if(!empty($values['assignTo'])) {
				$user = $this->context->repository->findAll('vManager\Security\User')->where('[username] = %s', $values['assignTo'])->fetch();
				$newAssignedTo = $user !== false ? $user : null;
			} else
				$newAssignedTo = null;

			if(!$changed)
				$changed = $newAssignedTo === null ? ($ticket->assignedTo !== null) : ($ticket->assignedTo === null || $newAssignedTo->id != $ticket->assignedTo->id);

			$ticket->assignedTo = $newAssignedTo;
		}
		
		if(isset($values['project'])) {
			if(!empty($values['project'])) {
				$newProject = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Project')
					  ->where('[revision] > 0 AND [name] = %s', $values['project'])->fetch();
				
				if(!$newProject) {
					$newProject = $this->context->repository->create('vManager\Modules\Tickets\Project');
					$newProject->name = $values['project'];
					$newProject->author = Nette\Environment::getUser()->getIdentity();
				}
				
			} else
				$newProject = null;

			if(!$changed) $changed = $newProject === null ? ($ticket->project !== null) : ($ticket->project === null || $newProject->id != $ticket->project->id);
			$ticket->project = $newProject;
		}

		if(isset($values['priority'])) {
			$priority = $this->context->repository->get('vManager\\Modules\\Tickets\\Priority', $values['priority']);
			$ticket->priority = $priority->exists() ? $priority : null;
			$changed = true;
		}

		foreach(array('name', 'description', 'deadline') as $curr) {
			if($ticket->{$curr} != $values[$curr]) {
				$ticket->{$curr} = $values[$curr];
				$changed = true;
			}
		}

		if($changed) {
			$ticket->author = Nette\Environment::getUser()->getIdentity();
			$ticket->timestamp = null; // Vyuzivam CURRENT_TIMESTAMP defaultu
			$ticket->save();
		}

		return $changed;
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Form suggestion helpers">
	
	/**
	 * Set matching items for current query given in typedText parameter (GET term)
	 * 
	 * @param string $typedText The text the user typed in the input
	 *
	 * @return void
	 */
	public function actionSuggestAssignTo() {
		$typedText = $this->getParam('term', '');

		$this->assignToSuggestions = $this->getAllAvailableUsernames(false, $typedText, 10);
	}
	
	/**
	 * Send the matching items for assign to field completer (JSON)
	 * 
	 * @return void
	 */
	public function renderSuggestAssignTo() {
		$this->sendResponse(new Nette\Application\Responses\JsonResponse($this->assignToSuggestions));
	}
	
	private function getAllAvailableUsernames($realNames = false, $filterTerm = '', $limit = -1) {
		$users = $this->context->repository->findAll('vManager\\Security\\User');
		
		if($filterTerm != '')
			$users->where('[username] LIKE %s', $filterTerm.'%');
		
		if($limit > 0)
			$users->limit($limit);				  

		$data = array();
		foreach($users as $curr) {
			$data[$curr->getId()] = $realNames ? $curr->getDisplayName() : $curr->getUsername();
		}
		
		return $data;
	}
	
	public function renderSuggestProject() {
		$projectSuggestions = $this->getAllAvailableProjects($this->getParam('term', ''), 10);
		$this->sendResponse(new Nette\Application\Responses\JsonResponse($projectSuggestions));
	}
	
	private function getAllAvailableProjects($filterTerm = '', $limit = -1) {
		$ds = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Project')
				  ->where('[revision] > 0');
		
		if($filterTerm != '')
			$ds->and('[name] LIKE %s', $filterTerm.'%');
		
		if($limit > 0)
			$ds->limit($limit);				  

		$data = array();
		foreach($ds as $curr) {
			$data[$curr->getId()] = $curr->getName();
		}
		
		return $data;
	}
	
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="Create ticket form (create)">
	
	/**
	 * Create form
	 * @return vManager\Form
	 */
	protected function createComponentCreateForm() {
		$form = new Form;

		$this->setupTicketDetailForm($form);

		$form->addTextArea('description')
				  ->getControlPrototype()->class('texyla');

		$form->addSubmit('send', __('Save'));

		$form->onSuccess[] = callback($this, 'createFormSubmitted');

		return $form;
	}
	
	public function createFormSubmitted(Form $form) {
		$values = $form->getValues();
		$ticket = $this->context->repository->create('vManager\Modules\Tickets\Ticket');

		$values['newState'] = $this->module->defaultTicketState->id;

		$this->saveTicket($ticket, $values);

		$this->flashMessage(__('New ticket has been created.'));
		$this->redirect('detail', $ticket->id);
	}
	
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="Update form (detail)">
	
	/**
	 * Update form
	 * @return vManager\Form
	 */
	protected function createComponentUpdateForm() {
		$form = new Form;
		$form->setRenderer(new Nette\Forms\Rendering\DefaultFormRenderer());

		$ticket = $this->getTicket();

		$form->addTextArea('comment')->setAttribute('class', 'texyla');

		$form->addCheckbox('private', __('Make this comment private'));

		$form->addTextArea('description')->setValue($ticket->description)->setAttribute('class', 'texyla');

		
		$possibleStates = $ticket->possibleStates;
				
		if(count($possibleStates) > 1) {
			$states = array();
			foreach($possibleStates as $curr)
				$states[$curr->id] = $curr->isFinal()
						? _x('resolve as %s', array($curr->name))
						: _x('change to %s', array($curr->name));
			
			$form->addSelect('newState', __('State:'), $states)
							->setPrompt(__('do not change'));

			
		} elseif(count($possibleStates) > 0) {
			list($nextState) = $possibleStates;
			
			if($ticket->state->isFinal() && !$nextState->isFinal())
				$label = __('Reopen this ticket');
			else
				$label = _x('Change state to \'%s\'', array($nextState->name));
				
			$form->addCheckbox('newState', $label);
		}

		$this->setupTicketDetailForm($form);
		$form['name']->setValue($ticket->name);
		$form['deadline']->setValue($ticket->deadline);
		if($ticket->assignedTo !== null && $ticket->assignedTo->exists())
			$form['assignTo']->setValue($ticket->assignedTo->username);

		if($ticket->priority !== null && $ticket->priority->exists())
			$form['priority']->setValue($ticket->priority->id);
		
		if($ticket->project !== null && $ticket->project->exists())
			$form['project']->setValue($ticket->project->name);

		$form->addSubmit('send', __('Save'));

		$form->onSuccess[] = callback($this, 'updateFormSubmitted');

		return $form;
	}

	public function updateFormSubmitted(Form $form) {
		$values = $form->getValues();
		$ticket = $this->getTicket();

		if($this->saveTicket($ticket, $values))
			$this->flashMessage(__('Change has been saved.'));
		else
			$this->flashMessage(__('Nothing to change.'));


		$this->redirect('this');
	}
	
	// </editor-fold>

	protected function getTicket() {
		if($this->getParam('id') === null)
			return null;
		if($this->ticket !== null)
			return $this->ticket;

		$this->ticket = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Ticket')
							 ->where('[revision] > 0 AND [ticketId] = %i', $this->getParam('id'))->fetch();

		return $this->ticket;
	}

	/**
	 * Returns instance of ticketing system module
	 * @return vManager\Modules\Tickets
	 */
	public function getModule() {
		return vManager\Modules\Tickets::getInstance();
	}
	
}
