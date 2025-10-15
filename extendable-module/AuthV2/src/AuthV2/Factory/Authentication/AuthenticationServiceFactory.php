<?php
namespace AuthV2\Factory\Authentication;

#use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
#use Zend\Authentication\Adapter\DbTable\CredentialTreatmentAdapter as DbTableAuthAdapter;
use AuthV2\Api\AuthenticationService;
use AuthV2\Api\StatelessAuthService;

class AuthenticationServiceFactory extends AbstractAuthenticationServiceFactory {
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
		#die('here we try to create service! @'.__LINE__.': '.__FILE__);
		#$dbTableAuthAdapter = new DbTableAuthAdapter($serviceLocator->get('Zend\Db\Adapter\Adapter'), 'User', 'loginId', 'password', 'password(?)');
		#die('just stop here @'.__LINE__.': '.__FILE__);
		#$authService = new TfwAuthenticationService($serviceLocator->get('AuthStorage'), $dbTableAuthAdapter);
		#$authService = new AuthenticationService($serviceLocator->get('AuthStorage'), $serviceLocator->get('TfwAuthDatabase'));
        AuthenticationService::setStaticSystemPrefix($serviceLocator->get('AuthV2\\SysPrefix'));
        /* try {
			$authStorage=$serviceLocator->get('AuthStorage');
		} catch (\Exception $e) {
			die('@'.__LINE__.': '.__FILE__.' - '.$e->getMessage().'<pre>'.$e->getTraceAsString());
		} */
        $authStorage=$serviceLocator->get('AuthStorage');
        /* 
		try {
		    $authStorage=$serviceLocator->get('AuthStorage');
		} catch (\Exception $e) {
		    die('<h2>Authentication Storage Service NOT Initiated. Exception: '.$e->getMessage().'</h2><pre>'.$e->getTraceAsString().'</pre>');
		} */
		#die('fount authstorate @'.__LINE__.': '.__FILE__);
        $vldtblAuthAdptr=$serviceLocator->get('AuthV2\\ValidatableAuthAdapter');
        /* 
        try {
            $vldtblAuthAdptr=$serviceLocator->get('AuthV2\\ValidatableAuthAdapter');
        } catch (\Exception $e) {
            die('<h2>Validatable Authentication Adapter NOT Initiated. Exception: '.$e->getMessage().'</h2><pre>'.$e->getTraceAsString().'</pre>');
        } */
        $need2clear=false;
        $token=$this->getTokenFromHeader();
        $usrTbl=$this->getUserTable();
        #die('is it ok? @'.__LINE__.': '.__FILE__);
		if(!is_null($token)){
		    #die('$token: '.$token.' @'.__LINE__.': '.__FILE__);
			$auth2rtrn=new StatelessAuthService($authStorage, $vldtblAuthAdptr, $usrTbl);
			$auth2rtrn->setClosureForIdentityArray($this->getClosureForIdentityArray());
			try{
				$auth2rtrn->setToken($token);
				#die('token set done!!! @'.__LINE__.': '.__FILE__.PHP_EOL.PHP_EOL);
			}catch(\Exception $e){
			    #die($e->getMessage().'<pre>'.$e->getTraceAsString().PHP_EOL.PHP_EOL);
				$need2clear=true;
				$auth2rtrn = new AuthenticationService($authStorage, $vldtblAuthAdptr, $usrTbl);
			}
		}else $auth2rtrn = new AuthenticationService($authStorage, $vldtblAuthAdptr, $usrTbl);
		$auth2rtrn->setClosureForIdentityArray($this->getClosureForIdentityArray());
		if(true==$need2clear && $auth2rtrn->hasIdentity()) $auth2rtrn->clearIdentity();
		#die('just stop here @'.__LINE__.': '.__FILE__);
		if($this->isPreflightCorsRequested()) $auth2rtrn->setAsPreflightCorsRequest();
		return $auth2rtrn;
	}
}