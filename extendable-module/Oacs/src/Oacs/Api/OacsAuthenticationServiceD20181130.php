<?php
namespace Oacs\Api;
use Lib3rdParty\Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Lib3rdParty\Authentication\AuthenticatedUserObjectInterface;
class D20181130OacsAuthenticationService extends AuthenticationService {
    protected function getCredentialValidationCallback(){
        #die('??? @'.__LINE__.': '.__FILE__);
        $crdntialColumnName=$this->getUserTable()->getCredentialColumn();
        #die('@'.__LINE__.'; '.__FILE__.' - '.$crdntialColumnName);
		return function (array $pUserData, $pCredential) use ($crdntialColumnName) {
                #die('oacsPass: '.print_r($pUserData, true));
                $bcrpt=new \Zend\Crypt\Password\Bcrypt();
                return $bcrpt->verify($pCredential, $pUserData[$crdntialColumnName]);
			};
	}
	private $loggedInUserDataClosure;
	public function setLoggedInUserDataClosure(\Closure $pClosure){
		$this->loggedInUserDataClosure=$pClosure;
		return $this;
	}
	#private function isLoggedInUserDataClosureExists(){ return isset($this->loggedInUserDataClosure); }
	private function getLoggedInUserDataClosure(){
		if(!isset($this->loggedInUserDataClosure)) return function(AuthenticatedUserObjectInterface $pUserOrgiObj){
			return array();
		};
		return $this->loggedInUserDataClosure;
	}
	protected function getIdentityArrayToStore($pIdentity, AuthenticatedUserObjectInterface $pUserOrgiObj, $pIpAddress, $pUserAgent, $pUserArrayObject=null){
		/** @var \Oacs\Model\OacsUser $pUserOrgiObj **/
		/** $pUserArrayObject is the standard class object of $pUserOrgiObj **/
		$lggdInUsrDataClsr=$this->getLoggedInUserDataClosure();
		return array(
			'id'			=> (!is_null($pUserArrayObject)?$pUserArrayObject->id:$pIdentity),
			'textId'		=> $pUserOrgiObj->getTextId(),
			'useremail'		=> $pUserOrgiObj->getEmailAddress(),
			'fullname'		=> $pUserOrgiObj->getName(),
			'ip_address'	=> $pIpAddress,
			'user_agent'	=> $pUserAgent,
			'data'			=> $lggdInUsrDataClsr($pUserOrgiObj),
		);
	}
//     protected function getIdentityDataToSave(Result $pAuthResult, $pUserArrayObject, AuthenticatedUserObjectInterface $pUserOrgiObj, $pIpAddress, $pUserAgent){
// 		$lggdInUsrDataClsr=$this->getLoggedInUserDataClosure();
//         return array(
//             'id'			=> $pUserArrayObject->id,
//             'textId'		=> $pAuthResult->getIdentity(),
//             'useremail'		=> $pUserOrgiObj->getEmailAddress(),
//             'fullname'		=> $pUserOrgiObj->getName(),
//             'ip_address'	=> $pIpAddress,
//             'user_agent'	=> $pUserAgent,
// 			'data'			=> $lggdInUsrDataClsr($pUserOrgiObj),
//         );
//     }
//     private $userToLoginCache;
//     /**
// 	 * @return \Lib3rdParty\Authentication\AuthenticatedUserObjectInterface
// 	 * @param string|int $pUserTextId
// 	 */
// 	protected function getUserToLogin($pUserTextId){
// 		if(!isset($this->userToLoginCache)) $this->userToLoginCache=array();
// 		if(!array_key_exists($pUserTextId, $this->userToLoginCache)) $this->userToLoginCache[$pUserTextId]=$this->getUserTable()->getUserToLogin($pUserTextId);
// 		return $this->userToLoginCache[$pUserTextId];
// 	}
    protected function getSystemPrefix(){
        if(!isset(self::$oacsSysPrefix)) return 'oacsAuth';
        return self::$oacsSysPrefix;
    }
    private static $oacsSysPrefix;
    public static function setSystemPrefix($pSysPrefix){
        self::$oacsSysPrefix=strval($pSysPrefix);
    }
}

