<?php
namespace MinifyJsCss\Helper;

use Zend\View\Helper\Url;
use Zend\View\Helper\AbstractHelper;

class PinnedScript extends AbstractHelper {
	public static function getMe(Url $pUrl){
		$hdLink=new static();
		$hdLink->setViewHelperUrl($pUrl);
		return $hdLink;
	}
	private $urlInstance;
	public function setViewHelperUrl(Url $pUrl){
		$this->urlInstance=$pUrl;
		return $this;
	}
	public function getViewHelperUrl(){
		if(!isset($this->urlInstance)) throw new \Exception('url instance not set!');
		return $this->urlInstance;
	}
	private $objPinned;
	public function get($pPinIdx){
		if(!isset($this->objPinned)) $this->objPinned=array();
		#echo 'requested: '.$pPinIdx.' | exists: '.implode(', ', array_keys($this->objPinned)).' @'.__LINE__.': '.__FILE__.'<br />';
		if(!array_key_exists($pPinIdx, $this->objPinned)) $this->objPinned[$pPinIdx]=HeadScript::getMe($this->getViewHelperUrl());
		return $this->objPinned[$pPinIdx];
	}
	function __invoke($pPinIdx){ return $this->get($pPinIdx); }
}

