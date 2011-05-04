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

use vManager, Nette, vBuilder\Orm\Repository, Gridito, Nette\Application\UI\Form;

/**
 * Presenter for viewing tickets
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 27, 2011
 */
class TicketPresenter extends vManager\Modules\System\SecuredPresenter {
	
	/** @var Ticket */
	protected $ticket;
	
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
				 echo Nette\Utils\Html::el("a")->href($link)->setText('#' . $row->ticketId);
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
	 * Sign in form component factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentUpdateForm() {				
		$form = new Form;
		
		$ticket = $this->getTicket();
		
		$form->addTextArea('comment')->setAttribute('class', 'texyla');
		$form->addTextArea('description')->setValue($ticket->description)->setAttribute('class', 'texyla');
		
		$form->addSubmit('send', __('Send'));

		$form->onSubmit[] = callback($this, 'updateFormSubmitted');
		
		return $form; 
	}
	
	public function updateFormSubmitted(Form $form) {
		$values = $form->getValues();

		$ticket = $this->getTicket();
		
		if(isset($values['comment'])) {
			$ticket->comment = new Comment();
			$ticket->comment->text = $values['comment'];
		}
		
		if(isset($values['description'])) {
			$ticket->description = $values['description'];
		}
		
		$ticket->save(); 
		
		$this->flashMessage(__('Change has been saved.'));
	}
	
	protected function getTicket() {
		if($this->ticket !== null) return $this->ticket;
		
		$this->ticket = Repository::findAll('vManager\\Modules\\Tickets\\Ticket')
				  ->where('[revision] > 0 AND [ticketId] = %i', $this->getParam('id'))->fetch();
		
		return $this->ticket;
	}
	
}
