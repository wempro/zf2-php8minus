<?php
namespace OcrCaptcha;

use OcrCaptcha\Api\CaptchaHandler;
use Zend\Session\Container;
use ProjectCore\ModuleBase;
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
class Module extends ModuleBase {
    protected function isModuleSecure(){ return false; }
    function getNameSpace(){ return __NAMESPACE__; }
	function getCurrentModulePath(){ return __DIR__ . DS; }
	protected function isModuleUsingSSL(){ return ($this->isServerUsingSSL()?parent::isModuleUsingSSL():false); }
	public function getServiceConfig() {
		$rtrn=parent::getServiceConfig();
		$need2create=(!isset($rtrn) || !array_key_exists('factories', $rtrn));
		if(false==$need2create) $need2create=(!array_key_exists('OcrCaptchaService', $rtrn['factories']));
		if(true==$need2create){
		    $rtrn['factories']['OcrCaptchaService']=function ($sm) {
    		        return new CaptchaHandler(new Container('OcrCaptcha'), $sm->get('ViewHelperManager')->get('url'));
    		    };
		}
		return $rtrn;
	}
}

