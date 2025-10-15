<?php
namespace OcrCaptcha\Controller;


use Lib3rdParty\Zend\Mvc\Controller\AbstractRestfulController;
use OcrCaptcha\Api\CaptchaHandler;

class OcrCaptchaController extends AbstractRestfulController {
	private $captcha;
	/**
	 * @return CaptchaHandler
	 */
	private function getCaptcha(){
		if(!isset($this->captcha)) $this->captcha=$this->getServiceLocator()->get('OcrCaptchaService');
		return $this->captcha;
	}
	public function indexAction(){
		$id=$this->params('id', null);
		if(!is_null($id)){
			try{
				$cptch=$this->getCaptcha()->getById($id);
			}catch (\Exception $e){
			    return $this->redirect()->toRoute($this->getEvent()->getRouteMatch()->getMatchedRouteName(), array('id'=>$this->getCaptcha()->generate()->getId()), array('query'=>array('prev'=>$id)));
			    /* if(is_null($this->getQueryAsArray('tm2rfrsh', null))){
			        # 01556338084
			        return $this->redirect()->toRoute($this->getEvent()->getRouteMatch()->getMatchedRouteName(), array('id'=>$id), array('query'=>array('tm2rfrsh'=>time())));
			    }
				$cptch=$this->getCaptcha()->generate(); */
			    # https://www.elementsearch.com/ocr-captcha/e9ca742adbd54cdc517fad62410a1dbc
			}
		}else $cptch=$this->getCaptcha()->generate();
		# https://www.elementsearch.com/ocr-captcha/039969bdab132769df40d6fe5eb5a864
		#die('<pre>'.print_r(array($id, $cptch->getId(), $cptch->getWord()), true).'</pre><img src="data:image/jpeg;base64, '.base64_encode($cptch->getImageString()).'" />');
		#die('<pre>'.print_r($cptch->getImageString(), true));
		$response = $this->getResponse();
		$response->getHeaders()->addHeaderLine('Content-Type', "image/png");
		$response->setContent($cptch->getImageString());
		return $response;
	}
}

