<?php
namespace AuthV2\Factory\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use AuthV2\Controller\AuthenticationController;

class AuthControllerServiceFactory implements FactoryInterface {
	public function createService(ServiceLocatorInterface $serviceLocator) {
            

		#die('i am in factory : ');
                return new AuthenticationController($serviceLocator->getServiceLocator()->get('AuthenticationService'));
	}
}