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
 * @Column(project, type="OneToOne", entity="vManager\Modules\Tickets\Project", joinUsing="projectId")  
 * 
 * TODO: Pak predelat na stavy dle analyzy
 * @Column(state, type="integer")
 */
class Ticket extends vBuilder\Orm\ActiveEntity {
	const STATE_OPENED = 1;
	const STATE_CLOSED = 0;

	/** @var array of function(Ticket $t); event handlers for performing actions after new ticket creation */
	public static $onTicketCreated = array();
	/** @var array of function(Ticket $t); event handlers for performing actions depending on ticket update */
	public static $onTicketUpdated = array();

	/**
	 * Overloaded constructor for setting event handlers
	 * 
	 * @param array data
	 */
	public function __construct(array $data = array()) {
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
	 * Return true, if ticket is opened
	 * 
	 * @return bool
	 */
	function isOpened() {
		return $this->state == self::STATE_OPENED;
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
			$t2 = new self(array('ticketId' => $t1->id, 'revision' => 0 - ($t1->revision - 1)));
			if(!$t2->exists()) return array();
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
				
			} elseif($field == 'priority' && $t1->$field !== null) {
				if($t2->$field === null || $t2->$field->id != $t1->$field->id) {
					$change = _x('Changed priority to <strong class="value">%s</strong>', array($t1->$field->exists()
										? $t1->$field->label : __('unknown priority') ));
				}	
				
			} elseif($t2->$field != $t1->$field) {
				if(is_object($t1->$field) && $t1->$field instanceof vBuilder\Orm\DataTypes\DateTime)
					$change = _x('Changed field <strong class="field">%s</strong> to <strong class="value">%s</strong>', array($field, $t1->$field->format("d. m. Y")));
				elseif($field == 'state')
					$change = $t1->isOpened() ? __('Reopened this ticket') : __('Resolve this ticket as done');
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

}
