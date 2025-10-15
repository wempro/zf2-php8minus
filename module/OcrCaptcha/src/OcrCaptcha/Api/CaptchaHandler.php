<?php
namespace OcrCaptcha\Api;


use Zend\Session\Container;
use OcrCaptcha\Model\OcrCaptcha;
use Zend\Form\FormInterface;

class CaptchaHandler {
	private $sess;
	private $url;
	function __construct(Container $pSess, \Zend\View\Helper\Url $pUrl){
		#die('$pUrl: '.get_class($pUrl).' @'.__LINE__.': '.__FILE__);
		$this->url=$pUrl;
		$this->sess=$pSess;
		if(!$this->sess->offsetExists('init')) $this->sess->offsetSet('init', 'test @'.__LINE__.': '.__FILE__);
		$this->generate();
	}
	private $generated;
	public function generate($pReGenerate=false){
	    if(!isset($this->generated) || true==$pReGenerate){
    		$letters = 'ABDEFGHLMNQRT23456789abdefghmnqrt';
    		$len = strlen($letters);
    		$word='';
    		for ($i = 0; $i< 6;$i++) {
    			$letter = $letters[rand(0, $len-1)];
    			$word.=$letter;
    		}
    		$this->generated=$this->getOcrCaptcha($word);
    		$this->sess->offsetSet($this->generated->getId(), $this->generated->getWord());
	    }
	    return $this->generated;
	}
	# https://www.elementsearch.com/ocr-captcha/7837e43cfa5c53228452e5be45090003
	private function getOcrCaptcha($pWord){
		$url=$this->url;
		return new OcrCaptcha($pWord, function ($pId) use ($url) {
				return $url('ocr-captcha/index', array('id'=>$pId), array('query'=>array('tm2rfrsh'=>time())));
			});
	}
	public function getById($pId){
		if($this->sess->offsetExists($pId)) return $this->getOcrCaptcha($this->sess->offsetGet($pId));
		throw new \Exception('NOT Found!');
	}
	public function isValid($pId, $pInput=null){
		if(is_array($pId)){
			if(array_key_exists(self::ELEMENT_NAME_CHALLANGE, $pId) && array_key_exists(self::ELEMENT_NAME_INPUT, $pId)){
				return $this->isValid($pId[self::ELEMENT_NAME_CHALLANGE], $pId[self::ELEMENT_NAME_INPUT]);
			}
		}elseif(!empty($pInput)){
		    #die('@'.__LINE__.': '.$this->sess->offsetGet($pId).' | $pInput: '.$pInput);
			try{
				$challange=$this->getById($pId);
				return $challange->isValid($pInput);
			}catch(\Exception $e){
				#die('@'.__LINE__.': '.$e->getMessage().'<pre>'.$e->getTraceAsString());
			}
		}
		return false;
	}
	public function getHtml(){
	    #die('@'.__LINE__.': '.__FILE__);
		return '<div class="ocr-captcha ocr-captcha-div"><div class="ocr-captcha-input ocr-captcha-input-div"><input type="text" name="'.self::ELEMENT_NAME_INPUT.'" class="ocr-captcha-input ocr-captcha-input-input" /></div>'.$this->generate()->getHtml().'</div>';
	}



	####### form element
	const ELEMENT_NAME_INPUT='ocrCaptchaInput';
	const ELEMENT_NAME_CHALLANGE='ocrCaptchaChallange';
	public function attachInputElement(FormInterface $pForm){
		$pForm->add(array(
						'type'  => 'Text',
						'name' => self::ELEMENT_NAME_INPUT,
						'attributes' => array(
								'id' => self::ELEMENT_NAME_INPUT.'ID',
								'class' => 'form-control',
							),
					));
		return $this;
	}
	public function attachChallangeElement(FormInterface $pForm){
		$challange=$this->generate()->getId();
		$pForm->add(array(
				'type'  => 'Hidden',
				'name' => self::ELEMENT_NAME_CHALLANGE,
				'attributes' => array(
						'id' => self::ELEMENT_NAME_CHALLANGE.'ID',
						'value'=>$challange,
					),
			));
		$pForm->get(self::ELEMENT_NAME_INPUT)->setValue(null);
		return $challange;
	}
	public function attachValidator(FormInterface $pForm, $pCaptchaEptyMessage='Captcha Required!', $pMismatchMessage='Captcha NOT Matched!'){
		$noEmpty=new \Zend\Validator\NotEmpty(\Zend\Validator\NotEmpty::ALL);
		$noEmpty->setMessage($pCaptchaEptyMessage, \Zend\Validator\NotEmpty::IS_EMPTY);
		$pForm->getInputFilter()->get(self::ELEMENT_NAME_INPUT)->setRequired(true)->getValidatorChain()->attach($noEmpty);
		$ocrCaptcha=$this;
		$chllngElementName=self::ELEMENT_NAME_CHALLANGE;
		$callback = new \Zend\Validator\Callback(function ($value, $context) use($ocrCaptcha, $chllngElementName) {
				// Your validation logic
				if(array_key_exists($chllngElementName, $context) && !empty($context[$chllngElementName])){
					try{
						$challange=$ocrCaptcha->getById($context[$chllngElementName]);
					}catch (\Exception $e){
							
					}
				}
				if(!isset($challange)) $challange=$ocrCaptcha->generate();
				return $challange->isValid($value);
			});
		$callback->setMessage($pMismatchMessage);
		$pForm->getInputFilter()->get(self::ELEMENT_NAME_INPUT)->getValidatorChain()->attach($callback);
		return $this;
	}
}


