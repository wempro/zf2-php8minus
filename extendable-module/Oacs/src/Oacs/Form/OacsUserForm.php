<?php
namespace Oacs\Form;


use Lib3rdParty\Helper\Form\TFWForm;
use Zend\Form\Element;
use Zend\Form\FormInterface;
use Oacs\Model\OacsUser;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
class OacsUserForm extends TFWForm{
	private $loggedInManager;
	private $companyTextId;
	private $adapter;
	function __construct($name=NULL, $option=array(), $pRequestOrActionUrl=NULL, OacsUser $pLoggedInManager=null, OacsUser $pUserToUpdate=null) {
		if(!is_null($pLoggedInManager)){
			if(!$pLoggedInManager->isManager()) throw new \Exception('not a manager logged in!');
			$this->loggedInManager=$pLoggedInManager;
			$this->adapter=$this->loggedInManager->getDbAdapter();
			$this->companyTextId=$this->loggedInManager->companyTextId;
		}
		if(!is_null($pUserToUpdate)){
			if(isset($this->loggedInManager) && $this->loggedInManager->companyTextId!==$pUserToUpdate->companyTextId) throw new \Exception('invalid user to update! unable to draw form');
			$this->userToUpdate=$pUserToUpdate;
			if(!isset($this->adapter)) $this->adapter=$this->userToUpdate->getDbAdapter();
			if(!isset($this->companyTextId)) $this->companyTextId=$this->userToUpdate->companyTextId;
		}
		$this->selfUpdate=(!isset($this->loggedInManager) && isset($this->userToUpdate));
		if(isset($this->loggedInManager) && isset($this->userToUpdate)) $this->selfUpdate=($this->loggedInManager->textId===$this->userToUpdate->textId);
		if(!isset($this->loggedInManager) && !isset($this->userToUpdate)) throw new \Exception('either manager or user should deal with form...');
		parent::__construct($name, $option, $pRequestOrActionUrl);
	}
	/**
	 * @var OacsUser
	 */
	private $userToUpdate;
	private $selfUpdate;
	/*public function setUserToUpdate(OacsUser $pUser, $pSelfUpdate=null){
		$this->userToUpdate=$pUser;
		if(!is_null($pSelfUpdate)) $this->selfUpdate=$pSelfUpdate;
		return $this;
	}*/
	public function isUpdateAssertive(){ return isset($this->userToUpdate); }
	public function isSelfUpdateAssertive(){
		if(!isset($this->selfUpdate)) return false;
		return $this->selfUpdate;
	}
	protected function initControls(){
		if(!isset($this->userToUpdate)){
			$this->add(new Element\Hidden('id'));
			$textId=new Element\Text('textId');
			$textId->setLabel('Login ID');
			$this->add($textId);
		}
		$email=new Element\Text('email');
		$email->setLabel('Email');
		$this->add($email);
		if($this->isSelfUpdateAssertive()){
			$pass=new Element\Password('pass');
			$pass->setLabel('Current Password');
			$this->add($pass);
		}
		$pass=new Element\Password('pass1');
		$pass->setLabel('New Password');
		$this->add($pass);
		$pass=new Element\Password('pass2');
		$pass->setLabel('Confirm Password');
		$this->add($pass);
		$fName=new Element\Text('fName');
		$fName->setLabel('First Name');
		$this->add($fName);
		$mName=new Element\Text('mName');
		$mName->setLabel('Middle Name');
		$this->add($mName);
		$lName=new Element\Text('lName');
		$lName->setLabel('Last Name');
		$this->add($lName);
		$desg=new Element\Text('desg');
		$desg->setLabel('Designation');
		$this->add($desg);
		if(!$this->isSelfUpdateAssertive()){
			$status=new Element\Radio('oacsUserStatus');
			$status->setLabel('User Status');
			$status->setValueOptions(OacsUser::getAvailableStatusToModify());
			$this->add($status);
			$type=new Element\Radio('oacsUserType');
			$type->setLabel('User Type');
			$type->setValueOptions(OacsUser::getAvailableTypeToModify());
			$this->add($type);
		}
		#$this->add(new Element\Hidden('companyTextId'));
		$submit=new Element\Submit('submit');
		$submit->setValue('Add New Admin');
		if($this->isSelfUpdateAssertive()) $submit->setValue('Update');
		elseif(isset($this->userToUpdate)) $submit->setValue('Update Admin');
		$this->add($submit);
		return $this;
	}
/*
 * use Zend\Validator\Identical;
use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;
 
 */
 	protected function grabInputFilter(){
		$inputFilter = new InputFilter();
		$factory     = new InputFactory();
		if(!isset($this->userToUpdate)) $inputFilter->add($factory->createInput(array(
	    				'name' => 'textId',
	    				'filters' => array(
	    						array('name' => '\\Zend\\Filter\\StringTrim'),
	    						array('name' => '\\Zend\\Filter\\StripTags'),
	    				),
	    				'validators' => array(
	    						array(
	    								'name' => '\\Zend\\Validator\\NotEmpty',
	    								'options' => array(
	    										'messages' => array(
	    												\Zend\Validator\NotEmpty::IS_EMPTY => 'Please provide valid ID'
	    										)
	    								)
	    						),
	    						array(
				                    'name' => '\\Lib3rdParty\\Helper\\Misc\\UserNameValidator',
	    								'options' => array(
	    										'min'      => 6, // need to apply policy. continue for out of project timeline
	    										'max'      => 30,
	    										'messages' => array(
	    												#\Registration\Validator\TFWUserName::NotEmpty => 'Username cannot be empty.',
														#\Lib3rdParty\Helper\Misc\UserNameValidator::LEAD_MUST_CHAR => 'Must start with alphabet.',
														#\Registration\Validator\TFWUserName::NO_INNER_SPACE => 'No space allowed for user name.',
														#\Registration\Validator\TFWUserName::TOO_SHORT => 'Username is to short, use a minimum of 4 characters.',
														#\Registration\Validator\TFWUserName::TOO_LONG => 'Username is to long, use a maximum of 50 characters.',
	    										),
	    								),
				                ),
	    						array(
	    								'name'    => '\\Zend\\Validator\\Db\\NoRecordExists',
	    								'options' => array(
	    										'table'     => 'OacsUser',
	    										'field'     => 'textId',
	    										'adapter'   => $this->adapter,
	    										'messages' => array(
	    												'recordFound' => 'A user with same ID address exists',
	    										),
	    								),
	    						),
	    				)
	    		)));
		$fltr_eml_options=array(
		                        				'table'     => 'OacsUser',
		                        				'field'     => 'email',
		                        				'adapter'   => $this->adapter,
		                        				'messages' => array(
		                        						'recordFound' => 'A user with same email address exists',
		                        				),
		                        		);
		if($this->isUpdateAssertive()) $fltr_eml_options['exclude']='`email`<>\''.$this->userToUpdate->email.'\'';
		$fltr_email=array(
	    				'name' => 'email',
	    				'filters' => array(
	    						array('name' => '\\Zend\\Filter\\StringTrim'),
	    						array('name' => '\\Zend\\Filter\\StripTags'),
	    				),
	    				'validators' => array(
	    						array(
	    								'name' => '\\Zend\\Validator\\NotEmpty',
	    								'options' => array(
	    										'messages' => array(
	    												\Zend\Validator\NotEmpty::IS_EMPTY => 'Please provide valid email address'
	    										)
	    								)
	    						),
	    						#/*
	    						array(
				                    'name' => '\\Zend\\Validator\\Callback',
				                    'options' => array(
				                        'messages' => array(
				                            \Zend\Validator\Callback::INVALID_VALUE => 'Please provide valid email address. i.e. email.id@example.com',
				                        ),
				                        'callback' => function($value, $context = array()) {
				                        	$rtrn=false;
				                        	if(preg_match('/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/', $value)) $rtrn=true;
				                            return $rtrn;
				                        },
				                    ),
				                ),
				                #*/
		                        array(
		                        		'name'    => '\\Zend\\Validator\\Db\\NoRecordExists',
		                        		'options' => $fltr_eml_options,
		                        ),
	    				)
	    		);
		$inputFilter->add($factory->createInput($fltr_email));
		$inputFilter->add($this->getInputFilterForText('fName', true, 150));
		if($this->isSelfUpdateAssertive()){
			$inputFilter->add($factory->createInput(array(
    					'name'     => 'pass',
    					'required' => true,
						'filters'  => array(
								array('name' => '\\Zend\\Filter\\StringTrim'),
						),
    					'validators' => array(
    							array(
    									'name' => '\\Zend\\Validator\\NotEmpty',
    									'options' => array(
    											'messages' => array(
    													\Zend\Validator\NotEmpty::IS_EMPTY => 'Please provide current password'
    											)
    									)
    							),
    					),
    			)));
		}
		$fltr_pass1=array(
				'name'     => 'pass1',
				'required' => !$this->isUpdateAssertive(),
				'filters'  => array(
						array('name' => '\\Zend\\Filter\\StringTrim'),
				),
				'validators' => array(
						array(
								'name' => '\\Zend\\Validator\\NotEmpty',
								'options' => array(
										'messages' => array(
												\Zend\Validator\NotEmpty::IS_EMPTY => 'Please provide new password'
										)
								)
						),
						array(
								'name'    => '\\Zend\\Validator\\StringLength',
								'options' => array(
										'encoding' => 'UTF-8',
										'min'      => 8,
										'max'      => 20,
										'messages' => array(
												\Zend\Validator\StringLength::TOO_SHORT => 'Must contain %min% characters',
												\Zend\Validator\StringLength::TOO_LONG => 'Not more than %max% characters'
										)
								),
						),
						array(
								'name' => '\\Zend\\Validator\\Callback',
								'options' => array(
										'messages' => array(
												\Zend\Validator\Callback::INVALID_VALUE => 'Must contain at least one uppercase letter, one lowercase letter and one number',
										),
										'callback' => function($value, $context = array()) {
											$atoz=$a2z=$zero2nine=$rtrn=false;
											if(preg_match('#[0-9]#', $value)) $zero2nine=true;
											if(preg_match('#[a-z]#', $value)) $a2z=true;
											if(preg_match('#[A-Z]#', $value)) $atoz=true;
											if($zero2nine==$a2z && $atoz==$zero2nine && true==$zero2nine) $rtrn=true;
											return $rtrn;
										},
								),
						),
						array(
								'name' => '\\Zend\\Validator\\Callback',
								'options' => array(
										'messages' => array(
												\Zend\Validator\Callback::INVALID_VALUE => 'No space allowed in password',
										),
										'callback' => function($value, $context = array()) {
											$rtrn=true;
											if(preg_match('#\s+#', $value)) $rtrn=false;
											return $rtrn;
										},
								),
						),
						array(
								'name' => '\\Zend\\Validator\\Callback',
								'options' => array(
										'messages' => array(
												\Zend\Validator\Callback::INVALID_VALUE => 'Must be different from your login id',
										),
										'callback' => function($value, $context = array()) {
											if(!array_key_exists('loginId', $context)) return true;
											$rtrn=(stripos($context['loginId'], $value)===false);
											if(true==$rtrn) $rtrn=(stripos($value, $context['loginId'])===false);
											return $rtrn;
										},
								),
						),
				),
		);
		$inputFilter->add($factory->createInput($fltr_pass1));
		$fltr_pass2=array(
				'name'     => 'pass2',
				'required' => !$this->isUpdateAssertive(),
				/*'filters'  => array(
				 array('name' => 'StringTrim'),
				),*/
				'validators' => array(
						array(
								'name' => '\\Zend\\Validator\\NotEmpty',
								'options' => array(
										'messages' => array(
												\Zend\Validator\NotEmpty::IS_EMPTY => 'Please confirm password'
										)
								)
						),
						array(
								'name'    => '\\Zend\\Validator\\Identical',
								'options' => array(
										'token' => 'pass1', // name of first password field
										'strict'=>true,
										'messages'=>array(
												\Zend\Validator\Identical::NOT_SAME=>'Password doesn\'t match with new password',
										),
								),
						),
				),
		);
		$inputFilter->add($factory->createInput($fltr_pass2));
		return $inputFilter;
	}
	/*public function fixFormElementsBeforeGettingData($pDataArray=array()){
		if(empty($this->companyTextId)) throw new \Exception('please set company text id before bind or set data!');
		$this->get('companyTextId')->setValue($this->companyTextId);
		return $this;
	}
	public function getData($flag=FormInterface::VALUES_NORMALIZED) {
		if(empty($this->companyTextId)) throw new \Exception('please set company text id before getting data!');
		$rtrn=parent::getData($flag);
		$rtrn['companyTextId']=$this->companyTextId;
		return $rtrn;
	}*/
}




