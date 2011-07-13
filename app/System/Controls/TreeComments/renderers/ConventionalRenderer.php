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
namespace vManager\Modules\Wiki\Controls;
/**
 * 
 *
 * @author Jirka Vebr
 */
class ConventionalRenderer extends \Nette\Object implements IRenderer
{
	private $treeComments;
	
	public function render(TreeComments $control, $part = null) {
		$this->treeComments = $control;
		
		if ($part === null) {
			$template = $this->createTemplate('conventional');
			$template->tree = $this->renderTree();
			$template->form = $this->renderForm();
			$template->flash = $this->renderFlash();
			return $template;
		} else {
			return $this->{'render'.$part}();
		}
	}
	
	public function renderForm() {
		return $this->createTemplate('form');
	}
	
	public function renderFlash() {
		return $this->createTemplate('flash');
	}
	
	public function renderTree() {
		$template = $this->createTemplate('tree');
		$template->treeData = $this->getTreeComments()->getModel()->getTreeData();
		return $template;
	}
	
	private function createTemplate($file) {
		$template = $this->treeComments->createTemplate();
		$template->setFile(__DIR__.'/templates/'.$file.'.latte');
		return $template;
	}
	
	public function getTreeComments() {
		return $this->treeComments;
	}
}