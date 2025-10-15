<?php
namespace AuthV2\Form;

use Zend\Form\Form;
use Zend\InputFilter;

class AnonymousForm extends Form {
	protected $dbAdapter;
	public function __construct($name = null) {
		// we want to ignore the name passed
		parent::__construct($name);
		#$this->dbAdapter=\Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
		#$policy=new TfwPolicy($this->dbAdapter);
		#$policy->set('newuser', 'others');
		$this->setAttribute('method', 'post');

		$this->add(array(
				'name' => 'userFirstName',
				'attributes' => array(
						'type'  => 'text',
						'class' => 'form-control',
				),
				'options' => array(
						'label' => 'First Name',
				),
		));
		$this->add(array(
				'name' => 'userLastName',
				'attributes' => array(
						'type'  => 'text',
						'class' => 'form-control',
				),
				'options' => array(
						'label' => 'Last Name',
				),
		));
		$this->add(array(
				'name' => 'userEmail',
				'attributes' => array(
						'type'  => 'text',
						'class' => 'form-control',
				),
				'options' => array(
						'label' => 'E-mail Address',
				),
		));
		$this->add(array(
				'name' => 'submit',
				'attributes' => array(
						'type'  => 'submit',
						'value' => 'Submit',
						'id' => 'submitbutton',
				),
		));
		$this->setInputFilter($this->createInputFilter());
	}
	public function setRedirUrl($pRedirUrl){
		$this->add(array(
				'name' => 'redir2url',
				'attributes' => array(
						'type'  => 'hidden',
						'value' => $pRedirUrl,
					),
			));
	}
	public function createInputFilter() {
		$inputFilter = new InputFilter\InputFilter();
		//username
		$userFirstName = new InputFilter\Input('userFirstName');
		$userFirstName->setRequired(true);
		$inputFilter->add($userFirstName);
		$userLastName = new InputFilter\Input('userLastName');
		$userLastName->setRequired(true);
		$inputFilter->add($userLastName);
		//password
		$userEmail = new InputFilter\Input('userEmail');
		$userEmail->setRequired(true);
		$userEmail->getValidatorChain()->attach(new \Zend\Validator\EmailAddress());
		$inputFilter->add($userEmail);
		return $inputFilter;
	}

}

