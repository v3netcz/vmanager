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

use vManager, vBuilder, Nette,
	vBuilder\Utils\Strings,
	vManager\MultipleFileUploadControl as MFU,
	Nette\Utils\Html;

/**
 * Base vManager Texy
 * 
 * @author Jirka
 */
class Texy extends \Texy {
	
	/**
	 * @var Nette\DI\IContainer
	 */
	protected $context;
	
	public function __construct(Nette\DI\IContainer $context) {
		parent::__construct();
		$this->context = $context;
		
		$this->encoding = 'utf-8';
		$this->allowedTags = static::NONE;
		$this->allowedStyles = static::NONE;
		$this->setOutputMode(static::XHTML1_STRICT);
		
		$this->addHandler('phrase', array($this, 'apiLinkHandler'));
		$this->addHandler('image', array($this, 'fileHandler'));
		
		// We want ticket links only if the module is enabled...
		$modules = vManager\Application\ModuleManager::getModules();
		foreach ($modules as $module) {
			if ($module instanceof vManager\Modules\Tickets && $module->isEnabled()) {
				$this->addHandler('phrase', array($this, 'ticketLinkHandler'));
			}
		}
	}
	
	/** 
	 * "My link to API":api://namespace\namespace\Class
	 *		OR
	 * "My link to API":api://namespace\namespace\Class::method
	 *		OR
	 * "My link to API":api://namespace\namespace\Class::$property
	 *		OR
	 * "My link to API":api://namespace\namespace\Interface
	 * @param type $invocation
	 * @param type $phrase
	 * @param type $content
	 * @param type $modifier
	 * @param type $link 
	 */
	public function apiLinkHandler($invocation, $phrase, $content, $modifier, $link) {
		if (!$link) {
			return $invocation->proceed();
		}
		$url = $link->URL;

		if (Strings::startsWith($url, 'api://')) {
			$url = Strings::substring($url, 6);
			if (Strings::contains($url, '::')) { //class::method
				list($class, $member) = explode('::', $url);
			} else {
				$class = $url;
				$member = null;
			}
			$link->URL = $this->context->apiManager->generateApiLink($class, $member);
		}

		return $invocation->proceed();
	}
	
	/**
	 * "My link to ticket 123":#123
	 * -> Just like an ordinary Texy! link.
	 * @param type $invocation
	 * @param type $phrase
	 * @param type $content
	 * @param type $modifier
	 * @param type $link 
	 */
	public function ticketLinkHandler($invocation, $phrase, $content, $modifier, $link) {
		if (!$link) {
			return $invocation->proceed();
		}
		$url = $link->URL;

		if (Strings::match($url, '~^#\d+$~')) {
			$id = (int) Strings::substring($url, 1);
			$link->URL = $this->context->application->presenter->link(':Tickets:Ticket:detail', $id);
		}

		return $invocation->proceed();
	}
	
	/**
	 * @param TexyHandlerInvocation handler invocation
	 * @param TexyImage
	 * @param TexyLink
	 * @return TexyHtml|string|false
	 */
	public function fileHandler($invocation, $image, $link) {
		if (false) { // is this file uploaded, ... ? -> to be changed
			$invocation->proceed();
		}
		
		$file = vBuilder\Utils\File::isImage($image->URL) ?
				$this->getImageHtml($image) :
				$this->getFileHtml($image);
		
		return $this->protect((string) $file, Texy::CONTENT_BLOCK);
	}
	
	protected function getImageHtml($image) {
		$params = array ();
		
		$image->width && $params['width'] = $image->width;
		$image->height && $params['height'] = $image->height;
		if (!$image->width && !$image->height) {
			$params['width'] = $image->width = 250;
		}
		$params['id'] = $this->context->application->presenter->getParam('id');
		$params['source'] = $image->URL;
		
		$linkParams = $params;
		unset($linkParams['width'], $linkParams['height']);
		$link = Html::el('a', array (
			'href' => $this->context->application->presenter->link(':System:Texy:texylaImage', $linkParams)
		));
		$link->add(Html::el('img', array (
			'alt' => $image->modifier->title,
			'src' => $this->context->application->presenter->link(':System:Texy:texylaImage', $params)
		)));
		return $link;
	}
	
	protected function getFileHtml($image) {
		$file = Html::el('a', array (
			'class' => $this->getFileClass($image),
			'href' => ''
		));
		$file->setText($image->modifier->title);
		return $file;
	}
	
	protected function getFileClass($image) {
		$extension = vBuilder\Utils\File::getExtension($image->URL);
		$extensions = TexylaUploadControl::getExtensionGroups();
		
		$class = '';
		in_array($extension, $extensions[MFU::IMAGES]) && $class = 'image';
		in_array($extension, $extensions[MFU::ARCHIVES]) && $class = 'archive';
		in_array($extension, $extensions[MFU::OFFICE]) && $class = 'office';
		in_array($extension, $extensions[MFU::PHP]) && $class = 'php';
		return 'file ' . $class;
	}
}
