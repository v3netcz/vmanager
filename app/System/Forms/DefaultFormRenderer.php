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

namespace vManager\Forms;

use vManager,
	 vBuilder,
	 Nette;

/**
 * Template form renderer
 * 
 * Based on http://addons.nette.org/cs/templaterenderer
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 14, 2011
 */
class DefaultFormRenderer extends Nette\Object implements Nette\Forms\IFormRenderer {

	/**
	 * Template path
	 * @var string
	 */
	private $template;
	/**
	 * Form
	 * @var Form
	 */
	protected $form;
	/**
	 * Callback
	 * @var array
	 */
	public $onBeforeRender = array();

	/**
	 * @param string $template
	 */
	function __construct($template = null) {
		if($template === null)
			$template = __DIR__.'/../Templates/Forms/default.latte';

		$this->setTemplate($template);
	}

	/**
	 * Getts template path
	 * @return string
	 */
	function getTemplate() {
		if(!$this->template) {
			throw new InvalidStateException("Template is not set!");
		}
		return $this->template;
	}

	/**
	 * Setts template path
	 * @param string $template
	 * @return FormTemplateRenderer Provides fluent interface.
	 */
	function setTemplate($template) {
		if(!file_exists($template)) {
			throw new InvalidStateException("Template not found!");
		}
		$this->template = $template;
		return $this;
	}

	/**
	 * Renders the form
	 * @param Form $form
	 */
	function render(Nette\Forms\Form $form) {
		$this->form = $form;
		$this->onBeforeRender($this);

		// Creates template
		$template = $this->createTemplate()->setFile($this->getTemplate());
		$template->form = $form;
		$template->render = $this;
		$template->render();
	}

	/**
	 * Creates template
	 * @return Template
	 */
	public function createTemplate($file = null) {
		$template = new Nette\Templating\FileTemplate($file);
		$presenter = $this->form->getParent()->getPresenter(FALSE);
		$template->onPrepareFilters[] = array("vManager\Forms\DefaultFormRenderer", 'templatePrepareFilters');
		$template->presenter = $presenter;
		$template->baseUri = Nette\Environment::getVariable('baseUri');
		$template->basePath = rtrim($template->baseUri, '/');
		return $template;
	}

	/**
	 * Descendant can override this method to customize template compile-time filters.
	 * @param  Template
	 * @return void
	 */
	public static function templatePrepareFilters($template) {
		// default filters
		$template->registerFilter(new Nette\Latte\Engine);
	}

	// Rendering helpers ->>

	/**
	 * @var ConventionalRenderer
	 */
	private $convenctionalRenderer;

	/**
	 * @return ConventionalRenderer
	 */
	private function getConventionalRenderer() {
		if(!$this->convenctionalRenderer)
			$this->convenctionalRenderer = new Nette\Forms\Rendering\DefaultFormRenderer();
		return $this->convenctionalRenderer;
	}

	function renderBegin() {
		echo $this->getConventionalRenderer()->render($this->form, "begin");
		return $this;
	}

	function renderErrors() {
		echo $this->getConventionalRenderer()->render($this->form, "errors");
		return $this;
	}

	function renderBody() {
		echo $this->getConventionalRenderer()->render($this->form, "body");
		return $this;
	}

	function renderEnd() {
		echo $this->getConventionalRenderer()->render($this->form, "end");
		return $this;
	}

}
