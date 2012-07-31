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

namespace vManager\Modules\System;

use vManager, Nette, Nette\Application\Responses\TextResponse,
	Nette\Application\Responses\JsonResponse,
	Nette\Image,
	vBuilder\Utils\File,
	vBuilder\Utils\Strings;

/**
 * Texy! presenter.
 *
 * @author Jirka Vebr
 */
class TexyPresenter extends SecuredPresenter {
	
	protected function getTexy() {
		return $this->context->texy;
	}
	
	public function actionPreview() {
		$texy = $this->getTexy();
		$httpRequest = $this->context->httpRequest;
		$this->sendResponse(new TextResponse($texy->process($httpRequest->getPost('texy'))));
	}
	
	/**
	 * This is just a temporary solution designed for test purposes only.
	 * Will be changed, of course
	 * @param string $term
	 */
	public function actionPromptClassName($term) { /* has to be called $term!!! 
		jQuery autocomplete does not support any other parameter name
	 */
		if ($this->isAjax() && Strings::length($term) > 3) { // Only ajax calls are intended to use this method.
			$result = $this->context->apiManager->searchApi($term);
			$this->sendResponse(new JsonResponse($result));
		}
		$this->terminate();
	}
	
	/**
	 * @param string $term 
	 */
	public function actionPromptMemberName($class, $term) { /* has to be called $term!!! 
		jQuery autocomplete does not support any other parameter name
	 */
		if ($this->isAjax() && isset($class) && Strings::length($term) > 2) {
			$result = $this->context->apiManager->searchForMember($class, $term);
			$this->sendResponse(new JsonResponse($result));
		}
		$this->terminate();
	}
	
	
	/**
	 * @param string $term 
	 */
	public function actionPromptTicketName($term) { /* has to be called $term!!! 
		jQuery autocomplete does not support any other parameter name
	 */
		if (class_exists('vManager\Modules\Tickets') // Modules may be 'disabled' by deletion..
				&& vManager\Modules\Tickets::getInstance()->isEnabled()
				&& $this->isAjax()
				&& Strings::length($term) > 3) {
			$search = $this->context->repository->findAll('vManager\Modules\Tickets\Ticket')
								->where('[name] LIKE %~like~',$term)
									->and('[revision] > 0')
								->limit('10')
								->fetchAll();
			$result = array ();
			foreach ($search as $entity) {
				$result[] = '#'.$entity->id.' '.$entity->name;
			}
			$this->sendResponse(new JsonResponse($result));
		}
		$this->terminate();
	}
	
	/**
	 * Every time anything is uploaded via texyla, a random token is used. This token
	 * distinguishes not only wiki, tickets or other possible implementations, but also
	 * every particular instance, or a session really. This method makes sure that even
	 * if the user decides to attach more files with identical names (from different directories,
	 * for instance), it will work. Since $filename is not a unique identifier of a file,
	 * $fileNumber is used. It can be any integer, really but it makes more sense to
	 * number the files as the user adds them... 
	 * @param type $token
	 * @param type $fileNumber
	 * @param type $filename 
	 */
	public function actionGetFinalFilename($token, $fileNumber, $filename) {
		if ($this->isAjax()) {
			$uploadManager = $this->context->uploadManager;
			
			
			///////////// BERLE JAKO KRÁVA ///////////
			/// WILL BE CHANGED!!!!
			$referer = $this->context->httpRequest->getReferer();
			$path = $referer->path;
			preg_match('~(\d+)$~', $path, $matches);
			$uploadManager->addNonConflictDirectory('/attachments/tickets/'.intval($matches[1]));
			/// FAIL!!!
			
			
			$finalFilename = $uploadManager->addFile($token, $fileNumber, $filename);
			$this->sendResponse(new JsonResponse(array (
				'finalFilename' => $finalFilename
			)));
		}
		$this->terminate();
	}
	
	
	public function actionTexylaImage($id, $source, $width = null, $height = null) {
		// todo: permissions
		// todo: put this somewhere else - I don't know where exactly to put this.
		//		 The FilePresenter does not really support custom real parameters
		//		 and I didn't know whether I could edit it. However, this can be
		//		 simply moved somewhere else and so I don't think that that's a 
		//		 real issue.
		
		$maxSizeLimit = 3500;
		$minSizeLimit = 10;
		ctype_digit($width) && $width = min($maxSizeLimit, max($minSizeLimit, $width));
		ctype_digit($height) && $height = min($maxSizeLimit, max($minSizeLimit, $height));
		
		$baseDir = realpath($this->context->parameters['upload']['dir'].'/attachments/tickets/');
		$tempPath = TEMP_DIR . '/imageCache/' . md5($width.$source.$height.'_'.$id) . '.' . File::getExtension($source);
		
		if (file_exists($tempPath)) {
			Image::fromFile($tempPath)->send();
			$this->terminate();
		}
		
		$imagePath = realpath($baseDir . '/' . $id . '/' . $source);
		
		if (Strings::startsWith($imagePath, $baseDir) && file_exists($imagePath)) { // defence against ../../../
			$image = Image::fromFile($imagePath);
			if ($width || $height) {
				$image->resize($width, $height);
			}
			$image->save($tempPath);
			$image->send();
		} else {
			// preview
			
			
			// will send a dummy image:
			$width = 15*Strings::length($source);
			$height = 30;
			$dummy = Image::fromBlank($width, $height, Image::rgb(255, 255, 255));	

			$dummy->ftText(16, 0, 5, 20, $dummy->colorAllocate(0, 0, 0), realpath(LIBS_DIR.'/Captcha/fonts/Vera.ttf'), $source);
			$dummy->send();
		}
	}
}
