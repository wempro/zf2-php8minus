<?php
namespace OcrCaptcha\Model;


use OcrCaptcha\Api\CaptchaHandler;

class OcrCaptcha {
	private $id;
	private $word;
	private $urlClosure;
	function __construct($pWord, $pUrlClosure=null){
	    #die('@'.__LINE__.': '.__FILE__);
		$this->id=md5($pWord);
		$this->word=$pWord;
		$this->urlClosure=$pUrlClosure;
	}
	private $imgHtml;
	public function getImageHtml(){
		if(!isset($this->imgHtml)){
			/* if(!isset($this->urlClosure)) throw new \Exception('URL Closure NOR Defined!');
			$fUrlClosure=$this->urlClosure;
			$this->imgHtml='<img src="'.$fUrlClosure($this->getId()).'" class="ocr-captcha-image ocr-captcha-image-img" />'; */
			$this->imgHtml='<img src="data:image/jpeg;base64, '.base64_encode($this->getImageString()).'" class="ocr-captcha-image ocr-captcha-image-img" />';
		}
		return $this->imgHtml;
	}
	public function getHtml($pChallangeHiddenValueElementName=null){
		if(is_null($pChallangeHiddenValueElementName)) $pChallangeHiddenValueElementName=CaptchaHandler::ELEMENT_NAME_CHALLANGE;
		return '<div class="ocr-captcha-image ocr-captcha-image-div">'.$this->getImageHtml().'<input type="hidden" name="'.$pChallangeHiddenValueElementName.'" value="'.$this.'" /></div>';
	}
	function __toString(){ return $this->getId(); }
	public function getId(){ return $this->id; }
	public function getWord(){ return $this->word; }
	public function isValid($pWord){ return strtolower(trim($this->word))==strtolower(trim($pWord)); }
	private $imageData;
	public function getImageString(){
		if(isset($this->imageData)) return $this->imageData;
		$w=200;
		$h=50;
		$image = imagecreatetruecolor($w, $h);
		$background_color = imagecolorallocate($image, 255, 255, 255);
		imagefilledrectangle($image,0,0,$w,$h,$background_color);
		/*$line_color = imagecolorallocate($image, 64,64,64);
		 for($i=0;$i<10;$i++) {
		 imageline($image,0,rand()%$h,$w,rand()%$h,$line_color);
		 }*/
		$pixel_color = imagecolorallocate($image, 255,255,0);
		for($i=0;$i<1000;$i++) {
			imagesetpixel($image,rand()%$w,rand()%$h,$pixel_color);
		}
		$text_color = imagecolorallocate($image, 255,0,0);
		for ($i = 0; $i< strlen($this->word);$i++) {
			$letter = substr($this->word, $i, 1);
			imagestring($image, 72,  5+($i*30), 20, $letter, $text_color);
		}
		ob_start();
		imagepng($image);
		$imgData = ob_get_contents();
		ob_end_clean();
		imagedestroy($image);
		$this->imageData=$imgData;
		return $this->imageData;
	}
}

