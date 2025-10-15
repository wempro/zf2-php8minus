<?php
namespace AuthV2\Api;


use Lib3rdParty\Zend\Authentication\AuthenticationService as ZendAuthAuthenticationService;
use Zend\Authentication\Result;
use Lib3rdParty\Authentication\AuthenticatedUserObjectInterface;
use Lib3rdParty\Zend\Authentication\Adapter\DbTable\DbTableHelperAdapterInterface;
use AuthV2\Model\UserX;
use Lib3rdParty\Zend\Authentication\Adapter\DbTable\CredentialTreatmentAdapter;
use Zend\Db\Sql\Where;

class AuthenticationService extends ZendAuthAuthenticationService {
//	protected function getSystemPrefix(){
//            if(isset(self::$oacsSysPrefix)) return self::$oacsSysPrefix;
//            return 'wvm'; }
//        private static $oacsSysPrefix;
//    public static function setSystemPrefix($pSysPrefix){
//        self::$oacsSysPrefix=strval($pSysPrefix);
//    }
	/**
	 * recommended to inherit
	 * 
	 * @return DbTableHelperAdapterInterface
	protected function getValidatableAuthAdapter(){
		if(!isset($this->authDbAdapter)){
			$oacsUserTable=$this->getUserTable();
			#$this->authDbAdapter=new CallbackCheckAdapter($this->getUserDbAdapter(), $oacsUserTable->getMyTableName(), $oacsUserTable->getIdentityColumn(), $oacsUserTable->getCredentialColumn(), $this->getCredentialValidationCallback());
			$this->authDbAdapter = new CredentialTreatmentAdapter($this->getUserDbAdapter(), $oacsUserTable->getMyTableName(), $oacsUserTable->getIdentityColumn(), $oacsUserTable->getCredentialColumn(), UserX::PASSWORD_CRYPT_FUNC.'(?)');
			$whr=new Where();
			$whr->equalTo(UserX::FLD_USER_STATUS, UserX::USER_STATUS_ACTIVE);
			$this->authDbAdapter->setAdditionalCondition($whr);
			#die('getValidatableAuthAdapter OK @'.__LINE__.': '.__FILE__);
		}
		return $this->authDbAdapter;
	}
	 */
	/**
	 * {@inheritDoc}
	 * @see \Lib3rdParty\Zend\Authentication\AuthenticationService::getUserToLogin()
	 */
	#protected function getUserToLogin($pUserTextId){ return $this->getUserTable()->getUserToLogin($pUserTextId); }
//	protected function getIdentityArrayToStore($pIdentity, AuthenticatedUserObjectInterface $pUserOrgiObj, $pIpAddress, $pUserAgent, $pUserArrayObject=null){
//		/** @var \stdClass $pUserArrayObject **/
//		/** @var \WvmDb\Registration\User\User $pUserOrgiObj **/
//		return array(
//				'id'			=> (!is_null($pUserArrayObject)?$pUserArrayObject->id:$pIdentity),
//				'username'		=> $pIdentity,
//				'useremail'		=> $pUserOrgiObj->getEmailAddress(),
//				'fullname'		=> $pUserOrgiObj->getName(),
//				'ip_address'		=> $pIpAddress,
//				'user_agent'		=> $pUserAgent,
//			);
//	}
}

