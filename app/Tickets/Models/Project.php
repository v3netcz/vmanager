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

use vManager, vBuilder, Nette;

/**
 * Project entity data class
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 14, 2011
 * 
 * @Table(name="pm_projects")
 * 
 * @Behavior(Versionable, idField = "id", revisionField = "revision")
 * 
 * @Column(id, realName="projectId", pk, type="integer")
 * @Column(revision, pk, type="integer")
 * @Column(author, type="OneToOne", entity="vManager\Security\User", joinOn="author=id")
 * @Column(comment, realName="commentId", type="OneToOne", entity="vManager\Modules\Tickets\Comment", joinOn="comment=id")
 * @Column(name, type="string")
 * @Column(deadline, type="DateTime")
 * @Column(assignedTo, type="OneToOne", entity="vManager\Security\User", joinOn="assignedTo=id")
 * @Column(timestamp, type="DateTime")
 * @Column(description, type="string")
 */
class Project extends vBuilder\Orm\ActiveEntity {

	private $_resolvedTicketCount;
	private $_ticketCount;
	private $_lastTicketModificationTime;
	
	/**
	 * Returns true if project has some unresolved tasks
	 * 
	 * @return bool
	 */
	function isInProgress() {
		return $this->ticketCount > 0 && $this->ticketCount != $this->resolvedTicketCount;
	}
	
	/**
	 * Returns count of project tickets
	 * 
	 * @return int
	 */
   function getTicketCount($closedOnly = false) {
		 if($this->_ticketCount === null) {
			 $tickets = $this->context->repository->findAll('vManager\Modules\Tickets\Ticket')
					->where('[revision] > 0 AND [projectId] = %i', $this->data->id);
			 
			 $this->_ticketCount = count($tickets);
		 }
		 
		
    return $this->_ticketCount;
   }
	 
	 /**
	  * Returns number of project tickets which has been resolved already
	  * 
	  * @return int 
	  */
	 function getResolvedTicketCount() {
		 if($this->_resolvedTicketCount === null) {
			 $tickets = $this->context->repository->findAll('vManager\Modules\Tickets\Ticket')
					->where('[revision] > 0 AND [projectId] = %i', $this->data->id)->and('[state] = %i', Ticket::STATE_CLOSED);
			 
			 $this->_resolvedTicketCount = count($tickets);
		 }
		 
		
    return $this->_resolvedTicketCount;
	 }
	 
	 /**
	  * Returns time of last modification of any project ticket
	  * 
	  * @return \DateTime
	  */
	 function getLastTicketModificationTime() {
		 if($this->_lastTicketModificationTime === null) {
			 $lastTicketRevision = $this->context->repository->findAll('vManager\Modules\Tickets\Ticket')
					->where('[revision] > 0 AND [projectId] = %i', $this->data->id)->orderBy('[timestamp] DESC')->fetch();
			 
			 if($lastTicketRevision)
				$this->_lastTicketModificationTime = $lastTicketRevision->timestamp;
		 }
		 
		 return $this->_lastTicketModificationTime;
	 }
   
	/**
	 * Returns array with changes from $t2
	 * 
	 * @param Ticket $t2 
	 * @return array of messages
	 */
	function diffLogAgainst(Project $t2 = null) {
		$t1 = $this;
		if($t1->revision < 2) return array();
		
		if($t2 === null) {
			$t2 = $this->context->repository->get('vManager\Modules\Tickets\Comment', array('ticketId' => $t1->id, 'revision' => 0 - ($t1->revision - 1)));
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
