<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vManager is free software: you can redistribute it and/or modify
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

namespace vManager\Timeline;

use vBuilder, Nette;

/**
 * Default record renderer implementation
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 13, 2012
 */
class TemplateRecordRenderer extends vBuilder\Object implements IRecordRenderer {
	
	/** @var Nette\Application\UI\Presenter */
	private $_presenter;

	/** @var Nette\Templating\ITemplate */
	private $_template;

	public function __construct(Nette\Application\UI\Presenter $parentPresenter) {
		$this->_presenter = $parentPresenter;
	}
		
	/**
	 * Returns DI context container
	 *
	 * @return Nette\DI\IContainer
	 */
	public function getContext() {
		return $this->_presenter->getContext();
	}
		
	/**
	 * Returns template
	 * 
	 * @return Nette\Templating\ITemplate 
	 */
	final public function getTemplate() {
		if(!isset($this->_template)) {
			$value = $this->createTemplate();
			if (!$value instanceof Nette\Templating\ITemplate && $value !== NULL) {
				$class2 = get_class($value); $class = get_class($this);
				throw new Nette\UnexpectedValueException("Object returned by $class::createTemplate() must be instance of Nette\\Templating\\ITemplate, '$class2' given.");
			}

			$this->_template = $value;
		}
		
		return $this->_template;
	}
	
	/**
	 * Mail template factory
	 * 
	 * @param string class name to use (if null FileTemplate will be used)
	 * 
	 * @return Nette\Templating\ITemplate
	 */
	protected function createTemplate($class = NULL) {
		// No need for checking class because of getTemplate
		$template = $class ? new $class : new Nette\Templating\FileTemplate;
		$presenter = $this->context->application->getPresenter();
		
		$template->onPrepareFilters[] = callback($this, 'templatePrepareFilters');
		$template->registerHelperLoader('Nette\Templating\Helpers::loader');
		$template->setCacheStorage($this->context->templateCacheStorage);
		
		// default parameters
		$template->renderer = $this;
		$template->presenter = $template->_presenter = $this->_presenter;
		$template->control = $template->_control = $this->_presenter;
		
		$template->context = $this->context;
		$template->baseUri = $template->baseUrl = rtrim($this->context->httpRequest->getUrl()->getBaseUrl(), '/');
		$template->basePath = preg_replace('#https?://[^/]+#A', '', $template->baseUrl);
		$template->user = $this->context->user;
				
		return $template;
	}
	
	/**
	 * Descendant can override this method to customize template compile-time filters.
	 * @param  Nette\Templating\Template
	 * @return void
	 */
	public function templatePrepareFilters($template, &$engine = null) {
		if(!$engine) $engine = new Nette\Latte\Engine;

		$template->registerFilter($engine);
	}

	/**
	 * Renders given timeline record and returns
	 * it's output
	 *
	 * @param IRecord
	 * @return string
	 */
	public function render(IRecord $record) {
		$this->template->record = $record;
		return (string) $this->template;
	}

}