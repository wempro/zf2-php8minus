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
	private $userTableClosure;
	public function setUserTableClosure(\Closure $pSiteOwnerServiceIdx){
		$this->userTableClosure=$pSiteOwnerServiceIdx;
		return $this;
	}
	private function getUserTableClosure(){
		if(!isset($this->userTableClosure)){
			return function(\Zend\ServiceManager\ServiceManager $sm){
				$tblOacs=new OacsUserTable($sm->get('OacsUserTableGateway'));
				$tblOacs->setCompanyTextId($sm->get('OacsCompanyTextId'));
				return $tblOacs;
			};
			#throw new \Exception('please set user table closure to continue!!!');
		}
		return $this->userTableClosure;
	}
	private $authSysPrefix;
        public function setAuthSystemPrefix($pLoginUserNamespace){
            $this->authSysPrefix=$pLoginUserNamespace;
            return $this;
        }
        protected function getAuthSystemPrefix(){
            if(!isset($this->authSysPrefix)) return 'oacsAuth';
            return $this->authSysPrefix;
        } # getClosureForListActionUrl
    private $closureForListActionUrl;
    public function setClosureForListActionUrl(\Closure $pReadUrl){
        $this->closureForListActionUrl=$pReadUrl;
        return $this;
    }
    private function getClosureForListActionUrl(){
        if(!isset($this->closureForListActionUrl)) return function($pUrl){
                            return '';
                        };
        return $this->closureForListActionUrl;
    }
    private $closureForReadActionUrl;
    public function setClosureForReadActionUrl(\Closure $pReadUrl){
        $this->closureForReadActionUrl=$pReadUrl;
        return $this;
    }
    private function getClosureForReadActionUrl(){
        if(!isset($this->closureForReadActionUrl)) return function($pUrl, $pTextId=null){
                            return '';
                        };
        return $this->closureForReadActionUrl;
    }
    private $closureForIdentityArray;
    public function setClosureForIdentityArray(\Closure $pClosure){
        	$this->closureForIdentityArray=$pClosure;
        	return $this;
    }
    private function getClosureForIdentityArray(){
        	if(!isset($this->closureForIdentityArray)){
        		return function($pIdentity, \Lib3rdParty\Authentication\AuthenticatedUserObjectInterface $pUserOrgiObj, $pIpAddress, $pUserAgent, $pUserArrayObject=null){
        			/** @var \stdClass $pUserArrayObject **/
        			/** @var \WvmDb\Registration\User\User $pUserOrgiObj **/
        			return array(
        				'id'			=> (!is_null($pUserArrayObject)?$pUserArrayObject->id:$pIdentity),
        				'textId'		=> $pUserOrgiObj->getTextId(),
        				'useremail'		=> $pUserOrgiObj->getEmailAddress(),
        				'fullname'		=> $pUserOrgiObj->getName(),
        				'ip_address'	=> $pIpAddress,
        				'user_agent'	=> $pUserAgent,
        				'companyTextId' => $pUserArrayObject->companyTextId,
        			);
        		};
        	    #throw new \Exception('Logged In User Data Closure NOT Found!!!');
    	    }
        	return $this->closureForIdentityArray;
    }
    private $authServiceClosure;
    public function setAuthenticationServiceClosure(\Closure $pSiteOwnerServiceIdx){
            $this->authServiceClosure=$pSiteOwnerServiceIdx;
            return $this;
    }
    private function getBaseAuthenticationServiceClosure(){
        $lggdInUsrDataClsr=($this->isLoggedInUserDataClosureExists()?$this->getLoggedInUserDataClosure():null);
        $oacsSysPrefix=$this->getAuthSystemPrefix();
        $clsr4identity=$this->getClosureForIdentityArray();
            return function (\Zend\ServiceManager\ServiceManager $sm) use($lggdInUsrDataClsr, $oacsSysPrefix, $clsr4identity) {
                #$auth2rtrn=new OacsAuthenticationService(new OacsAuthStorage('oacsLoginUser', null, $sm->get('Zend\\Session\\SessionManager')), $sm->get($pDbAdapterServiceIdx), $sm->get('Oacs\\Model\\OacsUserTable'));
            OacsAuthenticationService::setStaticSystemPrefix($oacsSysPrefix);
            $auth2rtrn=new OacsAuthenticationService(new OacsAuthStorage($oacsSysPrefix, null, $sm->get('Zend\\Session\\SessionManager')), $sm->get('Oacs\\ValidatableAuthAdapter'), $sm->get('Oacs\\Model\\OacsUserTable'));
                /** @var $lggdInUsrDataClsr \Closure */
            $auth2rtrn->setClosureForIdentityArray($clsr4identity);
                $url=$sm->get('ViewHelperManager')->get('url');
                $auth2rtrn->setLoginUrl($url('oacs', array(
                                                'action' => 'login',
                                ), array('force_canonical' => true)));
                return $auth2rtrn;
        };
    }
    private function getAuthenticationServiceClosure(){
            if(!isset($this->authServiceClosure)){
                return $this->getBaseAuthenticationServiceClosure();
                    #throw new \Exception('please set user table closure to continue!!!');
            }
            return $this->authServiceClosure;
    }
    private $validatableAuthAdapterClosure;
    public function setValidatableAuthAdapterClosure(\Closure $pValidatableAuthAdapterClosure){
    	    $this->validatableAuthAdapterClosure=$pValidatableAuthAdapterClosure;
    	    return $this;
    }
    private function getValidatableAuthAdapterClosure(){
        if(!isset($this->validatableAuthAdapterClosure)){
        		$this->validatableAuthAdapterClosure=function(\Zend\ServiceManager\ServiceManager $sm){
        			$oacsUserTable=$sm->get('Oacs\\Model\\OacsUserTable');
        			$cmpnTxtId=$sm->get('OacsCompanyTextId');
        			$crdntialColumnName=$oacsUserTable->getCredentialColumn();
        			#die('identity column: '.$oacsUserTable->getIdentityColumn().' @'.__LINE__.': '.__FILE__);
        			return new \Lib3rdParty\Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter($oacsUserTable->getAdapter(), $oacsUserTable->getMyTableName(), $oacsUserTable->getIdentityColumn(), $crdntialColumnName, function (array $pUserData, $pCredential) use ($crdntialColumnName, $cmpnTxtId) {
        				    if($pUserData['companyTextId']===$cmpnTxtId){
        				    		$bcrpt=new \Zend\Crypt\Password\Bcrypt();
        				    		return $bcrpt->verify($pCredential, $pUserData[$crdntialColumnName]);
        				    }
        				    return false;
        			    });
        		};
        		#throw new \Exception('please set validatable authentication adapter closure to continue!!!');
        	}
        	return $this->validatableAuthAdapterClosure;
    }
    public function getServiceConfig(array $pExistingServices, $pDbAdapterServiceIdx, $pServiceIdxToGetCompanyTextId=null) {
		$usrTblClsr=$this->getUserTableClosure();
		$vldtblAuthAdptrClsr=$this->getValidatableAuthAdapterClosure();
		#$pExistingServices['factories']['Oacs\\DbAdapter']=function (\Zend\ServiceManager\ServiceManager $sm) use($pDbAdapterServiceIdx) { return $sm->get($pDbAdapterServiceIdx); };
		#$pExistingServices['factories']['Oacs\\ClosureForIdentityArray']=function (\Zend\ServiceManager\ServiceManager $sm) use($clsr4identity) { return $clsr4identity; };
		$pExistingServices['factories']['Oacs\\ValidatableAuthAdapter']=function (\Zend\ServiceManager\ServiceManager $sm) use($vldtblAuthAdptrClsr) { return $vldtblAuthAdptrClsr($sm); };
		$pExistingServices['factories']['OacsCompanyTextId']=function (\Zend\ServiceManager\ServiceManager $sm) use($pServiceIdxToGetCompanyTextId) {
                    if(empty($pServiceIdxToGetCompanyTextId)) return '';
                    return $sm->get($pServiceIdxToGetCompanyTextId);
                };
		$pExistingServices['factories']['Oacs\\Model\\OacsUserTable']=function (\Zend\ServiceManager\ServiceManager $sm) use($usrTblClsr) {
			return $usrTblClsr($sm);
			$tblOacs=new OacsUserTable($sm->get('OacsUserTableGateway'));
			$tblOacs->setCompanyTextId($sm->get($pServiceIdxToGetCompanyTextId));
			return $tblOacs;
		};
		$pExistingServices['factories']['OacsUserTableGateway']=function (\Zend\ServiceManager\ServiceManager $sm) use($pDbAdapterServiceIdx) { return OacsUser::getTg($sm->get($pDbAdapterServiceIdx)); };
		$pExistingServices['factories']['AuthenticationService']=$this->getAuthenticationServiceClosure();
                $pExistingServices['factories']['Oacs\\Base\\AuthenticationService']=$this->getBaseAuthenticationServiceClosure();
                $readUrlClosr=$this->getClosureForReadActionUrl();
                $pExistingServices['factories']['Oacs\\ReadUrlClosure']=function (\Zend\ServiceManager\ServiceManager $sm) use($readUrlClosr) {
                        # $aftrLgnSccssLnkClsr($sm->get('ViewHelperManager')->get('url'))
                        return $readUrlClosr;
                    };
                $listUrlClosr=$this->getClosureForListActionUrl();
                $pExistingServices['factories']['Oacs\\ListUrlClosure']=function (\Zend\ServiceManager\ServiceManager $sm) use($listUrlClosr) {
                        # $aftrLgnSccssLnkClsr($sm->get('ViewHelperManager')->get('url'))
                        return $listUrlClosr;
                    };
                $usrManageByOacsFl=true;
                $pExistingServices['factories']['Oacs\\UserManageByOacsFlag']=function (\Zend\ServiceManager\ServiceManager $sm) use($usrManageByOacsFl) { return $usrManageByOacsFl; };
		return $pExistingServices;
	}
}


