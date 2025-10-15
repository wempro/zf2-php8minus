<?php
namespace Oacs;

use Oacs\Api\OacsAuthenticationService;
use Oacs\Model\OacsUser;
use Oacs\Model\OacsUserTable;
use Oacs\Storage\OacsAuthStorage;

class OacsBaseModule {
	public function getNameSpace(){ return __NAMESPACE__; }
	public function getCurrentModulePath(){ return __DIR__ . DS; }
	#protected function isModuleSecure(){ return false; }
	#protected function showLeftMenuAtIndex(){ return true; }
	private $loggedInUserDataClosure;
	public function setLoggedInUserDataClosure(\Closure $pClosure){
		$this->loggedInUserDataClosure=$pClosure;
		return $this;
	}
	private function isLoggedInUserDataClosureExists(){ return isset($this->loggedInUserDataClosure); }
	private function getLoggedInUserDataClosure(){
		if(!isset($this->loggedInUserDataClosure)) throw new \Exception('Logged In User Data Closure NOT Found!!!');
		return $this->loggedInUserDataClosure;
	}
        private $authSysPrefix;
        public function setAuthSystemPrefix($pLoginUserNamespace){
            $this->authSysPrefix=$pLoginUserNamespace;
            return $this;
        }
        protected function getAuthSystemPrefix(){
            if(!isset($this->authSysPrefix)) return 'oacsAuth';
            return $this->authSysPrefix;
        }
	public function getServiceConfig(array $pExistingServices, $pDbAdapterServiceIdx, $pServiceIdxToGetCompanyTextId) {
            $oacsSysPrefix=$this->getAuthSystemPrefix();
		$lggdInUsrDataClsr=($this->isLoggedInUserDataClosureExists()?$this->getLoggedInUserDataClosure():null);
		$pExistingServices['factories']['OacsCompanyTextId']=function (\Zend\ServiceManager\ServiceManager $sm) use($pServiceIdxToGetCompanyTextId) { return $sm->get($pServiceIdxToGetCompanyTextId); };
		$pExistingServices['factories']['Oacs\\Model\\OacsUserTable']=function (\Zend\ServiceManager\ServiceManager $sm) {
			$tblOacs=new OacsUserTable($sm->get('OacsUserTableGateway'));
			$tblOacs->setCompanyTextId($sm->get('OacsCompanyTextId'));
			return $tblOacs;
		};
		$pExistingServices['factories']['OacsUserTableGateway']=function (\Zend\ServiceManager\ServiceManager $sm) use($pDbAdapterServiceIdx) { return OacsUser::getTg($sm->get($pDbAdapterServiceIdx)); };
		$pExistingServices['factories']['AuthenticationService']=function (\Zend\ServiceManager\ServiceManager $sm) use($pDbAdapterServiceIdx, $lggdInUsrDataClsr, $oacsSysPrefix) {
			#$auth2rtrn=new OacsAuthenticationService(new OacsAuthStorage('oacsLoginUser', null, $sm->get('Zend\\Session\\SessionManager')), $sm->get($pDbAdapterServiceIdx), $sm->get('Oacs\\Model\\OacsUserTable'));
                    OacsAuthenticationService::setSystemPrefix($oacsSysPrefix);
			$auth2rtrn=new OacsAuthenticationService(new OacsAuthStorage($oacsSysPrefix, null, $sm->get('Zend\\Session\\SessionManager')), $sm->get('Oacs\\Model\\OacsUserTable'));
			/** @var $lggdInUsrDataClsr \Closure */
			if(!is_null($lggdInUsrDataClsr)) $auth2rtrn->setLoggedInUserDataClosure($lggdInUsrDataClsr($sm));
			$url=$sm->get('ViewHelperManager')->get('url');
			$auth2rtrn->setLoginUrl($url('oacs', array(
							'action' => 'login',
					), array('force_canonical' => true)));
			return $auth2rtrn;
		};
		return $pExistingServices;
	}
}


