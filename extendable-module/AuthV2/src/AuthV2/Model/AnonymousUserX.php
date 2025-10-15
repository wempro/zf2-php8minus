<?php

namespace AuthV2\Model;


use TFW\Misc\TfwEncryptDecrypt;
class AnonymousUserX extends User{
	private $anonymousFirstName;
	private $anonymousLastName;
	private $anonymousFullName;
	private $anonymousEmail;
	const AnonymousUserId=-1;
	const AnonymousLeadQueryString=';';
	function __construct($pFirstName, $pLastName=null, $pEmail, $pSessionId){
		$this->id=self::AnonymousUserId;
		
		$this->anonymousFirstName=trim($pFirstName);
		if(!is_null($pLastName)) $this->anonymousLastName=trim($pLastName);
		$this->anonymousFullName=$fName;
		$this->anonymousEmail=$pEmail;
		$this->refNoForEmailComm=$pSessionId;
	}
	public function getSessionIdForAnonymousComm(){ return $this->refNoForEmailComm; }
	public function getFirstName(){ return $this->anonymousFirstName; }
	public function getLastName(){
		if(!isset($this->anonymousLastName)) return '';
		return $this->anonymousLastName;
	}
	public function fullName(){
		if(!isset($this->anonymousFullName)){
			$fName=trim($this->getFirstName().' '.$this->getLastName());
			if(!empty($fName)) $this->anonymousFullName=$fName;
		}
		return (!empty($this->anonymousFullName)?$this->anonymousFullName:'Anonymous');
	}
	public function getEmailAddress(){ return $this->anonymousEmail; }
	public function isAnonymous(){ return true; }
	public function getArrayCopy(){
		$arrCopy=parent::getArrayCopy();
		$arrCopy['commEmailAddress']=$this->getEmailAddress();
		return $arrCopy;
	}
	public function getUserSecretID(){
		return self::getAnonymouseSecretID($this->anonymousEmail, $this->refNoForEmailComm);
	}
	public function getAnonymousSecretID($pEmail, $pSessionId){
		$data2encrypt=$pSessionId.':'.$pEmail;
		return TfwEncryptDecrypt::encrypt($data2encrypt, false);
	}
	public function getAnonymousSecretID2chat($pEmail, $pSessionId){
		return self::AnonymousLeadQueryString.self::getAnonymouseSecretID($pEmail, $pSessionId);
	}
	public static function getUserIdFromSecretID($pSecretData){
		$dcrptd=TfwEncryptDecrypt::decrypt($pSecretData, false);
		$data=explode(':', $dcrptd, 2);
		return $data[0];
	}
}
