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
	 vBuilder\Orm\Repository;

/**
 * Project entity data class
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 27, 2011
 * 
 * @Table(name="pm_tickets")
 * 
 * @Behavior(Versionable, idField = "id", revisionField = "revision")
 * 
 * @Column(id, realName="ticketId", pk, type="integer")
 * @Column(revision, pk, type="integer")
 * @Column(author, type="OneToOne", entity="vManager\Security\User", joinOn="author=id")
 * @Column(comment, realName="commentId", type="OneToOne", entity="vManager\Modules\Tickets\Comment", joinOn="comment=id")
 * @Column(name, type="string")
 * @Column(description, type="string")
 * @Column(deadline, type="DateTime")
 * @Column(assignedTo, type="OneToOne", entity="vManager\Security\User", joinOn="assignedTo=id")
 * @Column(timestamp, type="DateTime")
 * @Column(priority, type="OneToOne", entity="vManager\Modules\Tickets\Priority", joinOn="priority=id")
 * @Column(project, realName="projectId", type="OneToOne", entity="vManager\Modules\Tickets\Project", joinOn="project=id")  
 * @Column(state)
 */
class Ticket extends vBuilder\Orm\ActiveEntity {

	/** @var array of function(Ticket $t); event handlers for performing actions after new ticket creation */
	public static $onTicketCreated = array();
	/** @var array of function(Ticket $t); event handlers for performing actions depending on ticket update */
	public static $onTicketUpdated = array();

	/**
	 * Overloaded constructor for setting event handlers
	 * 
	 * @param array data
	 */
	public function __construct($data = array()) {
		call_user_func_array(array('parent', '__construct'), func_get_args());

		// Nastavim staticke eventy pro vsechny tickety
		$this->onCreate[] = function (Ticket $t) {
					  if($t->revision == 1)
						  Ticket::onTicketCreated($t);
					  else
						  Ticket::onTicketUpdated($t);
				  };
	}

	/**
	 * Returns ticket state
	 * 
	 * @return ITicketState 
	 */
	function getState() {
			return vManager\Modules\Tickets::getInstance()
							->getTicketState($this->data->state);
	}
	
	/**
	 * Sets ticket state
	 * 
	 * @param ITicketState|string ticket state (instance or id)
	 */
	function setState($state) {
			// Kvuli overeni ze stav existuje
			if(!($state instanceof ITicketState))
				$state = vManager\Modules\Tickets::getInstance()
						->getTicketState($state);
			
			// Pokud se nejedna o prvotni nastaveni stavu musim overit,
			// jestli novy stav je validnim naslednikem
			if($this->data->state != "") {

				// Odkudkoliv muzeme zmenit stav ticketu na pocatecni stav
				if(!$state->isInitial()) {
					$found = false;
					
					foreach($this->state->successorIds as $succId) {
						if($succId == $state->id) {
							$found = true;
							break;
						}
					}
					
				if(!$found)
					throw new Nette\InvalidStateException("Cannot change ticket state from '".$this->state->id."' to '".$state->id."'");
				}	
				
			} elseif(!$state->isInitial()) {
					throw new Nette\InvalidStateException("State '".$state->id."' is not valid initial state of ticket");
			}
			
			$this->data->state = $state->id;
	}
	
	/**
	 * Returns array of posibnle succeeding states
	 * 
	 * @return array of ITicketState
	 */
	function getPossibleStates() {
		return $this->state->isFinal()
						? vManager\Modules\Tickets::getInstance()->initialTicketStates
						: $this->state->successors;;
	}
	
	/**
	 * Custom project getter because of Versionable
	 *  (have only one PK => ID, don't know the revision)
	 */
	function getProject() {
		// Pokud byl predtim promo naassignovanej projekt
		if(is_object($this->data->project) || $this->data->project === null) return $this->data->project;
		
		if(($cached = $this->fieldCache("project")) !== null) return $cached;
		
		$value = $this->context->repository->findAll('vManager\Modules\Tickets\Project')
			->where('[revision] > 0 AND [projectId] = %i', $this->data->project)->fetch();
		
		if(!$value) return null;		
		return $this->fieldCache("project", $value);
	}
	
	/**
	 * Returns array with changes from $t2
	 * 
	 * @param Ticket $t2 
	 * @return array of messages
	 */
	function diffLogAgainst(Ticket $t2 = null) {
		$t1 = $this;
		if($t1->revision < 2) return array();
		
		if($t2 === null) {
			$t2 = $this->context->repository->get('vManager\Modules\Tickets\Ticket', array('ticketId' => $t1->id, 'revision' => 0 - ($t1->revision - 1)));
			
			if(!$t2 || !$t2->exists()) return array();
		}
		
		
		$log = array();
		
		foreach($t2->getMetadata()->getFields() as $field) {
			if(in_array($field, array('timestamp', 'author', 'revision', 'comment')))
				continue;

			$change = null;

			if($field == 'assignedTo') {
				if($t1->$field === null) {
					if($t2->$field !== $t1->$field)
						$change = __('Reassigned to <strong class="value">nobody</strong>');
				} elseif($t2->$field === null || $t2->$field->id != $t1->$field->id) {
					$change = _x('Reassigned to <strong class="value">%s</strong>', array($t1->$field->exists()
										? $t1->$field->username : _x('User n. %d', array($t1->$field->id))));
				}
				
			} elseif($field == 'project') {
				if($t1->$field === null) {
					if($t2->$field !== $t1->$field)
						$change = _x('Removed task from project <strong class="value">%s</strong>', array($t2->$field->name));
				} elseif($t2->$field === null || $t2->$field->id != $t1->$field->id) {
					$change = _x('Moved task under project <strong class="value">%s</strong>', array($t1->$field->exists()
										? $t1->$field->name : _x('Project n. %d', array($t1->$field->id))));
				}
				
			} elseif($field == 'priority' && $t1->$field !== null) {
				if($t2->$field === null || $t2->$field->id != $t1->$field->id) {
					$change = _x('Changed priority to <strong class="value">%s</strong>', array($t1->$field->exists()
										? $t1->$field->label : __('unknown priority') ));
				}	
				
			} elseif($t2->$field != $t1->$field) {
				if(is_object($t1->$field) && $t1->$field instanceof vBuilder\Orm\DataTypes\DateTime)
					$change = _x('Changed field <strong class="field">%s</strong> to <strong class="value">%s</strong>', array($field, $t1->$field->format("d. m. Y")));
				elseif($field == 'state') {
					$module = vManager\Modules\Tickets::getInstance();
					
					if(!$t1->state->isFinal() && $t2->state->isFinal() && count($module->initialTicketStates) < 2)
							$change = __('Reopened this ticket');
					elseif($t1->state->isFinal())
							$change = _x('Resolved this ticket as <strong class="field">%s</strong>', array($t1->state->name));
					else
							$change = _x('Changed ticket state to <strong class="field">%s</strong>', array($t1->state->name));

				} elseif($t1->$field == "") 
					$change = _x('Unset field <strong class="field">%s</strong>', array($field));
				elseif(strlen($t1->$field) < 40)
					$change = _x('Changed field <strong class="field">%s</strong> to <strong class="value">%s</strong>', array($field, $t1->$field));
				else
					$change = _x('Changed field <strong class="field">%s</strong>', array($field));
			}

			if($change !== null)
				$log[] = "<div class=\"change\">$change</div>";
		}
		
		return $log;
	}
	
  public function isResponsibleUser(vBuilder\Security\User $user) {
      if ($this->data->assignedTo !== null) {
        if ($this->data->assignedTo == $user->id) 
          return true;
      }
      if ($this->data->project !== null) {
        return $this->project->isResponsibleUser($user);
      }    
      return false;
  }
}
