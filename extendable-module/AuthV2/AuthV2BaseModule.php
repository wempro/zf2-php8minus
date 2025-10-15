<?php
namespace AuthV2;
use ProjectCore\User\LoggedInUserHandler;
use Oacs\Api\OacsAuthenticationService;

/**
 * Description of AuthV2BaseModule
 *
 * @author shkhan
 */
class AuthV2BaseModule {
    /**
     * @deprecated
     * @var string
     */
    const TBLSTRU_NAME='AuthV2User';
    /**
     * @deprecated
     * @var string
     */
    const TBLSTRU_COL__ID='textId';
    /**
     * @deprecated
     * @var string
     */
    const TBLSTRU_COL__PASS='pass';
    /**
     * @deprecated
     * @var string
     */
    #const TBLSTRU_COL__ID='xxx';
    /**
     * @return string
     */
    public function getNameSpace(){ return __NAMESPACE__; }
    public function getCurrentModulePath(){ return __DIR__ . DS; }

    private $cssData;
    public function setCssFiles(array $pCssFiles) {
        if(empty($pCssFiles)) throw new \Exception('Files Should NOT Left Blank!!!');
        $this->cssData['files']=$pCssFiles;
        return $this;
    }
    public function setCssClosures($cssClosures, $code=false) {
        if(empty($cssClosures)) throw new \Exception('CSS Closure Should NOT Left Blank!!!');
        if(!isset($this->cssData)) $this->cssData=array();
        $idx=(true==$code ? 'code' : 'files');
        $this->cssData['closures'][$idx]=(is_array($cssClosures) ? $cssClosures : array($cssClosures));
        return $this;
    }
    public function addCssClosure($cssClosure, $code=false) {
        if(empty($cssClosure)) throw new \Exception('CSS Closure Should NOT Left Blank!!!');
        if(!isset($this->cssData)) $this->cssData=array();
        if(!array_key_exists('closures', $this->cssData)) $this->cssData['closures']=array();
        $idx=(true==$code ? 'code' : 'files');
        if(!array_key_exists($idx, $this->cssData['closures'])) $this->cssData['closures'][$idx]=array();
        if(is_array($cssClosure)) $this->cssData['closures'][$idx]+=$cssClosure;
        else $this->cssData['closures'][$idx][]=$cssClosure;
        return $this;
    }
    public function setCssCode($pCssCode, $pAppend=false) {
        if(empty($pCssCode)) throw new \Exception('CSS Code Should NOT Left Blank!!!');
        if(!isset($this->cssData)) $this->cssData=array();
        if(true==$pAppend) $pCssCode=(array_key_exists('code', $this->cssData)?$this->cssData['code'].PHP_EOL.PHP_EOL:'').$pCssCode;
        $this->cssData['code']=$pCssCode;
        return $this;
    }
    private function getCssData() {
        if(!isset($this->cssData)) return array();
        return $this->cssData;
    }
    private $jsData;
    public function setJsFiles(array $pJsFiles) {
        if(empty($pJsFiles)) throw new \Exception('Files Should NOT Left Blank!!!');
        $this->jsData['files']=$pJsFiles;
        return $this;
    }
    public function setJsClosures($jsClosures, $code=false) {
        if(empty($jsClosures)) throw new \Exception('JS Closure Should NOT Left Blank!!!');
        if(!isset($this->jsData)) $this->jsData=array();
        $idx=(true==$code ? 'code' : 'files');
        $this->jsData['closures'][$idx]=(is_array($jsClosures) ? $jsClosures : array($jsClosures));
        return $this;
    }
    public function addJsClosure($jsClosure, $code=false) {
        if(empty($jsClosure)) throw new \Exception('JS Closure Should NOT Left Blank!!!');
        if(!isset($this->jsData)) $this->jsData=array();
        if(!array_key_exists('closures', $this->jsData)) $this->jsData['closures']=array();
        $idx=(true==$code ? 'code' : 'files');
        if(!array_key_exists($idx, $this->jsData['closures'])) $this->jsData['closures'][$idx]=array();
        if(is_array($jsClosure)) $this->jsData['closures'][$idx]+=$jsClosure;
        else $this->jsData['closures'][$idx][]=$jsClosure;
        return $this;
    }
    public function setJsCode($pJsCode, $pAppend=false) {
        if(empty($pJsCode)) throw new \Exception('JS Code Should NOT Left Blank!!!');
        if(!isset($this->jsData)) $this->jsData=array();
        if(true==$pAppend) $pJsCode=(array_key_exists('code', $this->jsData)?$this->jsData['code'].PHP_EOL.PHP_EOL:'').$pJsCode;
        $this->jsData['code']=$pJsCode;
        return $this;
    }
    private function getJsData() {
        if(!isset($this->jsData)) return array();
        return $this->jsData;
    }

    private $closureForIdentityArray;
    public function setClosureForIdentityArray(\Closure $pClosure){
        $this->closureForIdentityArray=$pClosure;
        return $this;
    }
    #private function isClosureForIdentityArrayExists(){ return isset($this->loggedInUserDataClosure); }
    /**
     * protected function getIdentityArrayToStore($pIdentity, \Lib3rdParty\Authentication\AuthenticatedUserObjectInterface $pUserOrgiObj, $pIpAddress, $pUserAgent, $pUserArrayObject=null)
     * 
     * # @var \WvmDb\Registration\User\User $pUserOrgiObj
		return array(
				'id'			=> (!is_null($pUserArrayObject)?$pUserArrayObject->id:$pIdentity),
				'username'		=> $pIdentity,
				'useremail'		=> $pUserOrgiObj->getEmailAddress(),
				'fullname'		=> $pUserOrgiObj->getName(),
				'ip_address'		=> $pIpAddress,
				'user_agent'		=> $pUserAgent,
			);
     * 
     * @throws \Exception
     * @return \Closure
     */
    private function getClosureForIdentityArray(){
        if(!isset($this->closureForIdentityArray)){
            return function ($pIdentity, \Lib3rdParty\Authentication\AuthenticatedUserObjectInterface $pUserOrgiObj, $pIpAddress, $pUserAgent, $pUserArrayObject = null) {
                /** @var \stdClass $pUserArrayObject **/
                /** @var \AuthV2\Model\UserX $pUserOrgiObj **/
                $d2r=array(
                    'id' => (! is_null($pUserArrayObject) ? $pUserArrayObject->id : $pIdentity),
                    'username' => $pIdentity,
                    'useremail' => $pUserOrgiObj->getEmailAddress(),
                    'fullname' => $pUserOrgiObj->getName(),
                    'ip_address' => $pIpAddress,
                    'user_agent' => $pUserAgent
                );
                if(method_exists($pUserOrgiObj, 'getThisPrimaryFieldName')){
                    $pk=$pUserOrgiObj->getThisPrimaryFieldName();
                    if(!array_key_exists($pk, $d2r)) $d2r[$pk]=$d2r['username'];
                }
                return $d2r;
            };
            #throw new \Exception('Logged In User Data Closure NOT Found!!!');
        }
        return $this->closureForIdentityArray;
    }
    private $sessTableDbAdapterServiceIdx;
    public function setSessionTableDbAdapterServiceIndex($pSessionTableDbAdapterServiceIdx){
        $this->sessTableDbAdapterServiceIdx=$pSessionTableDbAdapterServiceIdx;
        return $this;
    }
    private function getSessionTableDbAdapterServiceIndex(){
        if(!isset($this->sessTableDbAdapterServiceIdx)) throw new \Exception('please set session table db adapter service index to continue!!!');
        return $this->sessTableDbAdapterServiceIdx;
    }
    private $authSysPrefixClosure;
    public function setAuthSystemPrefixClosure(\Closure $pSysPrefix){
        #Api\AuthenticationService::setSystemPrefix($pSysPrefix);
    	$this->authSysPrefixClosure=$pSysPrefix;
        return $this;
    }
    private function getAuthSystemPrefixClosure(){
        if(!isset($this->authSysPrefixClosure)) return function($sm){ return 'authV2test'; };
        return $this->authSysPrefixClosure;
    }
/*
    private $sessionSaveHandlerName;
    public function setSessionSaveHandlerName($pSessionSaveHandlerName){
        #die('@'.__LINE__.': $pSessionTableName - '.$pSessionTableName.' | '.__FILE__);
        $this->sessionSaveHandlerName=$pSessionSaveHandlerName;
        return $this;
    }
    private function getSessionSaveHandlerName(){
        if(!isset($this->sessionSaveHandlerName)) return $this->getSessionTableName().date('Ymd');
        return $this->sessionSaveHandlerName;
    }
 */
    private $sessionTableName;
    public function setSessionTableName($pSessionTableName){
        #die('@'.__LINE__.': $pSessionTableName - '.$pSessionTableName.' | '.__FILE__);
        $this->sessionTableName=$pSessionTableName;
        return $this;
    }
    private function getSessionTableName(){
        if(!isset($this->sessionTableName)) throw new \Exception('please set session table name to continue!!!');
        return $this->sessionTableName;
    }
    private $siteOwnerServiceIdx;
    public function setSiteOwnerServiceIndex($pSiteOwnerServiceIdx){
	    	$this->siteOwnerServiceIdx=$pSiteOwnerServiceIdx;
	    	return $this;
    }
    private function getSiteOwnerServiceIndex(){
	    	if(!isset($this->siteOwnerServiceIdx)) throw new \Exception('please set site owner service index to continue!!!');
	    	return $this->siteOwnerServiceIdx;
    }
    private $usrLoginHndlrSrvcIdx;
    public function setUserLoginHandlerServiceIndex($pUserLoginHandlerServiceIndex){
	    	$this->usrLoginHndlrSrvcIdx=$pUserLoginHandlerServiceIndex;
	    	return $this;
    }
//    private function getUserLoginHandlerServiceIndex(){
//	    	if(!isset($this->usrLoginHndlrSrvcIdx)) throw new \Exception('please set service index to get user login handler to continue!!!');
//	    	return $this->usrLoginHndlrSrvcIdx;
//    }
    private $contentTrackerServiceIdx;
    public function setContentTrackerServiceIndex($pContentTrackerServiceIdx){
	    	$this->contentTrackerServiceIdx=$pContentTrackerServiceIdx;
	    	return $this;
    }
    private $userTableClosure;
    public function setUserTableClosure(\Closure $pSiteOwnerServiceIdx){
	    	$this->userTableClosure=$pSiteOwnerServiceIdx;
	    	return $this;
    }
    private function getUserTableClosure(){
        if(!isset($this->userTableClosure)){
            $sessTableDbAdptrSrvcIdx=$this->getSessionTableDbAdapterServiceIndex();
            return function (\Zend\ServiceManager\ServiceManager $sm) use ($sessTableDbAdptrSrvcIdx) {
                return new \AuthV2\Model\AuthenticationUserTableX(\AuthV2\Model\UserX::getTg($sm->get($sessTableDbAdptrSrvcIdx)));
            };
            #throw new \Exception('please set user table closure to continue!!!');
        }
    	return $this->userTableClosure;
    }
    private $forgotPassLnkClsr;
    public function setForgotPasswordLinkClosure(\Closure $pForgotPasswordLinkClosure){
	    	$this->forgotPassLnkClsr=$pForgotPasswordLinkClosure;
	    	return $this;
    }
    private $regLnkClsr;
    public function setRegistrationLinkClosure(\Closure $pRegLinkClosure){
	    	$this->regLnkClsr=$pRegLinkClosure;
	    	return $this;
    }
    private $alsLnkClsr;
    public function setAfterLoginSuccessLinkClosure(\Closure $pAlsLinkClosure){
	    	$this->alsLnkClsr=$pAlsLinkClosure;
	    	return $this;
    }
    private $validatableAuthAdapterClosure;
    public function setValidatableAuthAdapterClosure(\Closure $pValidatableAuthAdapterClosure){
	    	$this->validatableAuthAdapterClosure=$pValidatableAuthAdapterClosure;
	    	return $this;
    }
    private function getValidatableAuthAdapterClosure(){
	    	if(!isset($this->validatableAuthAdapterClosure)){
                    $this->validatableAuthAdapterClosure=function(\Zend\ServiceManager\ServiceManager $sm){
                        $oacsUserTable=$sm->get('AuthV2\\User\\UserTable');
                        $crdntialColumnName=$oacsUserTable->getCredentialColumn();
			#die('identity column: '.$oacsUserTable->getIdentityColumn().' @'.__LINE__.': '.__FILE__);
			             return new \Lib3rdParty\Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter($oacsUserTable->getAdapter(), $oacsUserTable->getMyTableName(), $oacsUserTable->getIdentityColumn(), $crdntialColumnName, function (array $pUserData, $pCredential) use ($crdntialColumnName) {
			                 #die('@'.__LINE__.': '.__FILE__.'<br />'.PHP_EOL.'$pCredential: '.$pCredential.' | $crdntialColumnName: '.$crdntialColumnName.' | md5($pCredential): '.md5($pCredential).'<pre>'.print_r($pUserData, true));
			                 return ($pUserData[$crdntialColumnName]===md5($pCredential)); });
                    };
                    #throw new \Exception('please set validatable authentication adapter closure to continue!!!');
                }
	    	return $this->validatableAuthAdapterClosure;
    }
    public function getServiceConfig(array $pExistingServiceArr=array()){
        #die('@'.__LINE__.': $this->getSessionTableName() - '.$this->getSessionTableName().' | '.__FILE__);
        $clsr4identity=$this->getClosureForIdentityArray();
        $sessTableName=$this->getSessionTableName();
        $sessTblDbAdptrSrvcIdx=$this->getSessionTableDbAdapterServiceIndex();
        try {
            $siteOwnerSrvcIdx=$this->getSiteOwnerServiceIndex();
        } catch (\Exception $e) {
            $siteOwnerSrvcIdx=null;
        }
        $usrTblClsr=$this->getUserTableClosure();
        $sysPrefixClsr=$this->getAuthSystemPrefixClosure();
        $vldtblAuthAdptrClsr=$this->getValidatableAuthAdapterClosure();
        $jsCssData=array('js'=>$this->getJsData(), 'css'=>$this->getCssData());
        $s2r['AuthV2\\JsCssData']=function(\Zend\ServiceManager\ServiceManager $sm) use($jsCssData) {
            $rtrn=array('js'=>array(), 'css'=>array());
            foreach($jsCssData as $k=>$v){
                if(array_key_exists('closures', $v)){
                    foreach($v['closures'] as $x=>$y){
                        if(!array_key_exists($x, $v)) $v[$x]=($x=='files' ? array() : '');
                        foreach($y as $p) $v[$x]+=($x=='files' ? array($p($sm)) : $p($sm));
                    }
                    unset($v['closures']);
                }
                $rtrn[$k]=$v;
            }
            return $rtrn;
        };
        $s2r['AuthV2\\ValidatableAuthAdapter']=function (\Zend\ServiceManager\ServiceManager $sm) use($vldtblAuthAdptrClsr) {
            try {
                return $vldtblAuthAdptrClsr($sm);
            } catch (\Exception $e) {
                die($e->getMessage().' @'.__LINE__.': '.__FILE__.'<pre>'.$e->getTraceAsString());
            }
            
        };
        $s2r['AuthV2\\SysPrefix']=function (\Zend\ServiceManager\ServiceManager $sm) use($sysPrefixClsr) { return $sysPrefixClsr($sm); };
        if(isset($this->usrLoginHndlrSrvcIdx)){
            $s2r[$this->usrLoginHndlrSrvcIdx]=function (\Zend\ServiceManager\ServiceManager $sm) {
                if(LoggedInUserHandler::isExists()) return LoggedInUserHandler::getOnce();
                #echo '@'.__LINE__.': '.__FILE__.'<br />';
                return LoggedInUserHandler::getOnce($sm->get('AuthenticationService'));
            };
        }
        if(isset($this->contentTrackerServiceIdx)){
        	$cntntTrckrIdx=$this->contentTrackerServiceIdx;
        	$s2r['AuthV2\\ContentTracker']=function (\Zend\ServiceManager\ServiceManager $sm) use($cntntTrckrIdx) { return $sm->get($cntntTrckrIdx); };
        }
        if(isset($this->forgotPassLnkClsr)){
	        	$fpLnkClsr=$this->forgotPassLnkClsr;
	        	$s2r['AuthV2\\ForgotPasswordLink']=function (\Zend\ServiceManager\ServiceManager $sm) use($fpLnkClsr) { return $fpLnkClsr($sm->get('ViewHelperManager')->get('url')); };
        }
        if(isset($this->regLnkClsr)){
	        	$regLnkClsrX=$this->regLnkClsr;
	        	$s2r['AuthV2\\RegistrationLink']=function (\Zend\ServiceManager\ServiceManager $sm) use($regLnkClsrX) { return $regLnkClsrX($sm->get('ViewHelperManager')->get('url')); };
        }
        if(isset($this->alsLnkClsr)){
	        	$aftrLgnSccssLnkClsr=$this->alsLnkClsr;
	        	$s2r['AuthV2\\AfterLoginSuccessLink']=function (\Zend\ServiceManager\ServiceManager $sm) use($aftrLgnSccssLnkClsr) { return $aftrLgnSccssLnkClsr($sm->get('ViewHelperManager')->get('url')); };
        }
        $s2r['UserSessTableName']=function (\Zend\ServiceManager\ServiceManager $sm) use($sessTableName) { return $sessTableName; };
        $s2r['AuthV2\\UserSessionTableName']=function (\Zend\ServiceManager\ServiceManager $sm) use($sessTableName) { return $sessTableName; };
        $s2r['AuthV2\\ActiveCompany']=function (\Zend\ServiceManager\ServiceManager $sm) use($siteOwnerSrvcIdx) {
            if(empty($siteOwnerSrvcIdx)) throw new \Exception('company object NOT defined...');
            return $sm->get($siteOwnerSrvcIdx); };
        $s2r['TfwAuthDatabase']=function (\Zend\ServiceManager\ServiceManager $sm) use($sessTblDbAdptrSrvcIdx) { return $sm->get($sessTblDbAdptrSrvcIdx); };
        $s2r['AuthV2\\User\\UserTable']=$usrTblClsr;
        $s2r['AuthV2\\ClosureForIdentityArray']=function (\Zend\ServiceManager\ServiceManager $sm) use($clsr4identity) { return $clsr4identity; };
        $s2r['AuthV2\\AuthorizationHeaderGenerator']=function (\Zend\ServiceManager\ServiceManager $sm) use($clsr4identity) {
            return function($pUser, $pEncryptedString){
                return array(
                    'key'   => \Lib3rdParty\Crypt\CommunicationEncryptDecrypt::AUTH_HEADER_IDX,
                    'value' => \Lib3rdParty\Crypt\CommunicationEncryptDecrypt::getAuthorizationHeaderValue($pUser, $pEncryptedString),
                );
            };
        };
        foreach($s2r as $k=>$srvc) $pExistingServiceArr['factories'][$k]=$srvc;
        return $pExistingServiceArr;
    }
    public function getViewHelperConfig(array $pExistingServiceArr=array()) {
        $pExistingServiceArr['factories']['loginForm']=function(\Zend\View\HelperPluginManager $helperPluginManager) {
                        $viewHelper = new View\Helper\LoginForm();
                        $viewHelper->setServiceLocator($helperPluginManager->getServiceLocator());
                        return $viewHelper;
                };
        return $pExistingServiceArr;
    }
}

/**
 * usage: create two table, one for session another for users.
 * following example use default user table struction which is NOT recommended.
 * following example intended to show how this module can use, please extends all the method to use your own user table.
 * 

    private function getBaseModule(){
        if (! isset($this->baseModule)) {
            $this->baseModule = new \AuthV2\AuthV2BaseModule();
            $this->baseModule->setSessionTableDbAdapterServiceIndex($this->getServiceIdxForSessionDbAdapter());
            $this->baseModule->setSessionTableName($this->getSessionTableName());
            $this->baseModule->setUserLoginHandlerServiceIndex(self::getServiceIdxToGetUserLoginHandler());
        }
        return $this->baseModule;
    }

    public function getServiceConfig(){ return $this->getBaseModule()->getServiceConfig(parent::getServiceConfig()); }
    public function getViewHelperConfig(){ return $this->getBaseModule()->getViewHelperConfig(parent::getViewHelperConfig()); }


 * 
 */


