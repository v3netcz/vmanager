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

namespace vManager\Modules\Accounting;

use vManager, vBuilder, Nette, vManager\Form, Gridito;

/**
 * Presenter for listing and managing subject
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 31, 2012
 */
class SubjectPresenter extends vManager\Modules\System\SecuredPresenter {
	
	const ENTITY_SUBJECT = 'vManager\Modules\Accounting\Subject';
	const ENTITY_ADDRESS = 'vManager\Modules\Accounting\Address';
	const ENTITY_CONTACT = 'vManager\Modules\Accounting\ContactPerson';
	
	/** @var vManager\Modules\Accounting\Subject */
	protected $employee;
	
	/** @persistent */
	public $id;
	
	private $loadedSubjectInfo;
	private $subjectSuggestions;
	
	protected function createComponentSubjectGrid($name) {
		$grid = new vManager\Grid($this, $name);

		$model = new vManager\Grid\OrmModel($this->context->repository->findAll(self::ENTITY_SUBJECT));

		$grid->setModel($model);
		$grid->setItemsPerPage(10);

		// columns
		//$grid->addColumn("id", "ID")->setSortable(true);
		$grid->addColumn("name", __("Subject name"))->setSortable(true);
		$grid->addColumn("in", __("Subject IN"))->setSortable(true);
		/*$grid->addColumn("email", __("E-mail"), array(
			 "renderer" => function ($row) {
				 echo Nette\Utils\Html::el("a")->href("mailto:$row->email")->setText($row->email);
			 },
			 "sortable" => true,
		)); */
	}
	
	public function createComponentNewSubjectForm($name) {
		$context = $this->context;
	
		$form = new Form;
		$form->addText('in', __('Subject IN:'))->setRequired()
			->addRule(function ($control) use ($context) {
				return $context->repository->findAll(SubjectPresenter::ENTITY_SUBJECT)
					->where('[in] = %s', $control->value)->fetch() == false;
					
			}, __('This subject is already in database'));
		
		$form->addText('tin', __('Subject TIN:'));
		$form->addText('name', __('Subject name:'))->setRequired();
		
		$form->addText('street', __('Street / house number:'))->setRequired();	
		$form->addText('houseNumber')->setRequired(__('House number is required'));
		$form->addText('city', __('City:'))->setRequired();
		$form->addText('postal', __('Postal code:'))->setRequired();
		$form->addText('country', __('Country:'))->setRequired();
		
		$form->addText('contactName', __('Contact person name:'));
		$form->addText('contactEmail', __('Contact e-mail:'));
		$form->addText('contactPhone', __('Contact phone number:'));
				
		$form->addSubmit('s', __('Add subject'));

		$form->onSuccess[] = callback($this, 'processNewSubjectForm');

		return $form;
	}
	
	public function processNewSubjectForm($form) {
		$values = $form->getValues();
		
		$subject = $this->context->repository->create(self::ENTITY_SUBJECT);
		$subject->in = $values->in;
		if(!empty($subject->tin)) $subject->tin = $values->tin;
		$subject->name = $values->name;
		
		$address = $this->context->repository->create(self::ENTITY_ADDRESS);
		$address->street = $values->street;
		$address->houseNumber = $values->houseNumber;
		$address->city = $values->city;
		$address->zip = $values->postal;
		$address->country = $values->country;
		$subject->invoiceAddress = $address;
		// $subject->postAddress = $address;
		
		if(!empty($values->contactName) || !empty($values->contactEmail) || !empty($values->contactPhone)) {
			 $contact = $this->context->repository->create(self::ENTITY_CONTACT);
			 $contact->name = $values->contactName;
			 $contact->email = $values->contactEmail;
			 $contact->phone = $values->contactPhone;
			 $subject->contacts->add($contact);
		}
		
		$subject->save();
		
		$this->flashMessage(_x('Subject %s has been successfully created.', array($values->name)));	
		$this->redirect('this');
	}	
	
	public function actionLoadSubjectInfo($ic) {
		$this->loadedSubjectInfo = false;
		
		if(!$this->isValidIc($ic))
			throw new Nette\InvalidArgumentException('Given IC is not valid');
			
		$content = file_get_contents('http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico=' . intval($ic));
		if($content === false)
			throw new Nette\IOException('Error when contacting ARES server');
			
		$xml = simplexml_load_string($content);
		if($xml === false)
			throw new Nette\InvalidStateException('ARES response is not valid XML data');
			
		$ns = $xml->getDocNamespaces();
		$data = $xml->children($ns['are']);
		$el = $data->children($ns['D'])->VBAS;
		if(strval($el->ICO) == $ic) {
			$this->loadedSubjectInfo = array(
				'in' => strval($el->ICO),
				'tin' => strval($el->DIC),
				'name' => strval($el->OF),
				'address' => array(
					'street' => strval($el->AA->NU),
					'houseNumber' => strval($el->AA->CD) . ( strval($el->AA->CO) != "" ? '/' . strval($el->AA->CO) : ""),
					'city' => strval($el->AA->N) . (strval($el->AA->NCO) != "" ? ' - ' . strval($el->AA->NCO) : ""),
					'postal' => strval($el->AA->PSC),
					'country' => strval($el->AA->NS)
				)
			);
		}
	}
	
	public function renderLoadSubjectInfo() {
		$this->sendResponse(new Nette\Application\Responses\JsonResponse($this->loadedSubjectInfo));
	}
	
	/**
	 * Set matching items for current query given in typedText parameter (GET term)
	 * 
	 * @param string $typedText The text the user typed in the input
	 *
	 * @return void
	 */
	public function actionSuggestSubject() {
		$typedText = $this->getParam('term', '');

		$subjects = $this->context->connection->query('SELECT [name] FROM [accounting_subjects] WHERE [name] LIKE %like~ LIMIT 10', $typedText)
			->fetchAll();
			

		$this->subjectSuggestions = array();
		foreach($subjects as $curr)
			$this->subjectSuggestions[] = $curr['name'];
	}
	
	/**
	 * Send the matching items for assign to field completer (JSON)
	 * 
	 * @return void
	 */
	public function renderSuggestSubject() {
		$this->sendResponse(new Nette\Application\Responses\JsonResponse($this->subjectSuggestions));
	}
	
	protected function isValidIc($ic) {
		if (!ctype_digit($ic) || $ic > 99999999) return false;

		// Pro vypocet nutno doplnit IC uvodnimi nulami
		$ic = sprintf("%08s", $ic);
		$a = 0;
		for ($i = 0; $i < 7; $i++) $a += $ic[$i] * (8 - $i);
		$a = $a % 11;
		$c = 11 - $a;
		if ($a == 1) $c = 0;
		if ($a == 0) $c = 1;
		if ($a == 10) $c = 1;
		if ($ic[7] != $c) return false; // validaci neproslo
		
		return true; // validaci proslo
	}
	
}
