<?php
namespace AuthV2\Controller;

use Zend\View\Model\ViewModel;
#use Zend\Authentication\AuthenticationService;
#use TFW\TfwSoapRequestHandler;

use Zend\View\Model\JsonModel;
#use Zend\Mvc\Controller\AbstractRestfulController;
use Lib3rdParty\Zend\Authentication\AuthenticationService;
use Zend\Http\PhpEnvironment\Request;
use Lib3rdParty\Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Http\PhpEnvironment\Response;

class StatelessAuthenticationController extends AbstractRestfulController {
	public function initController(MvcEvent $e){
		$this->setAccessControlAllowOrigin('http://test.shahadat.web http://nur.works.com');
		return $this;
	}
	/**
	 * @var \WMS\WmsCompany
	 */
	private $curCompany;
	private function getCurrentActiveCompany(){
		if(!isset($this->curCompany)) $this->curCompany=$this->getServiceLocator()->get('AuthV2\\ActiveCompany');
		return $this->curCompany;
	}
	/**
	 * @var WmsContentTracker
	 */
	private $wmsContentTracker;
	private function getContentTracker(){
		if(!isset($this->wmsContentTracker)){
			if(!$this->getServiceLocator()->has('wmsContentTracker')) throw new \Exception('content tracker NOT exists!');
			$this->wmsContentTracker=$this->getServiceLocator()->get('wmsContentTracker');
		}
		return $this->wmsContentTracker;
	}
	private function isContentTrackerExists(){
		if(isset($this->wmsContentTracker)) return true;
		return $this->getServiceLocator()->has('wmsContentTracker');
	}
	/**
	 * @var \AuthV2\Api\AuthenticationService
	 */
	protected $authService;
	//we will inject authService via factory
	public function __construct(AuthenticationService $authService) { $this->authService = $authService; }
	/*private $userTable;
	public function getUserTable() {
		if (!$this->userTable) $this->userTable = $this->getServiceLocator()->get('Registration\Model\UserTable');
		return $this->userTable;
	}*/

	private $configData;
	/**
	 * ..
	 * forgotPasswordUrl<br />
	 * urlAfterLoginSuccess<br />
	 * login -> mailAnonUserInfoToAdmin
	 */
	private function getConfigData(){
		if(!isset($this->configData)) $this->configData=$this->getServiceLocator()->get('Config');
		return $this->configData;
	}
	private $loginConfigData;
	private function getLoginConfigData(){
		if(!isset($this->loginConfigData)){
			$config=$this->getConfigData();
			$this->loginConfigData=array();
			if(array_key_exists('login', $config)) $this->loginConfigData=$config['login'];
		}
		return $this->loginConfigData;
	}

	 
	/** this function called by indexAction to reduce complexity of function */
	private function authenticate($form, $viewModel) {
		#echo 'current: '.microtime(true).' @'.__LINE__.'<br />';
		$myurl=$this->plugin('url');
		$urlAfterLoginSuccess=$this->getUrlAfterLoginSuccess($myurl->fromRoute('login', array(
				'action' => 'welcome',
			), array('force_canonical' => true)));
		$request = $this->getRequest();
		$getQuery = $request->getUri()->getQueryAsArray();
		if(array_key_exists('url-after-login-success', $getQuery)){
			$urlAfterLoginSuccess=$getQuery['url-after-login-success'];
			$this->authService->setAfterLoginUrl($getQuery['url-after-login-success']);
		}
		if ($request->isPost()) {
			#echo 'current: '.microtime(true).' @'.__LINE__.'<br />';
			$form->setData($request->getPost());
			if ($form->isValid()) {
				#echo 'current: '.microtime(true).' @'.__LINE__.'<br />';
				$dataform = $form->getData();
				#var_dump($dataform); exit();
				 
				#$this->authService->getAdapter()->setIdentity($dataform['loginId'])->setCredential($dataform['loginPass']);
				#$result = $this->authService->authenticate();
				#var_dump($result); exit();
				if ($this->authService->isValid($dataform['loginId'], $dataform['loginPass'])) {
					#echo 'current: '.microtime(true).' @'.__LINE__.'<br />';
					//authentication success
					$this->authService->setUrlHandler($myurl);
					$this->authService->setLoggedIn($request->getServer('REMOTE_ADDR'), $request->getServer('HTTP_USER_AGENT'));
					if($this->isContentTrackerExists()) $this->getContentTracker()->loggedInAsRegisteredUser($dataform['loginId']);
					#if(empty($urlAfterLoginSuccess)) $urlAfterLoginSuccess=$myurl->fromRoute('login', array('action' => 'welcome'), array('force_canonical' => true));
					#$config = $this->getServiceLocator()->get('Config');
					#if(!empty($config['urlAfterLoginSuccess'])) $urlAfterLoginSuccess=(self::isClosure($config['urlAfterLoginSuccess'])?$config['urlAfterLoginSuccess']($myurl):trim($config['urlAfterLoginSuccess']));
					#echo $urlAfterLoginSuccess.'<pre>'.print_r($config['urlAfterLoginSuccess'], true); exit();
					#$urlAfterLoginSuccess=$this->authService->getAfterLoginUrl($urlAfterLoginSuccess);
					$this->authService->setAfterLoginUrl();
					#echo 'current: '.microtime(true).' @'.__LINE__.'<br />'; exit();
					return $this->plugin('redirect')->toUrl($urlAfterLoginSuccess);
				} else {
					$viewModel->setVariable('error', 'Email or password did not match. Please try again.');
				}
			}
		}
		return $this;
	}
	static function isClosure($arg) {
		$test = function(){};
		return $arg instanceof $test;
	}
	private function redir2home(){
		return $this->redirect()->toRoute('home');
	}
	private function getUrlAfterLoginSuccess($pUrlAfterLoginSuccess=NULL){
		$config = $this->getConfigData();
		if(!empty($config['urlAfterLoginSuccess'])){
			$pUrlAfterLoginSuccess=(self::isClosure($config['urlAfterLoginSuccess'])?$config['urlAfterLoginSuccess']($this->plugin('url')):trim($config['urlAfterLoginSuccess']));
			#echo '$pUrlAfterLoginSuccess: '.$pUrlAfterLoginSuccess.'<br />';
		}
		$sessurl=$pUrlAfterLoginSuccess=$this->authService->getAfterLoginUrl($pUrlAfterLoginSuccess);
		$getQuery = $this->getRequest()->getUri()->getQueryAsArray();
		if(array_key_exists('url-after-login-success', $getQuery)) $url2compr=$pUrlAfterLoginSuccess=$getQuery['url-after-login-success'];
		$postData=$this->getRequest()->getPost()->getArrayCopy();
		if(array_key_exists('redir2url', $postData) && !empty($postData['redir2url'])){
			$url2compr=$pUrlAfterLoginSuccess=$postData['redir2url'];
			#echo 'post: '.$pUrlAfterLoginSuccess.'<br />';
		}
		#die('$url2compr: '.$url2compr.', $pUrlAfterLoginSuccess: '.$pUrlAfterLoginSuccess.', $sessurl: '.$sessurl.' @'.__LINE__.': '.__FILE__);
		if(isset($url2compr) && $sessurl!=$url2compr) $this->authService->setAfterLoginUrl($url2compr);
		return $pUrlAfterLoginSuccess;
	}
    public function logoutAction() {
    	#echo 'begin: '.REQUEST_MICROTIME.' @'.__LINE__.': '.__FILE__.'<br />';
    	#echo 'current: '.microtime(true).' @'.__LINE__.': '.__FILE__.'<br />';
    	$this->authService->clearIdentity();
    	#echo 'current: '.microtime(true).' @'.__LINE__.': '.__FILE__.'<br />';
    	if($this->isContentTrackerExists()) $this->getContentTracker()->notLoggedIn();
    	#echo 'current: '.microtime(true).' @'.__LINE__.': '.__FILE__.'<br />'; exit();
    	return $this->redirect()->toRoute('login');
    }


    /**
     * @return AuthenticationService
     */
    private function getStatelessAuthService(){
    	return $this->authService;
    }
    public function indexAction(){
    	$request = $this->getRequest();
    	/** @var Request $request */
    	/* return new JsonModel(array(
    			'method'=>$request->getMethod(),
    			'isXmlHttpRequest'=>($request->isXmlHttpRequest()?'Yes':'NO'),
    			'posted'=>$this->getPostedData(),
    			#'content'=>$request->getContent(),
    	)); */
    	$response = $this->getResponse();
    	#$server  = $request->getServer();
    	/* $response->setStatusCode(Response::STATUS_CODE_408); # only post method, no other method allowed
    	$response->getHeaders()->addHeaderLine('Allow', 'POST');
    	return $response; */
    	#return $this->getJsonModel($request->getHeaders()->toArray());
    	if (!$request->isXmlHttpRequest()){
    		#return new JsonModel($request->getHeaders()->toArray());
    		$this->flashMessenger()->addMessage('not a valid json request!');
    		$response->setStatusCode(Response::STATUS_CODE_307); # Temporary Redirect [only valid ajax request accepted]
    		return $this->redirect()->toRoute('login');
    	}
    	switch(strtoupper($request->getMethod())){
    		case Request::METHOD_POST:
    			$post=$this->getPostedData();
    			if(array_key_exists('user', $post) && array_key_exists('pass', $post)){
    				#return new JsonModel(array('cls'=>get_class($this->getStatelessAuthService())));
    				if($this->getStatelessAuthService()->isValid($post['user'], $post['pass'])){
    				    #return new JsonModel(array('cls'=>get_class($this->getStatelessAuthService()), 'status'=>'valid credential', 'token'=>$this->getStatelessAuthService()->getSessionSecret()));
    				    if($this->getStatelessAuthService()->setLoggedIn()){
    					    $response->setStatusCode(Response::STATUS_CODE_200);
    						#return new JsonModel(array('cls'=>get_class($this->getStatelessAuthService()), 'status'=>'valid credential assigned', 'user'=>$uArr));
    						$viewModel = new JsonModel();
    						$viewModel->setVariable('userId', $post['user']);
    						$viewModel->setVariable('userData', $this->getStatelessAuthService()->getLoggedInUser()->getArrayCopyForRest());
    						$viewModel->setVariable('token', $this->getStatelessAuthService()->getSessionSecret());
    						#$response->getHeaders()->addHeaderLine('Token', $this->getStatelessAuthService()->getSessionSecret($post['user']));
    						return $viewModel;
    					}else $response->setStatusCode(Response::STATUS_CODE_500); # internal server error, cause we already validate the user and pass. so we must able to set credential, but fail!
    				}else $response->setStatusCode(Response::STATUS_CODE_401); # unauthorized, we don't have such user or password
    			}else $response->setStatusCode(Response::STATUS_CODE_428); # Precondition Required [value missing]
    			break;
			default:
			    $response->setStatusCode(Response::STATUS_CODE_405); # only post method, no other method allowed
			    $response->getHeaders()->addHeaderLine('X-Response-Message', 'method - '.$request->getMethod());
			    $response->getHeaders()->addHeaderLine('Allow', 'POST');
    	}
    	$response->getHeaders()->addHeaderLine('X-Request-URL', $this->getCurrentUrl());
    	return $response;
    }
    
    public function secretDataAction(){
        $request = $this->getRequest();
        $response = $this->getResponse();
        if (!$request->isXmlHttpRequest()){
            #return new JsonModel($request->getHeaders()->toArray());
            $this->flashMessenger()->addMessage('not a valid json request!');
            $response->setStatusCode(Response::STATUS_CODE_307); # Temporary Redirect [only valid ajax request accepted]
            return $this->redirect()->toRoute('login');
        }
        if(false==$this->getStatelessAuthService()->hasIdentity()){
            switch(strtoupper($request->getMethod())){
                case Request::METHOD_POST:
                    $post=$this->getPostedData();
                    if(array_key_exists('token', $post) && !empty($post['token'])){
                        if($this->getStatelessAuthService()->setToken($post['token'])){
                            #return new JsonModel(array('cls'=>get_class($this->getStatelessAuthService()), 'status'=>'valid credential'));
                            if($this->getStatelessAuthService()->hasIdentity()){
                                $response->setStatusCode(Response::STATUS_CODE_200);
                                #return new JsonModel(array('cls'=>get_class($this->getStatelessAuthService()), 'status'=>'valid credential assigned', 'data'=>'this is a test response and treat as sectet data'));
                            }else $response->setStatusCode(Response::STATUS_CODE_500); # internal server error, cause we already validate the user and pass. so we must able to set credential, but fail!
                        }else $response->setStatusCode(Response::STATUS_CODE_401); # unauthorized, we don't have such user or password
                    }else $response->setStatusCode(Response::STATUS_CODE_428); # Precondition Required [value missing]
                    break;
                default:
                    $response->setStatusCode(Response::STATUS_CODE_405); # only post method, no other method allowed
                    $response->getHeaders()->addHeaderLine('Allow', 'POST');
            }
        }
        if($this->getStatelessAuthService()->hasIdentity()) return new JsonModel(array('cls'=>get_class($this->getStatelessAuthService()), 'status'=>'valid credential assigned', 'data'=>'this is a test response and treat as sectet data'));
        return $response;
    }




}