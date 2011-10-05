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
		vManager\Mailer,
		vBuilder\Orm\Repository,
		Nette;

/**
 * Event handler for ticket update and ticket creation.
 * It takes care for notifying all ticket subscribers about it's change.
 *
 * @author Adam Staněk (velbloud)
 * @since May 13, 2011
 */
class TicketChangeMailer {
	
	static function ticketCreated(Ticket $t) {
		if($t->assignedTo === null || !$t->assignedTo->exists() || empty($t->assignedTo->email)) return ;
		
		
		$tpl = self::createMailTemplate(__DIR__ . '/../Templates/Emails/ticketCreated.latte');
		$tpl->ticket = $t;
		
		$mail = Mailer::createMail();
		$mail->setSubject(_x('[NEW TASK] %s', array($t->name)));
		$mail->addTo($t->assignedTo->email);
		$mail->setHtmlBody($tpl);
		
		Mailer::getMailer()->send($mail);
	}
	
	static function ticketUpdated(Ticket $t) {
		$recipients = self::getRecipients($t);
		if(count($recipients) == 0) return ;
		
		$tpl = self::createMailTemplate(__DIR__ . '/../Templates/Emails/ticketUpdated.latte');
		$tpl->ticket = $t;
		
		$mail = Mailer::createMail();
		$mail->setSubject(_x('[UPDATE] %s', array($t->name)));
		
		foreach($recipients as $recipient => $user) {
			$tpl->user = $user;
			
			$mail2 = clone $mail;
			$mail2->addTo($recipient);
			$mail2->setHtmlBody($tpl);

			Mailer::getMailer()->send($mail2);
		}
	}
	
	/**
	 * Sets up email template
	 * 
	 * @param string file path
	 * @return FileTemplate
	 */
	protected static function createMailTemplate($filename) {
		$tpl = Mailer::createMailTemplate($filename);
		
		$texy = new \Texy();
		$texy->encoding = 'utf-8';
		$texy->allowedTags = \Texy::NONE;
		$texy->allowedStyles = \Texy::NONE;
		$texy->setOutputMode(\Texy::XHTML1_STRICT);

		$tpl->registerHelper('texy', callback($texy, 'process'));
		return $tpl;
	}
	
	/**
	 * Returns array of all recipients for ticket
	 * 
	 * @param Ticket
	 * @return array mail_addr => vManager\Security\User
	 */
	protected static function getRecipients(Ticket $t) {
		$recipients = array();
		
		if($t->assignedTo !== null && $t->assignedTo->exists() && !empty($t->assignedTo->email)) {
			if($t->author === null || $t->author->id != $t->assignedTo->id) {
				$recipients[$t->assignedTo->email] = $t->assignedTo;
			}
		}				  
		
		$allRevisions = Nette\Environment::getContext()->repository->findAll('vManager\\Modules\\Tickets\\Ticket')
				  ->where('[ticketId] = %i', $t->id)->fetchAll();		
		
		foreach($allRevisions as $curr) {
			if($curr->author != null && $curr->author->exists()) {
				if($t->author == null || $t->author->id != $curr->author->id) {
					if(!empty($curr->author->email) && !isset($recipients[$curr->author->email])) {
						$recipients[$curr->author->email] = $curr->author;
					}
				}
			}
		}
				
		return $recipients;
	}
	
}
