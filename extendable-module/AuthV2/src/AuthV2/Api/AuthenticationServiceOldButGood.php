<?php
namespace AuthV2\Api;

use Zend\Session\Container;
#use Login\Controller\LoginController;
use Zend\Mvc\Controller\AbstractController;
use Zend\Authentication\AuthenticationService as ZendAuthAuthenticationService;
use Zend\Authentication\Storage\StorageInterface;
#use Zend\Authentication\Adapter\AdapterInterface;
use AuthV2\Model\CredentialTreatmentAdapter as DbTableAuthAdapter;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Stdlib\Response;
#use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;
#use TFW\Misc\TfwEncryptDecrypt;
use Lib3rdParty\Soap\TfwSoapRequestController;
use Lib3rdParty\Soap\TfwSoapResponseInterface;
use AuthV2\Model\User as TfwAuthUser;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Session\Validator\HttpUserAgent;
use Zend\Db\Sql\Where;
#use Lib3rdParty\Zend\Authentication\AuthenticationServiceInterface;
use ProjectCore\User\UnAuthenticatedAdmin;
use Lib3rdParty\Authentication\AuthenticationServiceInterface;

class AuthenticationServiceOldButGood extends ZendAuthAuthenticationService implements TfwSoapResponseInterface, AuthenticationServiceInterface {
	private $tfwSess;
	private $tgUser;
	private $authDbAdapter;
	private $dbAdapter;
	private $user;
	private $sessCustomData;
	private $urlHandler;
	public function __construct(StorageInterface $storage, DbAdapter $adapter) {
		$this->dbAdapter=$adapter;
		parent::__construct($storage, $this->getAuthDbAdapter());
		$this->tfwSess=new Container('tfwUserSession');
		$this->sessCustomData=new Container('tfwSessionCustomData');
		$this->tgUser=TfwAuthUser::getMe($adapter);
		#die('url: '.$this->getAfterLoginUrl().' @'.__LINE__.': '.__FILE__);
	}
	protected function getPasswordValueSql(){ return TfwAuthUser::PASSWORD_CRYPT_SQL_WITH_MD5; }
	private function getAuthDbAdapter(){
		if(!isset($this->authDbAdapter)){
			$this->authDbAdapter = new DbTableAuthAdapter($this->dbAdapter, TfwAuthUser::getMyName(), TfwAuthUser::TBL_FLD_LOGINTEXTID, TfwAuthUser::TBL_FLD_PASSWORD, $this->getPasswordValueSql());
			$whr=new Where();
			$whr->equalTo(TfwAuthUser::TBL_FLD_USER_STATUS, TfwAuthUser::USER_STATUS_ACTIVE);
			$this->authDbAdapter->setAdditionalCondition($whr);
		}
		return $this->authDbAdapter;
	}
	protected function getDbAdapter(){ return $this->dbAdapter; }
	private $preflightRequestFl;
	public function setAsPreflightCorsRequest(){
		$this->preflightRequestFl=true;
		return $this;
	}
	public function isPreflightCorsRequested(){
		if(!isset($this->preflightRequestFl)) return false;
		return $this->preflightRequestFl;
	}
	private $id;
	private $tfwIdentity;
	private function getId($pReturnAnonymousIdIfExists=false){
		if($this->hasIdentity()){
			if(!isset($this->id)){
				if(!isset($this->tfwIdentity)) $this->tfwIdentity=$this->getIdentity();
				$this->id=$this->tfwIdentity['id'];
			}
			return $this->id;
		}
		if(true==$pReturnAnonymousIdIfExists && $this->isAnonymousUserLoggedIn()) return $this->getAnonymousUserId();
		throw new \Exception('no id found to return!');
	}
	/**
	 * @return \Lib3rdParty\Authentication\AuthenticatedUserObjectInterface
	 */
	public function getLoggedInUser(){
		if($this->hasIdentity()){
			if(!isset($this->loggedInRegisteredUserObj)){
				$rs=$this->tgUser->select(array(TfwAuthUser::TBL_FLD_LOGINTEXTID=>$this->getId()));
				if($rs->count()<=0) throw new \Exception('logged in user ['.$this->getId().'] NOT found @DB');
				$this->loggedInRegisteredUserObj=$rs->current();
			}
			return $this->loggedInRegisteredUserObj;
		}
		if($this->isAnonymousOrRegisteredUserLoggedIn()) throw new \Exception('Anon user logged in, unable to find user object!');
		throw new \Exception('NOT logged in');
	}
	public function clearIdentity(){
		parent::clearIdentity();
		$arr2clear=array('anonymousUserLoggedIn', 'anonymousEmailVerified', 'anonymousEmailVerifiedTemporary', 'anonymousUserName', 'anonymousUserEmail');
		foreach($arr2clear as $sIdx){
			if($this->tfwSess->offsetExists($sIdx)) $this->tfwSess->offsetUnset($sIdx);
		}
		return $this;
	}
	private function isIdentityExists($pIdentity){ return $this->getAdapter()->isIdentityExists($pIdentity); }

	/* anonymouse handler start */
	public function isAnonymousOrRegisteredUserLoggedIn(){ return $this->isAnonymousUserLoggedIn(true); }
	public function isAnonymousUserLoggedIn($pReturnRealUserLoggedInStatusIfAnonymousNotLoggedIn=FALSE){
		if(true==$pReturnRealUserLoggedInStatusIfAnonymousNotLoggedIn && $this->hasIdentity()) return true;
		if($this->tfwSess->offsetExists('anonymousUserLoggedIn') && $this->tfwSess->offsetExists('anonymousLoggedInUserData')){
			$anonymousLoggedInUserData=unserialize(base64_decode($this->tfwSess->offsetGet('anonymousLoggedInUserData')));
			$emlAnonymousUser=$this->getAnonymousUserEmail();
			# anonymous user must have email address
			# if email not exists than this user is not continue as anonymous user
			if(empty($emlAnonymousUser)) return false;
			if(is_array($anonymousLoggedInUserData) && !empty($anonymousLoggedInUserData) && array_key_exists('useremail', $anonymousLoggedInUserData) && $emlAnonymousUser==$anonymousLoggedInUserData['useremail']) return ('yes'==$this->tfwSess->offsetGet('anonymousUserLoggedIn'));
		}
		return false;
	}
	public function setAnonymousUserLoggedIn($pAnonymousUserFirstName=NULL, $pAnonymousUserLastName=NULL, $pAnonymousUserEmail=NULL){
		if($this->hasIdentity()) throw new \Exception('existing user already logged in, please ensure that user logged out and try this method... @'.__LINE__.': '.__FILE__);
		if(!empty($pAnonymousUserFirstName)) $this->setAnonymousUserName($pAnonymousUserFirstName, $pAnonymousUserLastName);
		if(!empty($pAnonymousUserEmail)) $this->setAnonymousUserEmail($pAnonymousUserEmail);
		$usrEmail=$this->getAnonymousUserEmail();
		if(empty($usrEmail)) throw new \Exception('ýou must provide email before set this user as anonymous');
		$rmt=new RemoteAddress();
		$agnt=new HttpUserAgent();
		$rtrn=array(
				'id'=>$this->getAnonymousUserId(),
				'username'=>$this->getAnonymousUserName(),
				'firstname'=>$this->getAnonymousUserFirstName(),
				'lastname'=>$this->getAnonymousUserLastName(),
				'useremail'=>$this->getAnonymousUserEmail(),
				'ip_address'=>$rmt->getIpAddress(),
				'user_agent'=>$agnt->getData(),
			);
		$this->tfwSess->offsetSet('anonymousLoggedInUserData', base64_encode(serialize($rtrn)));
		$this->tfwSess->offsetSet('anonymousUserLoggedIn', 'yes');
		return $this;
	}
	public function setAnonymousEmailVerified(){
		if($this->hasIdentity()) throw new \Exception('existing user already logged in, please ensure that user logged out and try this method... @'.__LINE__.': '.__FILE__);
		$this->tfwSess->offsetSet('anonymousEmailVerified', 'yes');
	}
	public function setAnonymousEmailVerifiedTemporary($pRef){
		if($this->hasIdentity()) throw new \Exception('existing user already logged in, please ensure that user logged out and try this method... @'.__LINE__.': '.__FILE__);
 		if(!$this->isAnonymousUserLoggedIn()) throw new \Exception('not logged in as anonymous user!');
 		#die('$pRef: '.$pRef.' @'.__LINE__.': '.__FILE__);
 		$vrfyTmp=($this->tfwSess->offsetExists('anonymousEmailVerifiedTemporary')?unserialize(base64_decode($this->tfwSess->offsetGet('anonymousEmailVerifiedTemporary'))):array());
		$vrfyTmp['_'.md5($pRef.$this->getAnonymousUserEmail())]=$pRef;
		$this->tfwSess->offsetSet('anonymousEmailVerifiedTemporary', base64_encode(serialize($vrfyTmp)));
		return $this;
	}
	public function isAnonymousEmailVerified($pRef=NULL){
		if($this->hasIdentity()) throw new \Exception('existing user already logged in, please ensure that user logged out and try this method... @'.__LINE__.': '.__FILE__);
		if(!$this->isAnonymousUserLoggedIn()) throw new \Exception('not logged in as anonymous user!');
		if($this->tfwSess->offsetExists('anonymousEmailVerified') && 'yes'===$this->tfwSess->offsetGet('anonymousEmailVerified')) return true;
		if(!empty($pRef)){
			#die('$pRef: '.$pRef.' @'.__LINE__.': '.__FILE__.'<pre>'.print_r($this->tfwSess->anonymousEmailVerifiedTemporary, true));
			if($this->tfwSess->offsetExists('anonymousEmailVerifiedTemporary')){
				$vrfyTmp=($this->tfwSess->offsetExists('anonymousEmailVerifiedTemporary')?unserialize(base64_decode($this->tfwSess->offsetGet('anonymousEmailVerifiedTemporary'))):array());
				$fRef='_'.md5($pRef.$this->getAnonymousUserEmail());
				#die('$pRef: '.$fRef.' @'.__LINE__.': '.__FILE__);
				return array_key_exists($fRef, $vrfyTmp);
			}
		}
		return false;
	}
	public function getAnonymousUserId(){ return -1; }
	public function getAnonymousUserName(){ return trim($this->getAnonymousUserFirstName().' '.$this->getAnonymousUserLastName()); }
	private function getAnonymousUserFirstName(){ return ($this->tfwSess->offsetExists('anonymousUserFirstName')?$this->tfwSess->offsetGet('anonymousUserFirstName'):'Anonymous'); }
	private function getAnonymousUserLastName(){ return ($this->tfwSess->offsetExists('anonymousUserLastName')?$this->tfwSess->offsetGet('anonymousUserLastName'):''); }
	public function setAnonymousUserName($pFirstName, $pLastName=null){
		if($this->hasIdentity()) throw new \Exception('existing user already logged in, please ensure that user logged out and try this method... @'.__LINE__.': '.__FILE__);
		$fName=trim(trim($pFirstName).' '.$pLastName);
		$this->tfwSess->offsetSet('anonymousUserFirstName', trim($pFirstName));
		$this->tfwSess->offsetSet('anonymousUserLastName', trim($pLastName));
		$this->tfwSess->offsetSet('anonymousUserName', $fName);
		return $this;
	}
	public function setAnonymousUserEmail($pEmail){
		if($this->hasIdentity()) throw new \Exception('existing user already logged in, please ensure that user logged out and try this method... @'.__LINE__.': '.__FILE__);
		if(empty($pEmail) || strlen(trim($pEmail))<=0) throw new \Exception('email can\'t be left blank');
		$this->tfwSess->offsetSet('anonymousUserEmail', $pEmail);
		return $this;
	}
	public function getAnonymousUserEmail(){
		if($this->tfwSess->offsetExists('anonymousUserEmail')) $e2r=trim($this->tfwSess->offsetGet('anonymousUserEmail'));
		if(!empty($e2r)) return $e2r;
		throw new \Exception('email for anonymous user NOT found');
	}
	public function getAnonymousIdentity(){
		if($this->hasIdentity()){
			#echo '<pre>'.print_r(debug_backtrace(null, 4), true);
			throw new \Exception('existing user already logged in, please ensure that user logged out and try this method... @'.__LINE__.': '.__FILE__);
		}
		if(!$this->isAnonymousUserLoggedIn()) throw new \Exception('user not logged in anonymously!');
		return unserialize(base64_decode($this->tfwSess->offsetGet('anonymousLoggedInUserData')));
	}
	public function isAnonymousSettingsOK(){
		$rtrn=false;
		$nm=$this->getAnonymousUserName();
		$em=$this->getAnonymousUserEmail();
		if(!empty($nm) && !empty($em)) $rtrn=true;
		return $rtrn;
	}
	/* anonymous handler end */
	private function getSessionId(){
		return $this->tfwSess->getManager()->getId();
	}
	private function setCustomData($pKey, $pVal){
		$this->sessCustomData->$pKey=$pVal;
		return $this;
	}
	private function getCustomData($pKey){
		if(isset($this->sessCustomData->$pKey)) return $this->sessCustomData->$pKey;
	}
	#public function setUserLoggedIn($pUserId){
	#	if(!empty($pUserId)) $this->tfwSess->userId=$pUserId;
	#}
	#public function getUserLoggedInId(){
	#	if($this->isUserLoggedIn()) return $this->tfwSess->userId;
	#}
	#public function setUserLogout(){
	#	if(isset($this->tfwSess->userId)) unset($this->tfwSess->userId);
	#	if(isset($this->tfwSess->toRedirectRoute)) unset($this->tfwSess->toRedirectRoute);
	#	if(isset($this->tfwSess->toRedirectParam)) unset($this->tfwSess->toRedirectParam);
	#}
	public function setLogoutUrl($pUrl) {
		$this->tfwSess->logoutUrl=$pUrl;
		return $this;
	}
	public function getLogoutUrl($pAlternate='/login/logout') {
		$rtrn=(!empty($this->tfwSess->logoutUrl)?$this->tfwSess->logoutUrl:(!empty($pAlternate)?$pAlternate:'/login/logout'));
		return $rtrn;
	}
	private function setLoginUrl($pUrl) {
		$this->tfwSess->loginUrl=$pUrl;
		return $this;
	}
	private function getLoginUrl($pAlternate='/login') {
		$rtrn=(!empty($this->tfwSess->loginUrl)?$this->tfwSess->loginUrl:(!empty($pAlternate)?$pAlternate:'/login'));
		return $rtrn;
	}
	private $afterLoginUrl;
	public function getAfterLoginUrl($pAlternate='/login/welcome'){
		if(empty($this->afterLoginUrl)){
			$this->afterLoginUrl=(!empty($this->tfwSess->afterLoginUrl)?$this->tfwSess->afterLoginUrl:(!empty($pAlternate)?$pAlternate:'/login/welcome'));
		}
		#if(!empty($this->tfwSess->afterLoginUrl)) unset($this->tfwSess->afterLoginUrl);
		return $this->afterLoginUrl;
	}
	public function setAfterLoginUrl($pUrl=NULL){
		#echo '<pre>'; print_r(debug_backtrace(null, 3));
		#echo('url: '.$pUrl.' @'.__LINE__.': '.__FILE__.'<br />');
		$this->afterLoginUrl=$this->tfwSess->afterLoginUrl=$pUrl;
		return $this;
	}
	#public function isUserLoggedIn(){
	#	return !empty($this->tfwSess->userId);
	#}
	private function makeThisActionRestricted($pController, $pSuccessRedirectUrlAuto=NULL, $pRedirectIfFail=true) {
		if($pController instanceof AbstractController){
			#die('$rqst->getRequestUri(): '.self::getUri($pController->getRequest()).' : '.get_class($rqst->getUri()));
			$myUrl=$pController->plugin('url');
			if($this->hasIdentity()){
				$logoutAlternetUrl=$myUrl->fromRoute('login', array(
						'action' => 'logout',
					), array('force_canonical' => true));
				return $this->getLogoutUrl($logoutAlternetUrl);
				#return 'we execute it!';
			}else{
				#die('we are at else part... @'.__LINE__.': '.__FILE__);
				if(!isset($pSuccessRedirectUrlAuto)) $pSuccessRedirectUrlAuto=true;
				if(true===$pSuccessRedirectUrlAuto) $successLoginUrl=self::getUri($pController->getRequest());
				elseif($pSuccessRedirectUrlAuto instanceof Zend\Mvc\Router\RouteMatch) $successLoginUrl=$myUrl->fromRoute($route->getMatchedRouteName(), $route->getParams(), array('force_canonical' => true));
				elseif(is_string($pSuccessRedirectUrlAuto) && !empty($pSuccessRedirectUrlAuto)) $successLoginUrl=$pSuccessRedirectUrlAuto;

				if(isset($successLoginUrl)) $this->setAfterLoginUrl($successLoginUrl);
				#die('$successLoginUrl: '.$successLoginUrl.' ::::: $pSuccessRedirectUrlAuto: '.$pSuccessRedirectUrlAuto);
				#$pController->redirect()->toRoute('login');
				$loginUrl=$myUrl->fromRoute('login', array(), array('force_canonical' => true));
				$this->setLoginUrl($loginUrl);
				if(true==$pRedirectIfFail){
					$evnt=$pController->getEvent();
					$evnt->stopPropagation(true);
					$response = $evnt->getResponse();
					$response->getHeaders()->addHeaderLine('Location', $loginUrl);
					$response->setStatusCode(403);
					$response->sendHeaders();
					exit;
				}else return false;
				// Skip executing the action requested.
				// Execute anotherController::errorAction() instead.
				/*$evnt->setResponse(new Response());
				$result = $pController->forward()->dispatch('Another', array(
						'action' => 'error'
					));
				$result->setTerminal(true);
				$evnt->setViewModel($result);*/
				#die('are we get control here? @'.__LINE__.': '.__FILE__);
				#return 'we are not logged in!';
			}
		}
		if($this->hasIdentity()) return $this->getLogoutUrl();
		return false;
	}
	private static function getUri(\Zend\Http\PhpEnvironment\Request $request){
		$uri=$request->getUri();
		#$uri->setPath(null);
		$qry=$uri->getQueryAsArray();
		$pth=$uri->getPath();
		#echo '<h5>$pth: '.$pth.' : '.substr($pth, (-1*strlen($qry['q']))).' : '.(-1*strlen($qry['q'])).' : '.$qry['q'].'</h5>';
		if(!empty($qry['q']) && $qry['q']==substr($pth, (-1*strlen($qry['q'])))) unset($qry['q']);
		$uri->setQuery($qry);
		#echo '<p>'.$uri->normalize().'</p>';
		return $uri->normalize();
	}
	public function setUrlHandler($pUrlHandler){
		$this->urlHandler=$pUrlHandler;
		$logoutUrl=$this->urlHandler->fromRoute('login', array(
				'action' => 'logout',
			), array('force_canonical' => true));
		$this->setLogoutUrl($logoutUrl);
		return $this;
	}
	private $userArrayCache;
	/**
	 * this method is access from stateless controller, to provide user information
	 * @param unknown $pLoginId
	 * @return mixed|mixed
	 */
	public function getUserArrayByLoginId($pLoginId){
		if(!isset($this->userArrayCache)) $this->userArrayCache=array();
		if(array_key_exists($pLoginId, $this->userArrayCache)) return $this->userArrayCache[$pLoginId];
		$rs=$this->tgUser->select(array(TfwAuthUser::TBL_FLD_LOGINTEXTID=>$pLoginId, TfwAuthUser::TBL_FLD_USER_STATUS=>TfwAuthUser::USER_STATUS_ACTIVE));
		if($rs->count()>0) $this->insertIntoUserArrayCache($rs->current()->getArrayCopy());
		else return array();
		return $this->getUserArrayByLoginId($pLoginId);
	}
	private function insertIntoUserArrayCache($d2s){
		if(!isset($this->userArrayCache)) $this->userArrayCache=array();
		$numericId=$d2s[TfwAuthUser::TBL_FLD_NUMERICID];
		$textId=$d2s[TfwAuthUser::TBL_FLD_LOGINTEXTID];
		if(array_key_exists($numericId, $this->userArrayCache)) unset($this->userArrayCache[$numericId]);
		if(array_key_exists($textId, $this->userArrayCache)) unset($this->userArrayCache[$textId]);
		$this->userArrayCache[$numericId]=$d2s;
		if(!array_key_exists($textId, $this->userArrayCache)) $this->userArrayCache[$textId]=$d2s;
		return $this;
	}
	protected function setLoggingInDirectly($pLoginId){
		$uArr=$this->getUserArrayByLoginId($pLoginId);
		if(!empty($uArr) && array_key_exists(TfwAuthUser::TBL_FLD_NUMERICID, $uArr)){
			$rmt=new RemoteAddress();
			$agnt=new HttpUserAgent();
			$this->lastCheckedResult=true;
			#$this->lastCheckedUserId=$pLoginId;
			#$this->lastCheckedPssMd5=md5($uArr[TfwAuthUser::TBL_FLD_PASSWORD]);
			$this->setLoggedInX($uArr[TfwAuthUser::TBL_FLD_NUMERICID], $rmt->getIpAddress(), $agnt->getData());
			return true;
		}
		return false;
	}
	public function setLoggedIn($pIpAddress, $pUserAgent){
		if(isset($this->lastCheckedResult) && true==$this->lastCheckedResult){
			$resultRow=$this->getAdapter()->getResultRowObject();
			$numericId=$resultRow->{TfwAuthUser::TBL_FLD_NUMERICID};
			$this->insertIntoUserArrayCache(json_decode(json_encode($resultRow), True));
			return $this->setLoggedInX($numericId, $pIpAddress, $pUserAgent);
		}
		throw new \Exception('not a valid user or password, unable to logged in!');
	}
	private function setLoggedInX($pId, $pIpAddress, $pUserAgent){
		if(!isset($this->lastCheckedResult) || false==$this->lastCheckedResult) throw new \Exception('not a valid user or password, unable to logged in!');
		$uArr=$this->getUserArrayByLoginId($pId);
		if(empty($uArr) || !array_key_exists(TfwAuthUser::TBL_FLD_LOGINTEXTID, $uArr)) throw new \Exception('unable to logged in!');
		$d2w=array(
							'id'			=> $pId,
							'username'		=> $uArr[TfwAuthUser::TBL_FLD_LOGINTEXTID],
							'ip_address'	=> $pIpAddress,
							'user_agent'	=> $pUserAgent,
					);
		#die('<pre>'.print_r($d2w, true));
		$this->getStorage()->write($d2w);
		return $pId;
	}
	#private $lastCheckedUserId;
	protected $lastCheckedPssMd5;
	private $lastCheckedResult;
	public function isValid($pLoginId=NULL, $pLoginPass=NULL){
		if(!empty($pLoginId) && !empty($pLoginPass)){
			#$this->lastCheckedUserId=$pLoginId;
			#$this->lastCheckedPssMd5=md5($pLoginPass);
			#die('id: '.$pLoginId.' | pass: '.$pLoginPass);
			$this->getAdapter()->setIdentity($pLoginId)->setCredential($pLoginPass);
			$this->lastCheckedResult=$this->authenticate()->isValid();
			#die('<pre>'.var_export($this->lastCheckedResult, true));
			return $this->lastCheckedResult;
		}
		return false;
	}
	#const secretCode4SOAP='l@jd0weImxcn8524';
	private function Security($pCredentialInfoObj){
		//if(empty($this->soapSecurityValues)) $this->soapSecurityValues=md5('%'.time().'%');
		#TfwEncryptDecrypt::setExternalCommunicationSecret($this->soapController->getSoapSecurityValue());
		#die('soap handler now enter into try...');
		$usrPass=TfwEncryptDecrypt::decrypt($pCredentialInfoObj->UsernameToken->Password);
		$isValid=$this->isValid($pCredentialInfoObj->UsernameToken->Username, $usrPass);
		if($isValid){
			$n2x[]=$loggedInID=$this->setLoggedIn($this->soapController->getRemoteAddress(), $this->soapController->getHttpUserAgent());
			$n2x[]=TfwEncryptDecrypt::makeSHA256Hash('yes');
			$n2x[]=$this->soapController->getRemoteAddress();
			$n2x[]=$this->soapController->getHttpUserAgent();
			$n2x[]=$rndVal=mt_rand(1, time());
			#$n2x[]=$scrtKey=$loggedInID.$pCredentialInfoObj->UsernameToken->Username.md5($usrPass).$rndVal;
			$rtrn=implode(':', $n2x);
			$this->tgUser->update(array('apiSessionKey'=>$rndVal), array('id'=>$loggedInID));
		}else $rtrn='0:'.TfwEncryptDecrypt::makeSHA256Hash('no').':'.$this->soapController->getRemoteAddress();
		return TfwEncryptDecrypt::encrypt($rtrn, true); # $rtrn.' :: '.$this->soapController->getSoapSecurityValue(); #TfwEncryptDecrypt::encrypt($rtrn);
	}
	private $soapController;
	public function setSoapController(TfwSoapRequestController $pSoapController){
		$this->soapController=$pSoapController;
		return $this;
	}
	/*protected $soapSecurityValues;
	public function setSoapSecurityValues($pVal){
		$this->soapSecurityValues=$pVal;
	}
	public function setRequestInfo($pRemoteAddress, $pHttpUserAgent, $pLogoutUrl){
		$this->authService->setLoggedIn($request->getServer('REMOTE_ADDR'), $request->getServer('HTTP_USER_AGENT'), $logouturl);
	}*/
}

