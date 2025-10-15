<?php
namespace MinifyJsCss;

use Zend\View\HelperPluginManager;
use Zend\ServiceManager\ServiceManager;
use MinifyJsCss\Helper\HeadLink;
use MinifyJsCss\Helper\HeadScript;
use MinifyJsCss\Helper\PinnedScript;
use ProjectCore\ModuleBase;

class Module extends ModuleBase {
    protected function isModuleSecure(){ return false; }
	function getNameSpace(){ return __NAMESPACE__; }
	function getCurrentModulePath(){ return __DIR__ . DS; }
	protected function isModuleUsingSSL(){ return ($this->isServerUsingSSL()?parent::isModuleUsingSSL():false); }


#/*
	public function getServiceConfig() {
		$rtrn=parent::getServiceConfig();
		$need2create=(!isset($rtrn) || !array_key_exists('factories', $rtrn));
		if(false==$need2create) $need2create=(!array_key_exists('minifyJsCssHttpLocation', $rtrn['factories']));
		if(true==$need2create){
			$rtrn['factories']['minifyJsCssHttpLocation']=function (ServiceManager $sm) {
			     if($sm->has('appHttpLocation')) return $sm->get('appHttpLocation');
    			     if(defined('MINIFYJSCSS_BASE_LOCATION')) return MINIFYJSCSS_BASE_LOCATION;
    			     if(defined('APP_INIT_POINT_LOCATION')) return APP_INIT_POINT_LOCATION;
    			     #die('got http location? @'.__LINE__.': '.__FILE__);
    			     if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
    			     if(!defined('APP_ROOT_PATH')){
    			         $curroot=((@__DIR__ == '__DIR__')?((@__FILE__ == '__FILE__')?realpath('.'):dirname(__FILE__)):__DIR__).DS;
    			         $appRootPath=realpath($curroot.'..'.DS.'..').DS;
    			         #throw new \Exception('please set APP_ROOT_PATH at your public index.php and adjust path here accordingle to get right path!');
    			     }else $appRootPath=APP_ROOT_PATH;
    			     #return realpath($appRootPath.'..'.DS.'..'.DS.'public_html').DS;
    			     return realpath($appRootPath.'public_html').DS;
    			     #return $appRootPath.'..'.DS.'public_html'.DS;
				};
		}
		return $rtrn;
	}
#*/
	public function getViewHelperConfig() {
		$rtrn=parent::getViewHelperConfig();
		$rtrn['invokables']['headStyle']='MinifyJsCss\\Helper\\HeadStyle';
		$rtrn['factories']['pinnedScript']=function (HelperPluginManager $sm) {
						return PinnedScript::getMe($sm->getRenderer()->plugin('url'));
					};
		$rtrn['factories']['headScript']=function (HelperPluginManager $sm) {
						return HeadScript::getMe($sm->getRenderer()->plugin('url'));
					};
		$rtrn['factories']['headLink']=function (HelperPluginManager $sm) {
						$url=$sm->getRenderer()->plugin('url');
						$hdLink=new HeadLink();
						$hdLink->setViewHelperUrl($url);
						#die('here i am @'.__LINE__.': '.__FILE__);
						$hdLink->setMinifyUrlBase($url('minify'));
						#die('here i am - '.get_class($url).' @'.__LINE__.': '.__FILE__);
						return $hdLink;
					};
$rtrn['factories']['minifyResizeImage']=function (HelperPluginManager $sm) {
						$url=$sm->getRenderer()->plugin('url');
						$hdLink=new Helper\ResizeImageHelper();
						#$hdLink->setViewHelperUrl($url);
						#die('here i am @'.__LINE__.': '.__FILE__);
						$hdLink->setResizeImageUrlBase($url('minify', array('action'=>'ri')));
						#die('here i am - '.get_class($url).' @'.__LINE__.': '.__FILE__);
						return $hdLink;
					};
		return $rtrn;
	}

}

