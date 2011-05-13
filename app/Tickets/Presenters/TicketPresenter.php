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

	public function renderDefault() {
		
	}

	protected function createComponentTicketListingGrid($name) {
		$grid = new vManager\Grid($this, $name);

		$ds = Repository::findAll('vManager\\Modules\\Tickets\\Ticket')
				  ->where('[revision] > 0');

		$grid->setModel(new Gridito\DibiFluentModel($ds));
		$grid->setItemsPerPage(20);

		// columns
		$grid->addColumn("id", __('ID'), array(
			 "renderer" => function ($row) {
				 $link = Nette\Environment::getApplication()->getPresenter()->link('detail', $row->ticketId);
				 echo Nette\Utils\Html::el("a")->href($link)->setText('#'.$row->ticketId);
			 },
			 "sortable" => true,
		));

		$grid->addColumn("name", __('Ticket name'), array(
			 "renderer" => function ($row) {
				 $link = Nette\Environment::getApplication()->getPresenter()->link('detail', $row->ticketId);
				 echo Nette\Utils\Html::el("a")->href($link)->setText($row->name);
			 },
			 "sortable" => true,
		));
			
		$grid->addColumn("timestamp", __("Last change"))->setSortable(true);
			 
		$grid->addColumn("state", __('State'), array(
			 "renderer" => function ($row) {
				 echo Nette\Utils\Html::el("span")->style($row->state == Ticket::STATE_CLOSED ? 'color: green' : 'color: orange')->setText($row->state == Ticket::STATE_CLOSED ? __('Closed') : __('Opened'));
			 },
			 "sortable" => true,
		));

		
	}

	public function renderDetail($id) {
		$texy = new \Texy();
		$texy->encoding = 'utf-8';
		$texy->allowedTags = \Texy::NONE;
		$texy->allowedStyles = \Texy::NONE;
		$texy->setOutputMode(\Texy::XHTML1_STRICT);

		$this->template->registerHelper('texy', callback($texy, 'process'));

		$this->template->historyWidget = new VersionableEntityView('vManager\\Modules\\Tickets\\Ticket', $id);
	}

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

		$form->onSubmit[] = callback($this, 'createFormSubmitted');

		return $form;
	}
	
	/**
	 * Function helper for setting up form fields of addtional ticket info
	 * 
	 * @param vManager\Form reference to form
	 */
	protected function setupTicketDetailForm(Form & $form) {
		$form->addText('name', __('Name:'))->setAttribute('title', __('Short task description. Please be concrete.'))
			 ->addRule(Form::FILLED, __('Name of ticket has to be filled.'));
		
		
		$form->addDatePicker('deadline', __('Deadline:'))->setAttribute('title', __('When has to be task done?'));

		$form->addText('assignTo', __('Assign to:'))
				  ->setAttribute('autocomplete-src', $this->link('suggestAssignTo'))
				  ->setAttribute('title', __('Who will resolve this issue?'))
				  ->addCondition(Form::FILLED)
				  ->addRule(function ($control) {
								 $users = Repository::findAll('vManager\Security\User')->where('[username] = %s', $control->value)->fetchSingle();
								 return ($users !== false);
							 }, __('Responsible person does not exist.'));
						  

		/*
		$form->addSelect('priority', __('Priority:'), array(
			__('Low'), __('Normal'), __('High') 
		)); */
	}

	/**
	 * Set matching items for current query given in typedText parameter (GET term)
	 * 
	 * @param string $typedText The text the user typed in the input
	 *
	 * @return void
	 */
	public function actionSuggestAssignTo() {
		$typedText = $this->getParam('term', '');
		
		$users = Repository::findAll('vManager\\Security\\User')
				  ->where('[username] LIKE %s', $typedText.'%')->limit(10)
				  ->fetchAll();		
		
		$data = array();
		foreach($users as $curr) {
			$data[] = $curr->getUsername();
		}
		
		$this->assignToSuggestions = $data;
	}

	/**
	 * Send the matching items for assign to field completer (JSON)
	 * 
	 * @return void
	 */
	public function renderSuggestAssignTo() {
		$this->sendResponse(new Nette\Application\Responses\JsonResponse($this->assignToSuggestions));
	}

	public function createFormSubmitted(Form $form) {
		$values = $form->getValues();
		$ticket = new Ticket();

		$this->saveTicket($ticket, $values);

		$this->flashMessage(__('New ticket has been created.'));
		$this->redirect('detail', $ticket->id);
	}

	/**
	 * Update form
	 * @return vManager\Form
	 */
	protected function createComponentUpdateForm() {
		$form = new Form;
		$form->setRenderer(new Nette\Forms\Rendering\DefaultFormRenderer());

		$ticket = $this->getTicket();

		$form->addTextArea('comment')->setAttribute('class', 'texyla');
		$form->addTextArea('description')->setValue($ticket->description)->setAttribute('class', 'texyla');

		if($ticket->isOpened())
			  $form->addCheckbox('close', __('Close ticket with this comment'));
		else
			  $form->addCheckbox('reopen', __('Reopen this ticket'));
		
		$this->setupTicketDetailForm($form);
		$form['name']->setValue($ticket->name);
		$form['deadline']->setValue($ticket->deadline);
		if($ticket->assignedTo !== null && $ticket->assignedTo->exists()) $form['assignTo']->setValue($ticket->assignedTo->username);
		
		$form->addSubmit('send', __('Save'));

		$form->onSubmit[] = callback($this, 'updateFormSubmitted');

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

	protected function saveTicket(Ticket $ticket, $values) {
		$changed = false;
		
		if(isset($values['reopen']) && $values['reopen'] && !$ticket->isOpened()) {
			$ticket->state = Ticket::STATE_OPENED;
			$changed = true;
		}
		
		if(isset($values['close']) && $values['close'] && $ticket->isOpened()) {
			$ticket->state = Ticket::STATE_CLOSED;
			$changed = true;
		}			
		
		if(isset($values['comment']) && !empty($values['comment'])) {
			$ticket->comment = new Comment();
			$ticket->comment->text = $values['comment'];
			$changed = true;
		} else {
			$ticket->comment = null;
		}

		if(isset($values['assignTo'])) {
			if(!empty($values['assignTo'])) {
				$user = Repository::findAll('vManager\Security\User')->where('[username] = %s', $values['assignTo'])->fetch();
				$newAssignedTo = $user !== false ? $user : null;
			} else
				$newAssignedTo = null;
			
			if(!$changed)
				$changed = $newAssignedTo === null
					? ($ticket->assignedTo !== null)
					: ($ticket->assignedTo === null || $newAssignedTo->id != $ticket->assignedTo->id);
					  			
			$ticket->assignedTo = $newAssignedTo;
		}
		
		foreach(array('name', 'description', 'deadline') as $curr) {
			if(isset($values[$curr]) && $ticket->{$curr} != $values[$curr]) {
				$ticket->{$curr} = $values[$curr];
				$changed = true;
			}
		}
				
		if($changed) {
			$ticket->author = Nette\Environment::getUser()->getIdentity();
			$ticket->timestamp = null;	// Vyuzivam CURRENT_TIMESTAMP defaultu
			$ticket->save();
		}
		
		return $changed;
	}

	protected function getTicket() {
		if($this->getParam('id') === null)
			return null;
		if($this->ticket !== null)
			return $this->ticket;

		$this->ticket = Repository::findAll('vManager\\Modules\\Tickets\\Ticket')
							 ->where('[revision] > 0 AND [ticketId] = %i', $this->getParam('id'))->fetch();

		return $this->ticket;
	}

}
