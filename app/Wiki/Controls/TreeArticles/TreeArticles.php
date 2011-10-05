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

use vBuilder\Orm\Repository, Nette;
/**
 * 
 *
 * @author Jirka Vebr
 */
class TreeArticles extends \Nette\Application\UI\Control {
	
	/** @var string wikiId */
	private $wikiId;




	public function render($depth = null) {
		
		// TODO: Escapovat $slashes a $depth!!!
		$slashes = \substr_count($this->getWikiId(), '/');
		$tree = Nette\Environment::getContext()->repository->findAll('vManager\Modules\Wiki\Article')
								->where('[url] LIKE %s',$this->getWikiId().'%', 'AND [revision] > 0', 
										'%if', $depth, ' AND [url] RLIKE %s', '^(/[a-z\-]+){'.$slashes.','.($slashes+$depth-1).'}$', '%end')
								->orderBy('[url]')
								->fetchAll();
		
		
		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/tree.latte');
		$template->tree = $tree;
		echo $template;
	}
	
	
	
	public function setWikiId($value) {
		$this->wikiId = (string) $value;
		return $this;
	}
	
	public function getWikiId() {
		return $this->wikiId;
	}
}