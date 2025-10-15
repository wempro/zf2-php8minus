<?php
namespace AuthV2\Factory\Authentication;

use Zend\ServiceManager\ServiceLocatorInterface;
use AuthV2\Api\StatelessAuthService;

class StatelessAuthenticationServiceFactory extends AbstractAuthenticationServiceFactory {
    private $userTable;
    protected function getUserTable(){
            if(!isset($this->userTable)) $this->userTable=$this->getServiceLocator()->get('AuthV2\\User\\UserTable');
            return $this->userTable;
    }
    private function getClosureForIdentityArray(){
            return $this->getServiceLocator()->get('AuthV2\\ClosureForIdentityArray');
    }
	public function createService(ServiceLocatorInterface $serviceLocator) {
		$this->setServiceLocator($serviceLocator);
                StatelessAuthService::setStaticSystemPrefix($serviceLocator->get('AuthV2\\SysPrefix'));
                try {
			$authStorage=$serviceLocator->get('AuthStorage');
		} catch (\Exception $e) {
			die('@'.__LINE__.': '.__FILE__.' - '.$e->getMessage().'<pre>'.$e->getTraceAsString());
		}
                $usrTbl=$this->getUserTable();
                $vldtblAuthAdptr=$serviceLocator->get('AuthV2\\ValidatableAuthAdapter');
		$auth2rtrn=new StatelessAuthService($authStorage, $vldtblAuthAdptr, $usrTbl);
		$auth2rtrn->setClosureForIdentityArray($this->getClosureForIdentityArray());
		$token=$this->getTokenFromHeader();
		if(!is_null($token)){
			try{
				$auth2rtrn->setToken($token);
				#if($auth2rtrn->hasIdentity()) die('@'.__LINE__.': valid $token: '.$token);
			}catch(\Exception $e){
			    #die('@'.__LINE__.': '.__FILE__.' - '.$e->getMessage().'<pre>'.$e->getTraceAsString());
				if($auth2rtrn->hasIdentity()) $auth2rtrn->clearIdentity();
			}
		}
		if($this->isPreflightCorsRequested()) $auth2rtrn->setAsPreflightCorsRequest();
		elseif(is_null($token) && $auth2rtrn->hasIdentity()) $auth2rtrn->clearIdentity();
		return $auth2rtrn;
	}


}