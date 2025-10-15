<?php
namespace AuthV2\Factory\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use AuthV2\Controller\StatelessAuthenticationController;

class StatelessAuthControllerServiceFactory implements FactoryInterface {
	public function createService(ServiceLocatorInterface $serviceLocator) {
		#die('i am in factory : ');
		return new StatelessAuthenticationController($serviceLocator->getServiceLocator()->get('StatelessAuthenticationService'));
	}
}