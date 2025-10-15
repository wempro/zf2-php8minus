<?php
namespace Oacs\Controller;

use Oacs\Form\OacsUserForm;
use Oacs\Model\OacsUser;
use Lib3rdParty\Helper\Zf2Controller\Zf2AbstractActionController;
use Oacs\Model\OacsUserTable;
use Oacs\Api\OacsAuthenticationService;
use Oacs\Form\LoginForm;
use Zend\Form\Element\Hidden;
class OacsController extends Zf2AbstractActionController{
	private $companyTextId;
	private function getCompanyTextId(){
		if(!isset($this->companyTextId)) $this->companyTextId=$this->getServiceLocator()->get('OacsCompanyTextId');
		return $this->companyTextId;
	}
	/**
	 * @var OacsUserTable
	 */
	private $oacsUserTable;
	/**
	 * @return OacsUserTable
	 * @throws \Exception
	 */
	private function getOacsUserTable(){
		if($this->isNotLoggedIn()) throw new \Exception('Not Logged In');
		if(!isset($this->oacsUserTable)){
			$this->oacsUserTable=$this->getServiceLocator()->get('Oacs\Model\OacsUserTable');
			$loggedInUserArray=$this->getAuthService()->getIdentity();
			#die('<pre>'.var_export($loggedInUserArray, true));
			$this->oacsUserTable->setLoggedInUser($this->oacsUserTable->getUserToLogin($loggedInUserArray['textId'], $this->getCompanyTextId()));
		}
		return $this->oacsUserTable;
	}
//        private $closureForReadUrl;
//        private function getDefaultReadUrl($pUserTextId=null){
//            if(!isset($this->closureForReadUrl)) $this->closureForReadUrl=function($pUrl, $pTextId=null){
//                                $params=array('action'=>'read');
//                                if(!is_null($pTextId)) $params['param1']=$pTextId;
//                                return $pUrl('oacs', $params);
//                            };
//            $clsr=$this->closureForReadUrl;
//            return $clsr($this->getServiceLocator()->get('ViewHelperManager')->get('url'), $pUserTextId);
//        }
        private $readUrlClosure;
        private function getReadUrl($pUserTextId=null){
            if(!isset($this->readUrlClosure)) $this->readUrlClosure=$this->getServiceLocator()->get('Oacs\\ReadUrlClosure');
            $clsr=$this->readUrlClosure;
            return $clsr($this->getServiceLocator()->get('ViewHelperManager')->get('url'), $pUserTextId);
        }
        private function redirect2read($pUserTextId=null){
            $readUrl=$this->getReadUrl($pUserTextId);
            if(!empty($readUrl)) return $this->redirect()->toUrl($readUrl);
            if(empty($pUserTextId)) return $this->redirect()->toRoute('oacs', array('action'=>'read'));
            return $this->redirect()->toRoute('oacs', array('param1'=>$pUserTextId, 'action'=>'read'));
        }
        private $listUrl;
        private function getListUrl(){
            if(!isset($this->listUrl)){
                $readUrlX=$this->getServiceLocator()->get('Oacs\\ListUrlClosure');
                $this->listUrl=$readUrlX($this->getServiceLocator()->get('ViewHelperManager')->get('url'));
            }
            return $this->listUrl;
        }
	public function indexAction(){
		if($this->isNotLoggedIn()) return $this->redirect()->toRoute('oacs', array('action'=>'login'));
		$tblOacsUser=$this->getOacsUserTable();
		if(!$tblOacsUser->isSetLoggedInManager()) return $this->redirect2read();
                $lUrl=$this->getListUrl();
                if(!empty($lUrl)) return $this->redirect()->toUrl($lUrl);
		return $this->getViewModel(array('tblOacsUser'=>$tblOacsUser));
	}
	public function createAction(){
		if($this->isNotLoggedIn()) return $this->redirect()->toRoute('oacs', array('action'=>'login'));
		$form = new OacsUserForm('OacsForm', array(), $this->getRequest(), $this->getOacsUserTable()->getLoggedInManager());
		if ($this->getRequest()->isPost() && $form->isValid()) {
			$frmData=$form->getData();
			if(empty($frmData['pass1']) || $frmData['pass1']!=$frmData['pass2']) throw new \Exception('passwotd and confirm password is not equal!');
			$oacsUser=new OacsUser();
			$oacsUser->exchangeArray($frmData);
			$oacsUser->companyTextId=$this->getOacsUserTable()->getLoggedInManager()->companyTextId;
			$oacsUser->setPassword($frmData['pass1']);
			$st=$this->getOacsUserTable()->create($oacsUser);
			#die('@'.__LINE__.': '.__FILE__.'<pre>'.print_r(array($st, $oacsUser->getArrayCopy()), true));
			$this->flashMessenger()->addMessage('user create done...');
			return $this->redirect2read($oacsUser->textId);
		}
		$form->get('submit')->setValue('Add User');
		return $this->getViewModel(array('form'=>$form, 'managerLoggedIn'=>$this->getOacsUserTable()->isSetLoggedInManager()), 'oacs/oacs/create-update.phtml');
	}
	public function updateAction(){
		if($this->isNotLoggedIn()) return $this->redirect()->toRoute('oacs', array('action'=>'login'));
		$oacsUser=$this->getOacsUserTable()->getById($this->params()->fromRoute('param1', 'NULL'));
		$managerLoggedIn=$this->getOacsUserTable()->isSetLoggedInManager();
		$selfUpdate=true;
		if(true==$managerLoggedIn){
			$form = new OacsUserForm('OacsForm', array(), $this->getRequest(), $this->getOacsUserTable()->getLoggedInManager(), $oacsUser);
			$selfUpdate=($this->getOacsUserTable()->getLoggedInManager()->textId===$oacsUser->textId);
		}else $form = new OacsUserForm('OacsForm', array(), $this->getRequest(), null, $oacsUser);
		#$form->setUserToUpdate($oacsUser, ($this->getOacsUserTable()->getLoggedInManager()->textId==$oacsUser->textId));
		if ($this->getRequest()->isPost() && $form->isValid()) {
			$frmData=$form->getData();
			$proceed2update=true;
			if(true==$selfUpdate) $proceed2update=$oacsUser->isPasswordMatched($frmData['pass']);
			if(true==$proceed2update){
				$cmpnTextId=$oacsUser->companyTextId;
				$oacsUser->exchangeArray($frmData);
				$oacsUser->companyTextId=(true==$managerLoggedIn?$this->getOacsUserTable()->getLoggedInManager()->companyTextId:$cmpnTextId);
				$oacsUser->updated=$oacsUser->getMysqlFormatedDateTime();
				if(strlen(trim($frmData['pass1']))>0){
					if($frmData['pass1']!=$frmData['pass2']) throw new \Exception('passwotd and confirm password is not equal!');
					$oacsUser->setPassword($frmData['pass1']);
				}
				$id=$this->getOacsUserTable()->update($oacsUser->textId, $oacsUser);
				$this->flashMessenger()->addMessage('user update done...');
				if($form->isSelfUpdateAssertive()) return $this->redirect2read();
				return $this->redirect2read($oacsUser->textId);
			}else $form->get('pass')->addError('password not matched!');
		}
		$form->bind($oacsUser);
		#$form->get('submit')->setValue('Update Admin');
		return $this->getViewModel(array('form'=>$form, 'selected'=>$oacsUser, 'managerLoggedIn'=>$managerLoggedIn), 'oacs/oacs/create-update.phtml');
	}
	private function isNotLoggedIn(){ return !$this->getAuthService()->hasIdentity(); }
	public function readAction(){
		if($this->isNotLoggedIn()) return $this->redirect()->toRoute('oacs', array('action'=>'login'));
                $slctd=$this->getOacsUserTable()->getById($this->params()->fromRoute('param1', 'NULL'));
                if(strlen($slctd->getTextId())<=0) throw new \Exception('invalid user to read operation!');
                $uId=($slctd->getTextId()==$this->getOacsUserTable()->getById()->getTextId()?null:$slctd->getTextId());
                $readUrl=$this->getReadUrl($uId);
                #die('<pre>'.print_r(array($uId, $readUrl), true));
                if(!empty($readUrl)) return $this->redirect()->toUrl($readUrl);
		#die('<pre>'.var_export($this->getAuthService()->hasIdentity(), true));
		return $this->getViewModel(array('selected'=>$slctd, 'managerLoggedIn'=>$this->getOacsUserTable()->isSetLoggedInManager()), 'oacs/oacs/read-delete.phtml');
	}
	public function deleteAction(){
		if($this->isNotLoggedIn()) return $this->redirect()->toRoute('oacs', array('action'=>'login'));
		$managerLoggedIn=$this->getOacsUserTable()->isSetLoggedInManager();
		if(false==$managerLoggedIn){
			$this->flashMessenger()->addMessage('please login as manager to delete!');
			return $this->redirect2read();
		}
		$oacsUser=$this->getOacsUserTable()->getById($this->params()->fromRoute('param1', 'NULL'));
		if($oacsUser->getTextId()==$this->getOacsUserTable()->getLoggedInManager()->getTextId()){
			$this->flashMessenger()->addMessage('unable to delete!');
			return $this->redirect2read();
		}
		$qry=$this->request->getUri()->getQueryAsArray();
		if(array_key_exists('confirm', $qry) && $oacsUser->getDeleteCode()==$qry['confirm']){
			$oacsUser->setDelete();
			$this->getOacsUserTable()->update($oacsUser->id, $oacsUser);
			$this->flashMessenger()->addMessage('delete request executed...');
			return $this->redirect()->toRoute('oacs');
		}
		return $this->getViewModel(array('selected'=>$oacsUser, 'managerLoggedIn'=>$managerLoggedIn, 'confirmDelete'=>'yes'), 'oacs/oacs/read-delete.phtml');
	}
	# http://wms.shopping.shahadat.web/oacs/password/shkhan/nabIl321
	/**
	 * http://wms.shopping.burtprocess.com/oacs/password/shkhan/n@bIl321
	 * shopping.burtprocess.com[shkhan n@bIl321]: f7b35e877b4a6fdbe9164d4085225de31c95375419d0a7a0bdb17bf0ad2195ecMTIzNDU2Nzg5MDEyMzQ1NtarfWQwAMdiPiiBVJp5kWo=
	 * su.shopping.shahadat.web[shkhan.su n@bIl321]: 1f04b777566a8b712030153fec01e840db72e283e076fc05602c260085b0a33fMTIzNDU2Nzg5MDEyMzQ1NnJuduqvegVoWs/6EhxRzDA=
	 *
	 * die('password: '.$this->getServiceLocator()->get('Oacs\Model\OacsUserTable')->getUserToLogin($this->params()->fromRoute('param1'))->getEncryptedPassword($this->params()->fromRoute('param2')));
	 */
	public function passwordAction(){
		#die('schema: '.$this->getServiceLocator()->get('Oacs\Model\OacsUserTable')->getTableGateway()->getAdapter()->getDriver()->getConnection()->getCurrentSchema());
		#die('$this->getCompanyTextId(): '.$this->getCompanyTextId());
		die('$this->getCompanyTextId(): '.$this->getCompanyTextId().' | password: '.$this->getServiceLocator()->get('Oacs\Model\OacsUserTable')->getUserToLogin($this->params()->fromRoute('param1'))->getEncryptedPassword($this->params()->fromRoute('param2')));
		if($this->isNotLoggedIn()) return $this->redirect()->toRoute('oacs', array('action'=>'login'));
		$managerLoggedIn=$this->getOacsUserTable()->isSetLoggedInManager();
		if(false==$managerLoggedIn){
			$this->flashMessenger()->addMessage('please login as manager to get password!');
			return $this->redirect()->toRoute('oacs');
		}
		die('password: '.$this->getOacsUserTable()->getUserToLogin($this->params()->fromRoute('param1', $this->getOacsUserTable()->getLoggedInManager()->textId))->getEncryptedPassword($this->params()->fromRoute('param2', 'NULL')));
	}


	/**
	 * @var OacsAuthenticationService
	 */
	private $authService;
	/**
	 * @return OacsAuthenticationService
	 */
	private function getAuthService(){
		if(!isset($this->authService)) $this->authService=$this->getServiceLocator()->get('AuthenticationService');
		return $this->authService;
	}
	public function loginAction(){
		#die('<pre>'.var_export($this->getAuthService()->getStorage()->getSessionManager()->getSaveHandler()->read($this->getAuthService()->getStorage()->getSessionId()), true));
		#if ($this->getAuthService()->getStorage()->getSessionManager()->getSaveHandler()->read($this->getAuthService()->getStorage()->getSessionId())) {
			//redirect to success controller...
		#	return $this->redirect()->toRoute('oacs', array('action' => 'read'));
		#}
		if($this->getAuthService()->hasIdentity()) return $this->redirect2read();
		$getQuery=$this->getRequest()->getUri()->getQueryAsArray();
		#die('<pre>'.print_r($getQuery, true));
		$form=new LoginForm();
		$arr2pass=array('form'=>$form);
		if(array_key_exists('url-after-login-success', $getQuery)){
			$redir=new Hidden('url2redir');
			$redir->setValue(urldecode($getQuery['url-after-login-success']));
			$form->add($redir);
		}
		$request = $this->getRequest();
		if ($request->isPost()) {
			$arr2pass['error']='Login Error';
			$postData=$request->getPost();
			$form->setData($postData);
			if ($form->isValid()) {
				$dataform = $form->getData();
				#die('<pre>'.print_r($dataform, true));
				if ($this->getAuthService()->isValid($dataform['loginId'], $dataform['loginPass'])) {
					unset($arr2pass['error']);
					//authentication success
					$myurl=$this->plugin('url');
					$logoutUrl=$myurl->fromRoute('oacs', array(
							'action' => 'logout',
						), array('force_canonical' => true));
					#echo $logouturl; exit();
					$this->getAuthService()->setLogoutUrl($logoutUrl);
					$profileUrl=$myurl->fromRoute('oacs', array(
							'action' => 'read',
						), array('force_canonical' => true));
					$this->getAuthService()->setProfileUrl($profileUrl);
					$this->getAuthService()->setLoggedIn();
					#die('logged in!!! @'.__LINE__.': '.__FILE__);
					if(!array_key_exists('url2redir', $postData)){
						$urlAfterLoginSuccess=$myurl->fromRoute('oacs', array(
								'action' => 'read',
							), array('force_canonical' => true));
						$urlAfterLoginSuccess=$this->getAuthService()->getAfterLoginUrl($urlAfterLoginSuccess);
					}else $urlAfterLoginSuccess=$postData['url2redir'];
					#if($this->getAuthService()->isPostLoginUrlExists()) $urlAfterLoginSuccess=$this->getAuthService()->getPostLoginUrl();
					#die('$urlAfterLoginSuccess: '.$urlAfterLoginSuccess.' @'.__LINE__.': '.__FILE__);
					return $this->plugin('redirect')->toUrl($urlAfterLoginSuccess);
				} # else die('not valid at auth!!! @'.__LINE__.': '.__FILE__);
			} # else die('not valid at form!!! @'.__LINE__.': '.__FILE__);
		}
		return $this->getViewModel($arr2pass);
	}
	public function logoutAction(){
		$this->getAuthService()->clearIdentity();
		return $this->redirect()->toRoute('oacs', array('action'=>'login'));
	}



}

