<?php

namespace AuthV2\Controller;

#use Lib3rdParty\Zf2\Mvc\Controller\AbstractActionController;
use Lib3rdParty\Zend\Mvc\Controller\AbstractActionController;
#use Zend\Mvc\Controller\AbstractActionController;

class SuccessController extends AbstractActionController {
	public function indexAction() {
		return $this->getAcceptableViewModel(array('auth'=>$this->getServiceLocator()->get('AuthenticationService')));
	}
    
}

