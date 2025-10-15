<?php
namespace Wurfl;

use Zend\Mvc\MvcEvent;
use Zend\View\HelperPluginManager;
use Wurfl\View\Helper\WurflHelper;
use ProjectCore\ModuleBase;

class Module extends ModuleBase {
	function getNameSpace(){ return __NAMESPACE__; }
	function getCurrentModulePath(){ return __DIR__ . DS; }

/*
	public function preControllerExecute(MvcEvent $e){
		$rtrn=parent::preControllerExecute($e);
		if(empty($rtrn)) return ;
		
		return $this;
	}
#*/

	public function getViewHelperConfig() {
		$rtrn=parent::getViewHelperConfig();
		$rtrn['factories']['wurfl']=function (HelperPluginManager $sm) {
				$wurflHelper=new WurflHelper();
				$wurflHelper->setServiceLocator($sm->getServiceLocator());
				$wurflHelper->getWurflManager();
				#echo('requesting wurfl... @'.__LINE__.': '.__FILE__).'<br />';
				return $wurflHelper;
			};
		return $rtrn;
	}


}

