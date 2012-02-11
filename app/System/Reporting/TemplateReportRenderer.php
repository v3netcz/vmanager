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

namespace vManager\Reporting;

use vManager,
	vBuilder,
	Nette;

/**
 * Generic implementation of report renderer
 *
 * @author Adam Staněk (velbloud)
 * @since Jan 28, 2011
 */
abstract class TemplateReportRenderer extends vBuilder\Object {
	
	/** @var string file with report template */
	protected $templateFile;
	
	/** @var Nette\Templating\Template template */
	protected $template;
	
	/**
	 * @var Nette\DI\IContainer DI container
	 * @nodump
	 */
	protected $context;
	
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI container 
	 */
	function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
	}
	
	/**
	 * Sets file path to report template
	 * 
	 * @param string absolute file path
	 */
	function setTemplateFile($filePath) {
		$this->templateFile = $filePath;
	}
		
	/**
	 * Renders report into output buffer
	 */
	abstract function render();
	
	/**
	 * Renders report into file
	 *
	 * @param string absolute file path
	 */
	abstract function renderToFile($filepath);
			
	/**
	 * Creates template instance
	 * 
	 * @return Nette\Templating\FileTemplate
	 * 
	 * @throws Nette\InvalidStateException if template file was not set
	 * @throws Nette\InvalidArgumentException if template file does not exists
	 */
	protected function createTemplate() {
		if(empty($this->templateFile))
			throw new Nette\InvalidStateException("Template file was not set. Forget to call " . get_called_class() . "::setTempplate()?");		
		
		if(!file_exists($this->templateFile))
			throw new Nette\InvalidArgumentException("Invoice template file '$this->templateFile' does not exist.");
		
		$template = new Nette\Templating\FileTemplate($this->templateFile);
		
		$template->registerFilter(new Nette\Latte\Engine);
		$template->registerHelperLoader('Nette\Templating\DefaultHelpers::loader');
		
		$template->baseUrl = rtrim($this->context->httpRequest->getUrl()->getBaseUrl(), '/');
		$template->renderer = $this;
		$template->context = $this->context;
		$template->ownerInfo = vManager\Modules\System::getInstance()->getOwnerInfo();
		
		return $template;
	}
	
	/**
	 * Returns template
	 * 
	 * @return Nette\Templating\Template
	 */
	public function getTemplate() {
		return isset($this->template)
						? $this->template
						: $this->template = $this->createTemplate();
	}
	
}