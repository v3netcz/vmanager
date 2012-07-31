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
	 vManager\Form,
	 vManager\MultipleFileUploadControl;

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
	
	/** @persistent */
	public $state = '-1';
	
	/** @persistent */
	public $assignedTo = '-1';

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
				  
				  ->select('[ticketId], [projectId], [starId], [s.timestamp] AS [starTime], [revision], [author], [commentId], [name], [description], [priority], [assignedTo], [d.timestamp], [state],  [deadline]')

				  ->removeClause('from')
				  ->from(Ticket::getMetadata()->getTableName())
				  ->as('d')
				  ->leftJoin(Priority::getMetadata()->getTableName())->as('p')->on('[priority] = [p.id]')
				  ->leftJoin(Star::getMetadata()->getTableName())->as('s')->on('[s.entity] = %s AND [s.entityId] = [d.ticketId] AND [s.userId] = %i', 'vManager\\Modules\\Tickets\\Ticket', $uid)
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
		elseif($this->getParam('assignedTo') == -2)
			$ds->and("[assignedTo] IS NULL");
		
		if($this->getParam('projectId') > 0)
			$ds->and("[projectId] = %i", $this->getParam('projectId'));
		
		// Konec filteru

		$order = '[s.timestamp] DESC';
		if($grid->sortColumn === null)
			$ds->orderBy($order.', IF([state] IN %in, 1, 0)', $finalStateIds,', IF([deadline2] IS NULL, 0, 1) DESC, [deadline2], [p.weight] DESC, [assignedTo], [ticketId]');
		else
			$ds->orderBy($order);

		$grid->setModel(new Gridito\DibiFluentModel($ds, 'vManager\\Modules\\Tickets\\Ticket'));
		$grid->setItemsPerPage(20);

		$grid->setRowClass(function ($iterator, $ticket) {
					  $classes = array();

						$deadline = $ticket->deadline != null ? clone $ticket->deadline : null;

					  if($ticket->state->isFinal())
						  $classes[] = 'closedTicket';
					  elseif($deadline && $deadline->add(\DateInterval::createFromDateString('1 day'))->format("U") < time())
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
		
		// Tohle by melo byt zbytecne. Vsechna potrebna data jsou jiz v tom predchozim
		// megaselectu. Nase ORM vsak nepodporuje, kdyz se snazime do rowClass (entity)
		// narvat i jina data, ktera nejsou popsana v anotacich. Nechtel jsem davat do
		// Ticketu join na stars, protoze to neni jednoznacne, kazdy user to ma jinak.
		// Jak to resit...?
		$stars = $this->context->repository->findAll('vManager\Modules\Tickets\Star')
					->where('[userId] = %i',$this->user->id)
						->and('[entity] = %s','vManager\Modules\Tickets\Ticket')
					->fetchAll(); // fetchAssoc mi nefunguje. Bug nebo debil?
		$marked = array ();
		foreach ($stars as $star) {
			$marked[$star->entityId] = $star;
		}
				  		
		// ID ticketu
		$grid->addColumn("id", __('ID'), array(
			 "renderer" => function ($ticket) use($marked) {
			 		// Oznaceni hvezdickou
					$starred = array_key_exists($ticket->id, $marked);
					$phrase = $starred ? __('Unstar') : __('Mark with a star');
					$param = $starred ? -1*$ticket->id : $ticket->id;
					$link = Nette\Environment::getApplication()->getPresenter()->link('star!', $param);
					echo Nette\Utils\Html::el("a")->class(($starred ? 'unstar' : 'star').' starLink')->href($link)->setHtml('&nbsp;');
			 
			 		// ID ticketu
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
            
            	$deadline = $ticket->getProject()->deadline;

			   } else {
			   		$deadline = $ticket->deadline;
			   }
			   
			   $deadline2 = clone $deadline;
			   $deadline2->add(\DateInterval::createFromDateString('1 day'));
			   
				echo Nette\Utils\Html::el("abbr")
				 	->title($deadline->format("d. m. Y"))
				 	->setText(vManager\Application\Helpers::timeAgoInWords($deadline2));

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

				 echo Nette\Templating\Helpers::escapeHtml($ticket->priority->label);
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
			$form->addSelect('assignedTo', __('Assigned to'), array(-1 => __('To anybody'), -2 => __('To nobody')) + $this->getAllAvailableUsernames(true));
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
		
		$entity = 'vManager\\Modules\\Tickets\\Ticket';
		$metadata = $entity::getMetadata();
		$idFields = $metadata->getIdFields();
		
		// TODO: dat do do nejake mezivrstvy, aby to bylo spolecne i pro historyWidget
		$revisions = $this->context->repository->findAll($entity)
			  ->where('[' . $metadata->getFieldColumn($idFields[0]) . '] = %i', $id)
			  ->orderBy('[revision] DESC')
			  ->fetchAll();
		
		// Kontrola existence ticketu
		if($revisions === false || count($revisions) == 0) {
			throw new Nette\Application\BadRequestException("Ticket id " . var_export($id, true) . " not found");
		}
		
		// Kontrola pristupovych prav
		//  - Pokud je uzivatel Project manager
		// 	- Pokud byl ticket vytvoren aktualnim uzivatelem, nebo byl alespon jednou resitelem / prispivatelem
		// 	- Pokud je uzivatel spravcem asociovaneho projektu
		elseif(!$this->user->identity->isInRole('Project manager')) {
		
			$found = false;
			foreach($revisions as $curr) {
				if($curr->data->assignedTo == $this->context->user->id || $curr->data->author == $this->context->user->id) {
					$found = true;
					break;
				}
			}
			
			if(!$found && (!$revisions[0]->project || !$revisions[0]->project->isResponsibleUser($this->context->user->identity)))
				throw new Nette\Application\ForbiddenRequestException("Access denied to ticket id " . var_export($id, true));
		}

	}

	/**
	 * Render ticket detail1
	 * 
	 * @param int $id 
	 */
	public function renderDetail($id) {
		$texy = $this->context->texy;

		$this->template->registerHelper('texy', callback($texy, 'process'));
		$versionableEntityView = new VersionableEntityView('vManager\\Modules\\Tickets\\Ticket', $id);
		$this->addComponent($versionableEntityView, 'versionableEntityView');
		$this->template->historyWidget = $versionableEntityView;
		
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
		
		if(isset($this->module->config['attachments']['enabled']) && $this->module->config['attachments']['enabled']) {
			$form->addMultipleFileUpload('attachments', __('Select any file to attach'))
				->addRule(MultipleFileUploadControl::VALID, '', MultipleFileUploadControl::ALL);
		}

		$form->addText('project', __('Project:'))
				  ->setAttribute('autocomplete-src', $this->link('suggestProject'))
				  ->setAttribute('title', __('Is this task part of greater project?'));				  
							 
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
		
		// Not sure where to call the extensionMehtod from
		// temporary
		$files = vManager\TexylaUploadControl::addMultipleFileUpload($form, 'texylaFiles');
		$files->addRule(vManager\TexylaUploadControl::VALID, __('You may to upload anything...'),
					vManager\TexylaUploadControl::ALL);
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

		if(isset($values['attachments']) && count($values['attachments'])) {
			if(!$ticket->comment) $ticket->comment = $this->context->repository->create('vManager\Modules\Tickets\Comment');
			
			foreach($values['attachments'] as $uploadedFile) {
				$attachment = $this->context->repository->create('vManager\Modules\Tickets\Attachment');
				
				$attachment->name = $uploadedFile->getFilename();
				$attachment->type = $uploadedFile->getMimeType();
				
				// Pre save se vola az v okamziku, kdy znam ID nadrizene entity (komentare/ticketu),
				// zaroven je uzavreny v transakci, takze pokud se neco stane pri ukladani souboru
				// tak to zrusi zapis do DB.
				$attachment->onPreSave[] = function ($attachment) use ($uploadedFile, $ticket) {
					$filePath = '/attachments/tickets/' . $ticket->id;
					$attachment->path = $uploadedFile->save($filePath);
				};				
				
				$ticket->comment->attachments->add($attachment);
			}
			
			$changed = true;
		}
		
		// nebo to mam nějak zadrátovat do toho foreache výše...?
		if (isset($values['texylaFiles']) && count($values['texylaFiles'])) {
			if(!$ticket->comment) $ticket->comment = $this->context->repository->create('vManager\Modules\Tickets\Comment');

			foreach($values['texylaFiles'] as $file) {
				$attachment = $this->context->repository->create('vManager\Modules\Tickets\Attachment');
				$attachment->name = $file->getFilename();
				$attachment->type = $file->getMimeType();
				
				$attachment->onPreSave[] = function ($attachment) use ($file, $ticket) {
					$filePath = '/attachments/tickets/' . $ticket->id;
					$attachment->path = $file->move($filePath);
				};				
				$ticket->comment->attachments->add($attachment);
			}
			
			$changed = true;
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
		
		// Deadline u komentaru? WTF? Proc se to nebere z aktualniho stavu ticketu?
		/* if ($ticket->comment) {
			$ticket->comment->deadlineThen = $ticket->deadline;
		} */

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
	protected function createComponentCreateForm($name) {
		// attached early because of MFU
		$form = new Form($this, $name);

		$this->setupTicketDetailForm($form);

		$form->addTextArea('description')
				  ->getControlPrototype()->class('texyla');

		if($this->getParam('projectId')) {
			$project = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Project')
					->where('[projectId] = %i', $this->getParam('projectId'))->and('[revision] > 0')->fetch();
		
			if($project)		
				$form['project']->setDefaultValue($project->name);
		}

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
	protected function createComponentUpdateForm($name) {
		// attached early because of MFU
		$form = new Form($this, $name);
		$form->setRenderer(new Nette\Forms\Rendering\DefaultFormRenderer());

		$ticket = $this->getTicket();
		if(!$ticket) throw new Nette\InvalidStateException('No such ticket');

		$form->addTextArea('comment')->setAttribute('class', 'texyla');		

		$form->addCheckbox('private', __('Make this comment private'));
		$form->addTextArea('description')->setAttribute('class', 'texyla');
		
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
		
		if(!$form->isSubmitted()) {
			$form['name']->setValue($ticket->name);
			$form['deadline']->setValue($ticket->deadline);
			$form['description']->setValue($ticket->description);
			
			if($ticket->assignedTo !== null && $ticket->assignedTo->exists())
				$form['assignTo']->setValue($ticket->assignedTo->username);

			if($ticket->priority !== null && $ticket->priority->exists())
				$form['priority']->setValue($ticket->priority->id);

			if($ticket->project !== null && $ticket->project->exists())
				$form['project']->setValue($ticket->project->name);
		}

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
	
	public function handleStar($ticketId) {
		$repository = $this->context->repository;
		$id = intval($ticketId);
		if ($id > 0) {
			// star
			$star = $repository->create('vManager\Modules\Tickets\Star');
			$star->user = $this->user->identity;
			$star->entity = 'vManager\Modules\Tickets\Ticket';
			$star->entityId = $id;
			$star->save();
			if ($this->isAjax()) {
				$this->payload->success = true;
				$this->payload->newUrl = $this->link('star!', -1*$id);
				$this->sendPayload();
			} else {
				$this->flashMessage(__('The ticket was successfully starred.'));
				$this->redirect('this');
			}
		} elseif ($id < 0) {
			// unstar
			$star = $repository->findAll('vManager\Modules\Tickets\Star')
							->where('[userId] = %i',$this->user->id)
								->and('[entity] = %s','vManager\Modules\Tickets\Ticket')
								->and('[entityId] = %i', -1*$id)
							->fetch();
			$star->delete();
			if ($this->isAjax()) {
				$this->payload->success = true;
				$this->payload->newUrl = $this->link('star!', -1*$id);
				$this->sendPayload();
			} else {
				$this->flashMessage(__('The star was successfully removed.'));
				$this->redirect('this');
			}
		} else {
			
		}
	}

	/**
   * Create specific template according to ticket state
   * @return
   */   
  protected function createTemplate($class = NULL) {    
    $template = parent::createTemplate($class);

    $ticket = $this->getTicket();
    
    if ($ticket != NULL) {
      $stateName = $ticket->state->id;            
      
      $extendedTemplate = __DIR__ . '/../Templates/Ticket/detail.' . strtolower($stateName) . '.latte';
      if (file_exists($extendedTemplate)) {     
          $template->setFile($extendedTemplate);       
      }		
    }
    return $template;
  }
}
