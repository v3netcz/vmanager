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
	vBuilder\Utils\Strings;

/**
 * Texy! presenter.
 *
 * @author Jirka Vebr
 */
class TexyPresenter extends SecuredPresenter {
	
	protected function getTexy() {
		$texy = $this->context->texy;
		$texy->setPresenter($this);
		return $texy;
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
		if ($this->isAjax()) { // Only ajax calls are intended to use this method.
			$classes = get_declared_classes(); // dummy values
			$result = array ();
			foreach ($classes as $class) {
				if (Strings::contains($class, $term, false)) { // false -> case insensitive
					$result[] = $class;
				}
			}
			$this->sendResponse(new Nette\Application\Responses\JsonResponse($result));
		}
	}
}
