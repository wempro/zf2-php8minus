<?php
namespace AuthV2\Form;

use Zend\Form\Form;
use Zend\InputFilter;

class LoginForm extends Form {
	protected $dbAdapter;
	public function __construct($name = null) {
		// we want to ignore the name passed
		parent::__construct($name);
		#$this->dbAdapter=\Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
		#$policy=new TfwPolicy($this->dbAdapter);
		#$policy->set('newuser', 'others');
		$this->setAttribute('method', 'post');
		
		$this->add(array(
				'name' => 'loginId',
				'attributes' => array(
						'type'  => 'text',
						'class' => 'form-control',
				),
				'options' => array(
						'label' => 'E-mail Address',
				),
		));
		$this->add(array(
				'name' => 'loginPass',
				'attributes' => array(
						'type'  => 'password',
						'class' => 'form-control',
				),
				'options' => array(
						'label' => 'Password',
				),
		));
		$this->add(array(
				'name' => 'submit',
				'attributes' => array(
						'type'  => 'submit',
						'value' => 'Login',
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
		$username = new InputFilter\Input('loginId');
		$username->setRequired(true);
		$inputFilter->add($username);
		//password
		$password = new InputFilter\Input('loginPass');
		$password->setRequired(true);
		$inputFilter->add($password);
		return $inputFilter;
	}

}

