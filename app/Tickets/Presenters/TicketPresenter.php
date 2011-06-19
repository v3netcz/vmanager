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

	/**
	 * Ticket listing table grid component factory
	 * 
	 * @param string $name
	 * @return vManager\Grid
	 */
	protected function createComponentTicketListingGrid($name) {
		$grid = new vManager\Grid($this, $name);

		$uid = Nette\Environment::getUser()->getId();
		$table = Ticket::getMetadata()->getTableName();

		$ds = Repository::findAll('vManager\\Modules\\Tickets\\Ticket')
				  ->as('d')
				  ->leftJoin(Priority::getMetadata()->getTableName())->as('p')->on('[priority] = [p.id]')
				  ->where('[revision] > 0');

		// Pokud to neni spravce ticketu, zobrazuju jen tickety, ktere uzivatel zalozil
		// nebo kterym je prirazen
		if(!Nette\Environment::getUser()->getIdentity()->isInRole('Ticket admin'))
			$ds->and("([assignedTo] = %i OR [author] = %i OR ([revision] > 1 AND EXISTS (SELECT * FROM [$table] WHERE [author] = %i AND [revision] = -1 AND [ticketId] = [d.ticketId])))", $uid, $uid, $uid);

		
		if($grid->sortColumn === null)
			$ds->orderBy('[state] DESC, IF([deadline] IS NULL, 0, 1) DESC, [deadline], [p.weight] DESC, [assignedTo], [ticketId]');

		$grid->setModel(new Gridito\DibiFluentModel($ds, 'vManager\\Modules\\Tickets\\Ticket'));
		$grid->setItemsPerPage(20);

		$grid->setRowClass(function ($iterator, $ticket) {
					  $classes = array();

					  if(!$ticket->isOpened())
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
				 echo Nette\Utils\Html::el("a")->href($link)->setText($ticket->name);
			 },
			 "sortable" => true,
		));

		// Resitel ukolu
		//if(Nette\Environment::getUser()->getIdentity()->isInRole('Ticket admin')) {
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
					 echo "-";
					 return;
				 }

				 echo Nette\Utils\Html::el("abbr")->title($ticket->deadline->format("d. m. Y"))->setText(vManager\Application\Helpers::timeAgoInWords($ticket->deadline));
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
				 echo Nette\Utils\Html::el("span")->setText(!$ticket->isOpened() ? __('Closed')
										  : __('Opened'));
			 },
			 "sortable" => true,
		))->setCellClass("state");
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


		$prioritiesDs = Repository::findAll('vManager\\Modules\\Tickets\\Priority');
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

		$values['reopen'] = true;

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
		if($ticket->assignedTo !== null && $ticket->assignedTo->exists())
			$form['assignTo']->setValue($ticket->assignedTo->username);

		if($ticket->priority !== null && $ticket->priority->exists())
			$form['priority']->setValue($ticket->priority->id);

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
				$changed = $newAssignedTo === null ? ($ticket->assignedTo !== null) : ($ticket->assignedTo === null || $newAssignedTo->id != $ticket->assignedTo->id);

			$ticket->assignedTo = $newAssignedTo;
		}

		if(isset($values['priority'])) {
			$priority = Repository::get('vManager\\Modules\\Tickets\\Priority', $values['priority']);
			$ticket->priority = $priority->exists() ? $priority : null;
			$changed = true;
		}

		foreach(array('name', 'description', 'deadline') as $curr) {
			if(isset($values[$curr]) && $ticket->{$curr} != $values[$curr]) {
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
