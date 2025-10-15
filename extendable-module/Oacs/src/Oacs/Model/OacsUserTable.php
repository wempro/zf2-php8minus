<?php
namespace Oacs\Model;


use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Where;
use Lib3rdParty\Helper\Db\Table;
class OacsUserTable extends Table{
	public static function getMyTableName(){ return OacsUser::getMyName(); }
	public function getCredentialColumn(){ return 'oacsPass'; }
	protected function getIdFieldName(){ return 'textId'; }
	private $companyTextId;
	public function setCompanyTextId($pCompanyTextId, OacsUser $pSuperManager=null){
		$this->companyTextId=$pCompanyTextId;
		if(isset($pSuperManager) && $pSuperManager->isSuperUser()){
			/**
			 * note: super user/manager is the user of another virtual company who maintain all other companies secret information
			 * company text id may be different than logged in super user/manager
			 */
			$this->setLoggedInUserX($pSuperManager);
		}
		return $this;
	}
	private function setLoggedInUserX(OacsUser $pUser){
		if($pUser->notActive()) throw new \Exception('only active user allowed to logged in');
		$this->loggedInOacsUser=$pUser;
		$this->loggedInOacsUser->setAsCurrentUser();
		return $this;
	}
	private function getCompanyTextId(){
		if(!$this->isCompanyTextIdSet()) $this->companyTextId=$this->getLoggedInManager()->companyTextId;
		return $this->companyTextId;
	}
        private function isCompanyTextIdSet(){
            return isset($this->companyTextId);
        }
	/**
	 * @return OacsUser
	 * @param string $pUserTextId
	 * @throws \Exception
	 */
	private function getUserForAnyCompany($pUserTextId){
		$rs=$this->getTableGateway()->select(array('textId'=>$pUserTextId));
		if($rs->count()>0) return $rs->current();
		throw new \Exception('NO User Found');
	}
	public function createNewUser($pUserTextId, $pUserEmailAddres, $pUserPassword){
		if(!$this->isSetLoggedInManager()) throw new \Exception('NO manager logged in to proceed!');
		try{
			$existing=$this->getUserForAnyCompany($pUserTextId);
		}catch(\Exception $e){ }
		if(isset($existing) && $existing instanceof OacsUser) throw new \Exception('User Exists for "'.$existing->companyTextId.'" and email address is '.$existing->email);
		$user=new OacsUser($this->getAdapter());
		$user->companyTextId=$this->getCompanyTextId();
		$user->created=self::getMysqlFormatedDateTime();
		$user->email=$pUserEmailAddres;
		$user->textId=$pUserTextId;
		$user->oacsUserStatus=OacsUser::US_ACTIVE;
		$user->oacsUserType=OacsUser::UT_GENERAL;
		$user->fName=$pUserEmailAddres;
		$user->oacsPass=$user->getEncryptedPassword($pUserPassword);
		$this->create($user);
		return $user;
	}
	public function setUserPassword($pUserTextId, $pUserPassword, $pCompanyTextId=null){
		if(is_null($pCompanyTextId)) $pCompanyTextId=$this->getCompanyTextId();
		$user=$this->getById($pUserTextId);
		$user->oacsPass=$user->getEncryptedPassword($pUserPassword);
		$user->commit();
		return $user;
	}
	/**
	 * @var OacsUser
	 */
	private $loggedInOacsUser;
	public function setLoggedInUser(OacsUser $pUser){
		$this->setLoggedInUserX($pUser);
		if($this->isSetLoggedInManager()) $this->setCompanyTextId($this->getLoggedInManager()->companyTextId);
		return $this;
	}
	private function getLoggedInUser(){
		if(!$this->isSetLoggedInUser()) throw new \Exception('user NOT logged in, unable to proceed!');
		return $this->loggedInOacsUser;
	}
	
	public function isSetLoggedInManager(){
		if(!$this->isSetLoggedInUser()) return false;
		#if($this->loggedInOacsUser->isSuperManager()) return true;
		return $this->loggedInOacsUser->isManager();
	}
	public function getLoggedInManager(){
		if(!$this->isSetLoggedInManager()) throw new \Exception('NO manager logged in to proceed!');
		return $this->loggedInOacsUser;
	}
	public function getNumberOfManagableUser() {
		if(!$this->isSetLoggedInManager()) return 0;
		return $this->getNumberOfRow($this->getWhereToSelectOtherUser());
	}
	private function getWhereToSelectOtherUser(){
		$whr=new Where();
		$whr->equalTo('companyTextId', $this->getCompanyTextId());
		$whr->notEqualTo('oacsUserStatus', OacsUser::US_DELETED);
		if(!$this->getLoggedInUser()->isSuperManager()) $whr->notEqualTo('oacsUserType', OacsUser::UT_SU);
		$whr->notEqualTo('oacsUserType', OacsUser::UT_SM);
		if($this->isSetLoggedInUser()) $whr->notEqualTo('textId', $this->loggedInOacsUser->textId);
		return $whr;
	}
	public function getManagableUsers($pCurPgNo=1, $pRecPerPage=25) {
		if(!$this->isSetLoggedInManager()) return $this->getTableGateway()->getResultSetPrototype();
		return $this->getTableGateway()->selectByConditionWithPaging($this->getWhereToSelectOtherUser(), null, $pCurPgNo, $pRecPerPage);
	}
        public function isSetLoggedInUser(){ return isset($this->loggedInOacsUser); }
	public function getById($pId=null) {
		if(!$this->isSetLoggedInUser()) return new OacsUser($this->getTableGateway()->getAdapter());
		if(!$this->loggedInOacsUser->isManager() || is_null($pId) || 'NULL'===$pId) return $this->loggedInOacsUser;
		$whr=$this->getWhereToSelectOtherUser();
		$whr->equalTo('textId', $pId);
		$rs=$this->getTableGateway()->select($whr);
		if($rs->count()>0) return $rs->current();
		throw new \Exception('User NOT Found');
	}
//        private $usrInfoCounter;
        private $allowAnyCompanyUser;
        public function allowAnyCompanyUser(){
            $this->allowAnyCompanyUser=true;
            return $this;
        }
        private function isAnyCompanyUserAllowed(){
            if(!isset($this->allowAnyCompanyUser)) return false;
            return $this->allowAnyCompanyUser;
        }
	/**
	 * @param string $pTextId
	 * @param string $pCompanyTextId
	 * @return OacsUser
	 * @throws \Exception
	 */
	public function getUserToLogin($pTextId, $pCompanyTextId=null) {
		#echo '$pTextId: '.$pTextId.' | $pCompanyTextId: '.$pCompanyTextId.' @'.__LINE__.': '.__FILE__.'<br />';
//            $this->usrInfoCounter[]=$pTextId;
//            if(count($this->usrInfoCounter)>5){
//                $bkTrace=function ($stack) {
// $output = '';

// $stackLen = count($stack);
// for ($i = 1; $i < $stackLen; $i++) {
// $entry = $stack[$i];

// $func = (array_key_exists('class', $entry)?$entry['class'].'\\':'').$entry['function'] . '(';
// $argsLen = count($entry['args']);
// for ($j = 0; $j < $argsLen; $j++) {
// $my_entry = $entry['args'][$j];
// if (is_string($my_entry)) {
// $func .= $my_entry;
// }
// if ($j < $argsLen - 1) $func .= ', ';
// }
// $func .= ')';

// $entry_file = 'NO_FILE';
// if (array_key_exists('file', $entry)) {
// $entry_file = $entry['file'];
// }
// $entry_line = 'NO_LINE';
// if (array_key_exists('line', $entry)) {
// $entry_line = $entry['line'];
// }
// $output .= $entry_file . ':' . $entry_line . ' - ' . $func . PHP_EOL;
// }
// return $output;
// };
// echo '<pre>'.$bkTrace(debug_backtrace()).'</pre>';
//                die('call more than once!!! @'.__LINE__.': '.__FILE__.'<pre>'.print_r($this->usrInfoCounter, true));
//            }
                if(true==$this->isAnyCompanyUserAllowed()) $pCompanyTextId=null;
                elseif(empty($pCompanyTextId)) $pCompanyTextId=$this->getCompanyTextId();
		#die('$pCompanyTextId: '.$pCompanyTextId.' @'.__LINE__.': '.__FILE__);
		$whr=new Where();
		$whr->equalTo('textId', $pTextId);
		if(!empty($pCompanyTextId)) $whr->equalTo('companyTextId', $pCompanyTextId);
		$whr->equalTo('oacsUserStatus', OacsUser::US_ACTIVE);
		$rs=$this->getTableGateway()->select($whr);
		if($rs->count()>0) return $rs->current();
		throw new \Exception('User NOT Found ['.$pCompanyTextId.']');
	}
}

