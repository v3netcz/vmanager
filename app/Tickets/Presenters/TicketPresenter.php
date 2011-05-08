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
		$form->setRenderer(new Nette\Forms\Rendering\DefaultFormRenderer());

		$form->addText('name', __('Name:'))->setAttribute('title', __('Short task description. Please be concrete.'))
			 ->addRule(Form::FILLED);
		
		/*
		$form->addDatePicker('deadline', __('Deadline:'))->setAttribute('title', __('When has to be task done?'));

		$form->addText('assignTo', __('Assign to:'))
				  ->setAttribute('autocomplete-src', $this->link('suggestAssignTo'))
				  ->setAttribute('title', __('Who will resolve this issue?'));

		$form->addSelect('priority', __('Priority:'), array(
			__('Low'), __('Normal'), __('High') 
		)); */
		
		$form->addTextArea('description')
				  ->getControlPrototype()->class('texyla');

		$form->addSubmit('send', __('Save'));

		$form->onSubmit[] = callback($this, 'createFormSubmitted');

		return $form;
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

		$form->addSubmit('send', __('Save'));

		$form->onSubmit[] = callback($this, 'updateFormSubmitted');

		return $form;
	}

	public function updateFormSubmitted(Form $form) {
		$values = $form->getValues();
		$ticket = $this->getTicket();

		$this->saveTicket($ticket, $values);

		$this->flashMessage(__('Change has been saved.'));
		$this->redirect('this');
	}

	protected function saveTicket(Ticket $ticket, $values) {
		if(isset($values['comment'])) {
			$ticket->comment = new Comment();
			$ticket->comment->text = $values['comment'];
		}

		if(isset($values['name'])) $ticket->name = $values['name'];
		if(isset($values['description'])) $ticket->description = $values['description'];
		
		$ticket->author = Nette\Environment::getUser()->getIdentity();

		$ticket->save();
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
