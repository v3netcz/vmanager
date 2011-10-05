<?php

/**
 * This file is a part of vManager.
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

namespace vManager\Modules\Wiki\Controls;

use vManager\Form;

/**
 * 
 *
 * @author Jirka Vebr
 */
class TreeComments extends \Nette\Application\UI\Control {
	
	/** @var object IRenderer */
	private $renderer;
	
	
	public function setRenderer(IRenderer $renderer) {
		$this->renderer = $renderer;
		return $this;
	}
	
	public function getRenderer() {
		return $this->renderer ?: new ConventionalRenderer();
	}
	
	public function render($what = null) {
		echo $this->getRenderer()->render($this, $what);
	}
	
	public function createTemplate($class = NULL)
	{
		return parent::createTemplate($class);
	}
	
	
	public function createComponentAddForm($name) {
		$form = new Form;
		
		$control = $this;
		$form->onSuccess[] = function ($form) use ($control) {
			if ($control['model']->addReaction($form->values)) {
				$control->flashMessage(__('Your reaction was successfully saved'));
				
				/*if ($control->getPresenter()->isAjax()) {
					$control->invalidateControl('commentsTree');
				} else {*/
					$control->getPresenter()->redirect('this');
				//}				
			} else {
				$form->addError(__('An error occured while saving your reaction. Please try again.'));
			}			
		};
		
		$form->addText('subject', __('Subject:'))
			->addRule(Form::FILLED, __('You have to enter the subject!'));
		$form->addTextarea('text', __('Response:'))
			->addRule(Form::FILLED, __('You have to write the response!'));
		$form->addHidden('parentId');
		$form->addSubmit('s', __('Add!'));
		
		return $this[$name] = $form;
	}
	
	
	public function createComponentModel() {
		$model = new TreeCommentsModel;
		return $model;
	}
	
	public function getModel() {
		return $this['model'];
	}
	
	public function handleReact($id) {
		$this['addForm']->setDefaults(array(
			'parentId' => $id,
			'subject' => 'Re: '.$this->getModel()->getSubjectById($id)
		));	
	}
}