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

namespace vManager\Modules\Wiki;

use vManager, vBuilder, Nette, vBuilder\Orm\Repository, vManager\Form;


class DefaultPresenter extends BasePresenter {
	
	public function actionDefault($wikiId = null) {
		$config = vManager\Modules\Wiki::getInstance()->getConfig();
		
		if (isset($wikiId)) {
			$lower = \strtolower($wikiId);
			if ($lower !== $wikiId) {
				$this->redirect('default', $lower);
			}
			$id = $wikiId;
		} else {
			$id = '/';
		}
		if ($article = $this->getArticle($id)) {
			// creating array ('/', '/grandparent', '/grandparent/parent', '/grandparent/parent/child')
			$pieces = preg_split('~/~',substr($article->url,1));
			$count = count($pieces);
			$urls = array ('/');
			for ($i=0; $i<$count; $i++) {
				$temp = '';
				for ($m=0; $m<=$i; $m++) {
					$temp .= '/'.$pieces[$m];
				}
				$urls[] = $temp;
			}
			$this->template->navigation = $this->context->repository->findAll('vManager\Modules\Wiki\Article')
								->where('[url] IN %in', $urls, "\n AND [revision] > 0")
								->orderBy('[url]')
								->fetchAll();
			
			$this->template->article = $article;
			$this->template->commentsEnabled = !isset($config['comments']['enabled']) || (bool) $config['comments']['enabled'];  
		} else {
			$this->forward('nonExistentArticle', $wikiId);
		}
	}
	
	public function actionCreateArticle($wikiId) {
		if (!$this['articleForm']->isSubmitted()) {
			$this['articleForm']->setDefaults(array (
				'title' => substr($wikiId,1), // without the "/"
				'url' => $wikiId
			));
		}
	}
	
	public function actionEditArticle($wikiId) {
		if (!$this['articleForm']->isSubmitted()) {
			if ($article = $this->getArticle()) {
				$this['articleForm']->setDefaults(array (
					'oldUrl' => $article->url,
					'url' => $article->url,
					'text' => $article->text,
					'title' => $article->title,
					'active' => (bool) $article->active
				));
			} else {
				$this->redirect('default', $wikiId);
			}
		}
	}
	
	public function actionTree($wikiId) {
		
	}
	
	public function actionNonExistentArticle($wikiId) {
		
	}
	
	
	public function createComponentArticleForm() {
		$form = new Form;
		
		$presenter = $this;
		
		$form->onSuccess[] = function ($form) use ($presenter) {
			$values = $form->values;
			
			try {
				if ($values['url']) {
					// TODO
					// Can't use addRule(Form::PATTERN) as this field may be empty
					// Highway to hell...?
				}
				
				$date = new \DateTime();
				if ($values['oldUrl']) {
					// editting
					$article = $presenter->getArticle($values['oldUrl']);	
				} else {
					// creating
					$article = $presenter->context->repository->create('vManager\Modules\Wiki\Article');
					$article->added = $date->getTimestamp();
				}

				$url = $values['oldUrl'];
				unset($values['oldUrl']);
				foreach ($values as $key => $value) {				
					$article->{$key} = $value;
				}
				$article->author = $presenter->getUser()->getIdentity()->id;
				$article->lastModified = $date->getTimestamp();

				if ($presenter->getParam('wikiId') === '/') {
					$article->url = '/';
				} else {
					$article->url = empty($values['url']) ? \Nette\Utils\Strings::webalize('/'.$values['title'], 'abcdefghijklmnopqrstuvwxyz/', true) : \strtolower($values['url']);
				}

				$article->save();
				$presenter->flashMessage(__('Success!'));
				$presenter->redirect(':Wiki:Default:default', $article->url);
			} catch (\vBuilder\FormErrorExeption $e) {
				$form->addError($e->getMessage());
			}
		};
		
		$form->addText('title', __('Article title'))
			->addRule(Form::FILLED, __('You have to fill in the article title!'));
		$form->addTextarea('text', __('Content'))
			->addRule(Form::FILLED, __('You have to write the article!'))
			->getControlPrototype()->class('texyla');
		$form->addCheckbox('active', __('Active'))
			->setDefaultValue(true);
		$form->addText('url', __("Article ID"))
				  ->setAttribute('title', __('Fill in the text that will appear in the address panel of the article.\nIf you leave this empty, it will be genereated automatically.'));
		$form->addHidden('oldUrl');
		$form->addSubmit('send',__('Save'));
		
		return $form;
	}
	
	public function createComponentTreeComments() {
		$c = new Controls\TreeComments();
		
		// settings
		$c->model->setTable('wiki_discussion')
			->setAssociatedId($this->getArticle($this->getParam('wikiId'))->id);
		
		//$c->invalidateControl();
		return $c;
	}
	
	public function createComponentTreeArticles() {
		$tree = new Controls\TreeArticles;
		$tree->setWikiId($this->getParam('wikiId'));
		return $tree;
	}
	
	public function getArticle($url = null) {
		$url = $url === null ? $this->getParam('wikiId') : $url;
		return $this->context->repository->findAll('vManager\Modules\Wiki\Article')
			->where('[revision] > 0 AND [url] = %s', $url)->fetch();
	}
}