<?php
namespace AuthV2\Factory\Authentication;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\ServiceManager\ServiceLocatorAwareInterface;


abstract class AbstractAuthenticationServiceFactory implements FactoryInterface, ServiceLocatorAwareInterface {
	abstract protected function getUserTable();
	private $sm;
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator){
		$this->sm=$serviceLocator;
		return $this;
	}
	public function getServiceLocator(){ return $this->sm; }
	private $request;
	/**
	 * @return Request
	 */
	private function getRequest(){
		if(!isset($this->request)) $this->request=$this->getServiceLocator()->get('Request');
		return $this->request;
	}
	protected function getTokenFromHeader(){
		$token2rtrn=null;
		$headers = $this->getRequest()->getHeaders();
		/*
		 * no token provided: Zend\Http\PhpEnvironment\Request<pre>Array(    [Content-Type] => application/x-www-form-urlencoded    [Content-Length] => 47    [X-Original-Url] => /login/stateless    [X-Requested-With] => XMLHttpRequest    [User-Agent] => Java/1.8.0_102    [Host] => cart.shopping.shahadat.web    [Accept] => application/json    [Connection] => keep-alive)
		 */
		if ($headers->has('Token')){
			$token=$headers->get('Token');
			#die('token found! '.$token->getFieldValue());
			if(false !== $token) $token2rtrn=$token->getFieldValue();
		} #else echo('no token provided: '.get_class($request).'<pre>'.print_r($headers->toArray(), true));
		if ($headers->has('X-Token')){
			$token=$headers->get('X-Token');
			#die('token found! '.$token->getFieldValue());
			if(false !== $token) $token2rtrn=$token->getFieldValue();
		} #else echo('no token provided: '.get_class($request).'<pre>'.print_r($headers->toArray(), true));
		return $token2rtrn;
	}
	protected function isPreflightCorsRequested(){ return ('options'==strtolower($this->getRequest()->getMethod())); }
}