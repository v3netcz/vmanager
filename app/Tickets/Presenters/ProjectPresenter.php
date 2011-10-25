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

namespace vManager\Modules\Tickets;

use vManager,
	 vBuilder,
	 Nette,
	 vBuilder\Orm\Repository,
	 Gridito,
	 vManager\Form;

/**
 * Presenter for viewing projects
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 27, 2011
 */
class ProjectPresenter extends vManager\Modules\System\SecuredPresenter {

	/** @var Project */
	protected $project;
	/** @var array of suggestions to Assign form field */
	protected $assignToSuggestions = array();

	// <editor-fold defaultstate="collapsed" desc="Project listing (default)">
	
	/**
	 * Project listing table grid component factory
	 * 
	 * @param string $name
	 * @return vManager\Grid
	 */
	protected function createComponentProjectListingGrid($name) {
		$grid = new vManager\Grid($this, $name);
		$grid->setTemplateFile(__DIR__ . '/../Templates/Gridito/projectGrid.latte');

		$uid = Nette\Environment::getUser()->getId();
		$table = Project::getMetadata()->getTableName();

		$ds = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Project')
				  ->as('d')
				  ->where('[revision] > 0');

		// Pokud to neni spravce projektu, zobrazuju jen projekty, ktere uzivatel zalozil
		// nebo kterym je prirazen
		if(!Nette\Environment::getUser()->getIdentity()->isInRole('Project admin'))
			$ds->and("([assignedTo] = %i OR [author] = %i OR ([revision] > 1 AND EXISTS (SELECT * FROM [$table] WHERE [author] = %i AND [revision] = -1 AND [projectId] = [d.projectId])))", $uid, $uid, $uid);

		// Filtery		
		if($this->getParam('assignedTo') > 0)
			$ds->and("[assignedTo] = %i", $this->getParam('assignedTo'));
		
		// Konec filteru
		
		if($grid->sortColumn === null)
			$ds->orderBy('[name] DESC, [projectId]');
		
		$grid->setModel(new Gridito\DibiFluentModel($ds, 'vManager\\Modules\\Tickets\\Project'));
		$grid->setItemsPerPage(20);

		$grid->setRowClass(function ($iterator, $project) {
					  $classes = array();

					  return empty($classes) ? null : implode(" ", $classes);
				  });

		// =======================================================================
		// ID Projektu
		$grid->addColumn("id", __('ID'), array(
			 "renderer" => function ($project) {
				 $link = Nette\Environment::getApplication()->getPresenter()->link('detail', $project->id);
				 echo Nette\Utils\Html::el("a")->href($link)->setText('#'.$project->id);
			 },
			 "sortable" => true,
		))->setCellClass('id');;

		// Nazev projektu
		$grid->addColumn("name", __('Project name'), array(
			 "renderer" => function ($project) {
				 $link = Nette\Environment::getApplication()->getPresenter()->link('detail', $project->id);				 
				 echo ' ';
				 echo Nette\Utils\Html::el("a")->href($link)->setText($project->name);
			 },
			 "sortable" => true,
		));

		// zodpovedna osoba
		//if(Nette\Environment::getUser()->getIdentity()->isInRole('Project admin')) {
			$grid->addColumn("assignedTo", __('Assigned to'), array(
				 "renderer" => function ($project) {
					 echo $project->assignedTo !== null ? ($project->assignedTo->exists() ? $project->assignedTo->username
											  : _x('User n. %d', array($project->assignedTo->id))) : __('nobody');
				 },
				 "sortable" => true
			))->setCellClass('assignedTo');
		//}

		// Datum posledni zmeny
		$grid->addColumn("timestamp", __("Last change"), array(
			 "renderer" => function ($project) {			 
				 echo Nette\Utils\Html::el("abbr")
          ->title($project->timestamp->format("d. m. Y"))
          ->setText(vManager\Application\Helpers::timeAgoInWords($project->timestamp));
			 },
			 "sortable" => true
		))->setCellClass("date lastChange");

		// Deadline
		$grid->addColumn("deadline", __('Deadline'), array(
			 "renderer" => function ($project) {
				 if($project->deadline == null) {
					 echo "-";
					 return;
				 }

				 echo Nette\Utils\Html::el("abbr")
          ->title($project->deadline->format("d. m. Y"))
          ->setText(vManager\Application\Helpers::timeAgoInWords($project->deadline));
			 },
			 "sortable" => true
		))->setCellClass("date deadline");

		// Stav projectu
		$grid->addColumn("state", __('State'), array(        
			 "renderer" => function ($project) {
         $count = $project->getTicketCount();
         $link = Nette\Environment::getApplication()->getPresenter()->link(':Tickets:Ticket:default', array('projectId' => $project->id));
				 if ($count>0) {
            echo Nette\Utils\Html::el("a")->href($link)->setText($count);
				 } else {          
				    echo Nette\Utils\Html::el("span")->setText(__("None"));
         }
			 },
			 "sortable" => true,
		))->setCellClass("state");

	}
	
	protected function createComponentProjectFilter() {
		$form = new Form;
						
		if(Nette\Environment::getUser()->getIdentity()->isInRole('Project admin')) {
			$form->addSelect('assignedTo', __('Assigned to'), array(-1 => __('Anyone')) + $this->getAllAvailableUsernames(true));
		}
		
		$projects = $this->getAllAvailableProjects();
		if(count($projects) > 1) 
			$form->addSelect('projectId', __('Project'), array(-1 => __('Any')) + $projects);	
		
		$form->setValues($this->getParam());
		$form->addSubmit('send', __('Filter'));
		
		$presenter = $this;
		$form->onSuccess[] = function () use ($presenter, $form) {
			$presenter->redirect("default", (array) $form->getValues());
		}; 
		
		return $form;
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="PRoject detail (detail)">
	
	/**
	 * Authorization of project render request before performing any action
	 * 
	 * @param int $id 
	 */
	public function actionDetail($id) {
		// TODO: Autorizace prav uzivatele pro zobrazeni/pridani apod. projektu
		// pripadne to povesit do entity na event handlery (u verzovanych nemuze byt Secured)
	}

	/**
	 * Render project detail1
	 * 
	 * @param int $id 
	 */
	public function renderDetail($id) {
		$texy = new \Texy();
		$texy->encoding = 'utf-8';
		$texy->allowedTags = \Texy::NONE;
		$texy->allowedStyles = \Texy::NONE;
		$texy->setOutputMode(\Texy::XHTML1_STRICT);

		$this->template->registerHelper('texy', callback($texy, 'process'));

		$this->template->historyWidget = new VersionableEntityView('vManager\\Modules\\Tickets\\Project', $id);
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Project form helpers">
	
	/**
	 * Function helper for setting up form fields of addtional project info
	 * 
	 * @param vManager\Form reference to form
	 */
	protected function setupProjectDetailForm(Form & $form) {
		$form->addText('name', __('Task title:'))->setAttribute('title', __('Short task description. Please be concrete.'))
				  ->addRule(Form::FILLED, __('Task title has to be filled.'));


		$form->addDatePicker('deadline', __('Deadline:'))->setAttribute('title', __('When has to be task done?'));

		$context = $this->context;
		
		$form->addText('assignTo', __('Assign to:'))
				  ->setAttribute('autocomplete-src', $this->link('suggestAssignTo'))
				  ->setAttribute('title', __('Who will resolve this issue?'))
				  ->addCondition(Form::FILLED)
				  ->addRule(function ($control) use($context) {
								 $users = $context->repository->findAll('vManager\Security\User')->where('[username] = %s', $control->value)->fetchSingle();
								 return ($users !== false);
							 }, __('Responsible person does not exist.'));							 
	}

	protected function saveProject(Project $project, $values) {
		$changed = false;

		if(isset($values['comment']) && !empty($values['comment'])) {
			$project->comment = $this->context->repository->create('vManager\Modules\Tickets\Comment');
			$project->comment->text = $values['comment'];
			$changed = true;
		} else {
			$project->comment = null;
		}

		if(isset($values['assignTo'])) {
			if(!empty($values['assignTo'])) {
				$user = $this->context->repository->findAll('vManager\Security\User')->where('[username] = %s', $values['assignTo'])->fetch();
				$newAssignedTo = $user !== false ? $user : null;
			} else
				$newAssignedTo = null;

			if(!$changed)
				$changed = $newAssignedTo === null ? ($project->assignedTo !== null) : ($project->assignedTo === null || $newAssignedTo->id != $project->assignedTo->id);

			$project->assignedTo = $newAssignedTo;
		}
		

		foreach(array('name', 'description', 'deadline') as $curr) {
			if(isset($values[$curr]) && $project->{$curr} != $values[$curr]) {
				$project->{$curr} = $values[$curr];
				$changed = true;
			}
		}

		if($changed) {
			$project->author = Nette\Environment::getUser()->getIdentity();
			$project->timestamp = null; // Vyuzivam CURRENT_TIMESTAMP defaultu
			$project->save();
		}

		return $changed;
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Form suggestion helpers">
	
	/**
	 * Set matching items for current query given in typedText parameter (GET term)
	 * 
	 * @param string $typedText The text the user typed in the input
	 *
	 * @return void
	 */
	public function actionSuggestAssignTo() {
		$typedText = $this->getParam('term', '');

		$this->assignToSuggestions = $this->getAllAvailableUsernames(false, $typedText, 10);
	}
	
	/**
	 * Send the matching items for assign to field completer (JSON)
	 * 
	 * @return void
	 */
	public function renderSuggestAssignTo() {
		$this->sendResponse(new Nette\Application\Responses\JsonResponse($this->assignToSuggestions));
	}
	
	private function getAllAvailableUsernames($realNames = false, $filterTerm = '', $limit = -1) {
		$users = $this->context->repository->findAll('vManager\\Security\\User');
		
		if($filterTerm != '')
			$users->where('[username] LIKE %s', $filterTerm.'%');
		
		if($limit > 0)
			$users->limit($limit);				  

		$data = array();
		foreach($users as $curr) {
			$data[$curr->getId()] = $realNames ? $curr->getDisplayName() : $curr->getUsername();
		}
		
		return $data;
	}
		
	private function getAllAvailableProjects($filterTerm = '', $limit = -1) {
		$ds = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Project')
				  ->where('[revision] > 0');
		
		if($filterTerm != '')
			$ds->and('[name] LIKE %s', $filterTerm.'%');
		
		if($limit > 0)
			$ds->limit($limit);				  

		$data = array();
		foreach($ds as $curr) {
			$data[$curr->getId()] = $curr->getName();
		}
		
		return $data;
	}
	
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="Create project form (create)">
	
	/**
	 * Create form
	 * @return vManager\Form
	 */
	protected function createComponentCreateForm() {
		$form = new Form;

		$this->setupProjectDetailForm($form);

		$form->addTextArea('description')
				  ->getControlPrototype()->class('texyla');

		$form->addSubmit('send', __('Save'));

		$form->onSuccess[] = callback($this, 'createFormSubmitted');

		return $form;
	}
	
	public function createFormSubmitted(Form $form) {
		$values = $form->getValues();
		$project = $this->context->repository->create('vManager\Modules\Tickets\Project');

		$this->saveProject($project, $values);

		$this->flashMessage(__('New project has been created.'));
		$this->redirect('detail', $project->id);
	}
	
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="Update form (detail)">
	
	/**
	 * Update form
	 * @return vManager\Form
	 */
	protected function createComponentUpdateForm() {
		$form = new Form;
		$form->setRenderer(new Nette\Forms\Rendering\DefaultFormRenderer());

		$project = $this->getProject();

		$form->addTextArea('comment')->setAttribute('class', 'texyla');
		$form->addTextArea('description')->setValue($project->description)->setAttribute('class', 'texyla');


		$this->setupProjectDetailForm($form);
		$form['name']->setValue($project->name);
		$form['deadline']->setValue($project->deadline);
		if($project->assignedTo !== null && $project->assignedTo->exists())
			$form['assignTo']->setValue($project->assignedTo->username);

		$form->addSubmit('send', __('Save'));

		$form->onSuccess[] = callback($this, 'updateFormSubmitted');

		return $form;
	}

	public function updateFormSubmitted(Form $form) {
		$values = $form->getValues();
		$project = $this->getProject();

		if($this->saveProject($project, $values))
			$this->flashMessage(__('Change has been saved.'));
		else
			$this->flashMessage(__('Nothing to change.'));


		$this->redirect('this');
	}
	
	// </editor-fold>

	protected function getProject() {
		if($this->getParam('id') === null)
			return null;
		if($this->project !== null)
			return $this->project;

		$this->project = $this->context->repository->findAll('vManager\\Modules\\Tickets\\Project')
							 ->where('[revision] > 0 AND [projectId] = %i', $this->getParam('id'))->fetch();

		return $this->project;
	}

}
