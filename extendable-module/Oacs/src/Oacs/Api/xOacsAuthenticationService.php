<?php
namespace Oacs\Api;
use Oacs\Exception\UnAuthenticated;
use Oacs\Model\OacsUserTable;
use Zend\Authentication\Adapter\DbTable\CredentialTreatmentAdapter;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Session\Container;
class xOacsAuthenticationService extends AuthenticationService implements \Lib3rdParty\Zend\Authentication\AuthenticationServiceInterface {
	private $dbAdapter;
	private $authDbAdapter;
	private $oacsSess;
	private $sessCustomData;
	/**
	 * @var OacsUserTable
	 */
	private $tblOacsUser;
	public function __construct(StorageInterface $storage, Adapter $adapter, OacsUserTable $pOacsUserTable) {
		$this->dbAdapter=$adapter;
		$this->tblOacsUser=$pOacsUserTable;
		$this->authDbAdapter = new CredentialTreatmentAdapter($this->dbAdapter, 'OacsUser', 'textId', 'oacsPass');
		#die('@'.__LINE__.': '.__FILE__.' - '.$storage->get)
		parent::__construct($storage, $this->authDbAdapter);
		$this->oacsSess=new Container('tfwUserSession');
		$this->sessCustomData=new Container('tfwSessionCustomData');
	}
	public function getUserDbAdapter(){ return $this->dbAdapter; }
	public function isPreflightCorsRequested(){ return false; }
	public function isIdentityExists($pIdentity){ return $this->getAdapter()->isIdentityExists($pIdentity); }
	private $tblUser;
	/**
	 * @return \Lib3rdParty\Authentication\TableInterface
	 */
	public function getUserTable(){ return $this->tblOacsUser; }
	private $lastOacsUser;
	private $lastCheckedUserId;
	private $lastCheckedPssMd5;
	private $lastCheckedResult;
	public function isValid($pLoginId=NULL, $pLoginPass=NULL){
		if(!empty($pLoginId) && !empty($pLoginPass)){
			$this->lastOacsUser=$this->getUserToLogin($pLoginId);
			$fLoginPass=$this->lastOacsUser->getEncryptedPassword($pLoginPass);
			#die('$fLoginPass: '.$fLoginPass.' | $pLoginPass: '.$pLoginPass.' @'.__LINE__.': '.__FILE__);
			$this->lastCheckedUserId=$pLoginId;
			$this->lastCheckedPssMd5=md5($fLoginPass);
			$this->getAdapter()->setIdentity($pLoginId)->setCredential($fLoginPass);
			$result = $this->authenticate();
			$this->lastCheckedResult=$result->isValid();
			#die('<pre>'.var_export($this->lastCheckedResult, true));
			return $this->lastCheckedResult;
		}
		return false;
	}
	public function setLoggedIn($pIpAddress, $pUserAgent){
		if(isset($this->lastCheckedResult) && $this->lastCheckedResult && isset($this->lastOacsUser)){
			$resultRow = $this->getAdapter()->getResultRowObject();
			$wrStatus=$this->getStorage()->write(
					array(
							'id'			=> $resultRow->id,
							'username'		=> $this->lastCheckedUserId,
							'useremail'		=> $this->lastOacsUser->email,
							'fullname'		=> $this->lastOacsUser->getName(),
							'ip_address'	=> $pIpAddress,
							'user_agent'	=> $pUserAgent,
					)
			);
			#die('<pre>'.print_r(array($wrStatus, $resultRow), true));
			return $resultRow->id;
		}
		return 0;
	}
	private $loggedInOacsUser;
	public function getLoggedInUser(){
		if($this->hasIdentity()){
			if(!isset($this->loggedInOacsUser)){
				$identity=$this->getIdentity();
				$this->loggedInOacsUser=$this->getUserToLogin($identity['username']);
			}
			return $this->loggedInOacsUser;
		}
		throw new UnAuthenticated('not logged in');
	}
	private $userToLoginCache;
	private function getUserToLogin($pUserTextId){
		if(!isset($this->userToLoginCache)) $this->userToLoginCache=array();
		if(!array_key_exists($pUserTextId, $this->userToLoginCache)) $this->userToLoginCache[$pUserTextId]=$this->tblOacsUser->getUserToLogin($pUserTextId);
		return $this->userToLoginCache[$pUserTextId];
	}
	public function getSessionId(){
		return $this->oacsSess->getManager()->getId();
	}
	public function clearIdentity(){
		parent::clearIdentity();
		$this->oacsSess->getManager()->regenerateId();
	}
	public function setCustomData($pKey, $pVal){
		$this->sessCustomData->$pKey=$pVal;
		return $this;
	}
	public function getCustomData($pKey){
		if(isset($this->sessCustomData->$pKey)) return $this->sessCustomData->$pKey;
	}
	public function setProfileUrl($pUrl) {
		$this->oacsSess->offsetSet('profileUrl', $pUrl);
		return $this;
	}
	public function getProfileUrl($pAlternate='login/welcome'){ return ($this->oacsSess->offsetExists('logoutUrl')?$this->oacsSess->offsetGet('profileUrl'):(!empty($pAlternate)?$pAlternate:'login/welcome')); }
	public function setLogoutUrl($pUrl) {
		$this->oacsSess->logoutUrl=$pUrl;
		return $this;
	}
	public function getLogoutUrl($pAlternate='login/logout') {
		$rtrn=($this->oacsSess->offsetExists('logoutUrl')?$this->oacsSess->logoutUrl:(!empty($pAlternate)?$pAlternate:'login/logout'));
		return $rtrn;
	}
	public function setLoginUrl($pUrl) {
		$this->oacsSess->loginUrl=$pUrl;
		return $this;
	}
	public function getLoginUrl($pAlternate='login') {
		$rtrn=($this->oacsSess->offsetExists('loginUrl')?$this->oacsSess->loginUrl:(!empty($pAlternate)?$pAlternate:'login'));
		return $rtrn;
	}
	private $afterLoginUrl;
	public function getAfterLoginUrl($pAlternate='login/welcome'){
		if(empty($this->afterLoginUrl)){
			$this->afterLoginUrl=($this->oacsSess->offsetExists('afterLoginUrl')?$this->oacsSess->afterLoginUrl:(!empty($pAlternate)?$pAlternate:'login/welcome'));
		}
		if($this->oacsSess->offsetExists('afterLoginUrl')) $this->oacsSess->offsetUnset('afterLoginUrl');
		return $this->afterLoginUrl;
	}
	public function setAfterLoginUrl($pUrl){
		$this->afterLoginUrl=$this->oacsSess->afterLoginUrl=$pUrl;
		return $this;
	}
	private $postLoginUrl;
	public function isPostLoginUrlExists(){
		if(!empty($this->postLoginUrl)) return true;
		return $this->oacsSess->offsetExists('postLoginUrl');
	}
	public function getPostLoginUrl(){
		if(empty($this->postLoginUrl)) $this->postLoginUrl=($this->oacsSess->offsetExists('postLoginUrl')?$this->oacsSess->offsetGet('postLoginUrl'):'');
		return $this->postLoginUrl;
	}
	/**
	 * recommended to set while service create
	 * @param string $pUrl
	 */
	public function setPostLoginUrl($pUrl){
		if(isset($this->postLoginUrl)) unset($this->postLoginUrl);
		if($this->oacsSess->offsetExists('postLoginUrl')) $this->oacsSess->offsetUnset('postLoginUrl');
		if(empty($pUrl)) return $this;
		$this->oacsSess->offsetSet('postLoginUrl', strval($pUrl));
		$this->postLoginUrl=$this->oacsSess->offsetGet('postLoginUrl');
		return $this;
	}
	public static function getUri(\Zend\Http\PhpEnvironment\Request $request){
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
}


