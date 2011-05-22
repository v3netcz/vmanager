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

namespace vManager;

use vManager,
	 vBuilder,
	 Nette,
	 Nette\Mail\Message,
	 Nette\Templating\FileTemplate;

/**
 * Helper class for sending mail notifications and other system e-mails.
 *
 * @author Adam Staněk (velbloud)
 * @since May 13, 2011
 */
class Mailer extends vBuilder\Object {

	/**
	 * Helper function for creating mail object and setting it's default properties
	 * 
	 * @return Nette\Mail\Mail
	 */
	static function createMail() {
		$mail = new Message;
		
		$config = self::getConfig();
		
		$mail->setFrom(
			isset($config['sender']) ? $config['sender'] : 'info@vmanager.cz',
			isset($config['senderName']) ? $config['senderName'] : 'vManager'
		);
		
		return $mail;
	}
	
	/**
	 * Helper function for creating mail template from file.
	 * Function sets up all necessary filters.
	 * 
	 * @param string|null file path
	 */
	static function createMailTemplate($fromFile = null) {
		$emailTemplate = new FileTemplate;
		
		if($fromFile !== null)
			$emailTemplate->setFile($fromFile);
		
		$emailTemplate->basePath = Modules\System::getBasePath(true);
		$emailTemplate->control = Nette\Environment::getApplication()->getPresenter();
		
		$emailTemplate->registerFilter(new Nette\Latte\Engine);
		return $emailTemplate;
	}
	
	/**
	 * Returns IMailer implementation class.
	 * 
	 * @return Nette\Mail\IMailer mailer
	 */
	static function getMailer() {
		return Nette\Environment::getService('Nette\Mail\IMailer');
	}
	
	/**
	 * Returns mailer configuration array
	 * 
	 * @return array
	 */
	static function getConfig() {
		return (array) Nette\Environment::getConfig('mailer', array());
	}
	
}
