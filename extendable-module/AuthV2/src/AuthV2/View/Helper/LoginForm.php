<?php

namespace AuthV2\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Form\View\Helper\Form as ViewHelperForm;

class LoginForm extends AbstractHelper implements ServiceLocatorAwareInterface {
	/**
	 * Set the service locator.
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 * @return CustomHelper
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
		$this->serviceLocator = $serviceLocator;
		return $this;
	}
	/**
	 * Get the service locator.
	 *
	 * @return \Zend\ServiceManager\ServiceLocatorInterface
	 */
	public function getServiceLocator() {
		return $this->serviceLocator;
	}
	public function getCss(){
		return '.accountlogingeneral input{max-width:300px;}
.account-login label{display: block;
    width: 100%;}
.account-login label span {
    font-size: 18px;
    font-family: Open Sans,Helvetica;
}
.account-login #submitbutton {
    background-color: #0195ff;
    border: 1px solid #fff;
    border-radius: 5px;
    color: #fff;
    font-size: 16px;
    padding: 8px;
    width: 150px;
}';
	}
	function __invoke($pRedirUrl=NULL, $pReturnAnonymousForm=FALSE, $pForm=null){ return $this->get($pRedirUrl, $pReturnAnonymousForm, $pForm); }
	public function get($pRedirUrl=NULL, $pReturnAnonymousForm=FALSE, $pForm=null){
	    #return '<h3>login form test</h3>';
		$renderer = $this->getView();
		#$this->getServiceLocator();
		#$routeMatch = $this->serviceLocator->getServiceLocator()->get('Application')->getMvcEvent()->getRouteMatch();
		#echo $routeMatch->getMatchedRouteName();
		if(true==$pReturnAnonymousForm){
			#if(empty($pRedirUrl)) throw new \Exception('you must set redirect url to get anonymous user form...');
			$form = (!isset($pForm)?new \AuthV2\Form\AnonymousForm():$pForm);
			$form->setAttribute('action', $renderer->url('login', array('action'=>'anonymous-login')));
		}else{
			// use it at will ...
			$form = (!isset($pForm)?new \AuthV2\Form\LoginForm():$pForm);
			$form->setAttribute('action', $renderer->url('login'));
		}
		if(!empty($pRedirUrl)) $form->setRedirUrl($pRedirUrl);
		$form->prepare();
		$frmHelper = new ViewHelperForm();
		$rtrn = $frmHelper->openTag($form);
		$rtrn .= $renderer->formCollection($form);
		$rtrn .= $frmHelper->closeTag();
		#$rtrn .= '<h4>$renderer: '.get_class($renderer).'</h4>';
		#$rtrn .= '<h3>$routeMatch->getMatchedRouteName(): '.$routeMatch->getMatchedRouteName().'</h3>';
		#$rtrn .= '<pre>'.print_r($routeMatch, true).'</pre>';
		return $rtrn;
	}
	function __toString(){ return $this->get(); }
}
