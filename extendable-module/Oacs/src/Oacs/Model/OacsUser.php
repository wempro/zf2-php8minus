<?php
namespace Oacs\Model;


use Lib3rdParty\Helper\Db\AbstractTableData;

class OacsUser extends AbstractTableData implements \Lib3rdParty\Authentication\AuthenticatedAcsEnabledUserInterface{
	const FLD_PRIMARY='textId';
	const US_ACTIVE='activeUser';
	const US_INACTIVE='inactiveUser';
	const US_BANNED='bannedUser';
	const US_DELETED='deletedUser';
	const UT_SU='su'; # super user [top level user of a company]
	const UT_SM='sm'; # super manager [who maintain all companies user and password]
	const UT_MANAGER='mgr';
	const UT_GENERAL='general';
	public $id;
	public $textId;
	public $email;
	public $oacsPass;
	public $fName;
	public $mName;
	public $lName;
	public $desg;
	public $oacsUserStatus;
	public $oacsUserType;
	public $companyTextId;
	public $created;
	public $updated;
	public function getCredential(){ return $this->oacsPass; }
	public function getCompanyTextId(){ return $this->companyTextId; }
	public static function getMyName() { return 'OacsUser'; }
	private static function getAvailableStatus(){
		return array(
				self::US_ACTIVE=>'Active',
				self::US_INACTIVE=>'Inactive',
				self::US_BANNED=>'Banned',
				self::US_DELETED=>'Deleted',
			);
	}
	public static function getAvailableStatusToModify(){
		$s2r=self::getAvailableStatus();
		unset($s2r[self::US_DELETED]);
		return $s2r;
	}
	public static function getAvailableTypeToModify(){
		return array(
				self::UT_MANAGER=>'Manager',
				self::UT_GENERAL=>'User',
			);
	}

	public function setDelete(){
		$this->oacsUserStatus=self::US_DELETED;
		$this->updated=self::getMysqlFormatedDateTime();
		return $this;
	}
	private $curUser;
	public function setAsCurrentUser(){
		$this->curUser=true;
		return $this;
	}
	public function isCurrentUser(){
		if(!isset($this->curUser)) return false;
		return $this->curUser;
	}
	public function setPassword($pPlainPass){
		$this->oacsPass=$this->getEncryptedPassword($pPlainPass);
		return $this;
	}
	public function isPasswordMatched($pPassToCheck){
		$bcrpt=new \Zend\Crypt\Password\Bcrypt();
        return $bcrpt->verify($pPassToCheck, $this->oacsPass);
		die('@'.__LINE__.': '.__FILE__.' | '.$this->getEncryptedPassword($pPassToCheck).'=='.$this->oacsPass);
		return ($this->getEncryptedPassword($pPassToCheck)===$this->oacsPass); }
	public function getEncryptedPassword($pPlainPass){
		if(empty($pPlainPass)) throw new \Exception('you must provide plain password for encrypted password!!!'); # $pPlainPass=$this->getPassword();
		$bcrpt=new \Zend\Crypt\Password\Bcrypt();
		return $bcrpt->create($pPlainPass);
		#return self::getEncryptedText($pPlainPass, $this->getPassEncryptKey());
	}
	private static function getEncryptedText($pPlainPass, $pKey){
		$filter = new \Zend\Filter\Encrypt();
		$filter->setKey($pKey);
		$filter->setVector('12345678901234567890');
		return $filter->filter($pPlainPass);
	}
	private function getPassEncryptKey(){
		if(empty($this->companyTextId) || strlen(trim($this->companyTextId))<=0) throw new \Exception('company text id not found, unable to encrypt data...');
		if(empty($this->oacsUserStatus) || empty($this->textId)) throw new \Exception('please set user status and text id before setting password!');
		return self::getEncryptKey($this->companyTextId, $this->textId, $this->oacsUserStatus);
	}
	private static function getEncryptKey($pCompanyTextId, $pUserTextId, $pUserStatus){ return $pCompanyTextId.':'.$pUserStatus.':'.$pUserTextId; }
	private $dycrptdPasswordCache;
	public function getPassword(){
		die('please do not ask me for password @'.__LINE__);
		throw new \Exception('plain password not provided anymore!!!');
		if(!isset($this->dycrptdPasswordCache)){
			if(empty($this->oacsPass)) throw new \Exception('no password was set!');
			$this->dycrptdPasswordCache='';
			try{
				$this->dycrptdPasswordCache=self::getDecryptedText($this->oacsPass, $this->getPassEncryptKey());
			}catch(\Exception $e){
				
			}
			if(strlen(trim($this->dycrptdPasswordCache))<=0) $this->dycrptdPasswordCache=\Zend\Math\Rand::getString(12);
		}
		return $this->dycrptdPasswordCache;
	}
	private static function getDecryptedText($pEncryptedText, $pKey){
		$filter = new \Zend\Filter\Decrypt();
		$filter->setKey($pKey);
		## Note that even if we did not specify the same Vector, the BlockCipher is able to decrypt the message because the Vector is stored in the encryption string itself (note that the Vector can be stored in plaintext, it is not a secret, the Vector is only used to improve the randomness of the encryption algorithm).
		#$filter->setVector('12345678901234567890');
		return $filter->filter($pEncryptedText);
	}
	public function getDeleteCode(){ return md5($this->getName().$this->id.$this->oacsUserStatus.$this->oacsUserType); }
	protected function init(){
		$this->created=self::getMysqlFormatedDateTime();
		return parent::init();
	}
	protected function getReadOnlyFields(){
		return array('name'=>array('ignoreAtDatabase'=>true, 'fn'=>'getName'));
	}


	public function getTextId(){ return $this->textId; }
	public function getEmailAddress(){ return $this->email; }
	private $fullName;
        public function getFirstName(){ return $this->fName.((isset($this->mName) && strlen(trim($this->mName))>0)?' '.trim($this->mName):''); }
        public function getLastName(){ return $this->lName; }
	public function getName(){
		if(!isset($this->fullName)){
			$nm=array();
			if(isset($this->fName) && strlen(trim($this->fName))>0) $nm[]=trim($this->fName);
			if(isset($this->mName) && strlen(trim($this->mName))>0) $nm[]=trim($this->mName);
			if(isset($this->lName) && strlen(trim($this->lName))>0) $nm[]=trim($this->lName);
			$this->fullName=(!empty($nm)?implode(' ', $nm):(!empty($this->textId)?$this->textId.' ['.$this->email.']':'** Unknown **'));
		}
		return $this->fullName;
	}
	public function isActive() { return (self::US_ACTIVE==$this->oacsUserStatus); }
	public function notActive() { return (self::US_ACTIVE!=$this->oacsUserStatus); }
	public function isSuperUser() {
		if($this->notActive()) return false;
		return ($this->isSuperManager() || self::UT_SU==$this->oacsUserType);
	}
	public function isSuperManager() {
		if($this->notActive()) return false;
		return (self::UT_SM==$this->oacsUserType);
	}
	public function isManager() {
		if($this->notActive()) return false;
		return ($this->isSuperUser() || self::UT_MANAGER==$this->oacsUserType);
	}

    public function getDesignationTextId(){
        return '';
    }

}

