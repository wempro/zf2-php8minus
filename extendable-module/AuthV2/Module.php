<?php
namespace AuthV2;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;
use Zend\View\HelperPluginManager;
use WemproMarketplace\WemproMarketplaceModuleBase;
use WvmDb\Registration\User\UserTable;
use WvmDb\Registration\User\User;
use ProjectCore\User\LoggedInUserHandler;

class Module extends WemproMarketplaceModuleBase implements AutoloaderProviderInterface, ViewHelperProviderInterface {
	protected function isModuleSecure(){ return false; }
	function getNameSpace(){ return __NAMESPACE__; }
	function getCurrentModulePath(){ return __DIR__ . DS; }
	protected function isModuleUsingSSL(){ return true; }

	// Add this method:
	public function getServiceConfig() {
		$dbAdapterServiceIdx='Zend\\Db\\Adapters\\WvmRegistrationAdapter';
		$sessTableName=$this->getSessionTableName();
		$companyObjSrvcTxtId=self::getServiceIdxToGetMasterVendorCompanyObject();
		$srvc2rtrn=array(
						self::getServiceIdxToGetUserLoginHandler()=> function($sm) {
								try {
									return new LoggedInUserHandler($sm->get('AuthenticationService'));
								} catch (\Exception $e) {
								}
								
							},
                                                'AuthV2\\ContentTracker'=>function ($sm) { return 'TODO'; },
                                                'AuthV2\\ForgotPasswordLink'=>function ($sm) { return 'TODO'; },
                                                'AuthV2\\RegistrationLink'=>function ($sm) { return 'TODO'; },
                                                'AuthV2\\AfterLoginSuccessLink'=>function ($sm) { return 'TODO'; },
						'UserSessTableName'=>function ($sm) use($sessTableName){ return $sessTableName; },
						'AuthV2\\ActiveCompany'=> function($sm) use ($companyObjSrvcTxtId) {
							return $sm->get($companyObjSrvcTxtId)->getWmsObject();
						},
						'TfwAuthDatabase'=>function ($sm) use($dbAdapterServiceIdx){ return $sm->get($dbAdapterServiceIdx); },
						/* 'AuthStorage' => function ($sm) {
							$storageFactory=new Factory\Storage\AuthStorageFactory();
							return $storageFactory->createService($sm);
						},
						'AuthenticationService' => function ($sm) {
							$authSrvcFactory=new Factory\Authentication\AuthenticationServiceFactory();
							return $authSrvcFactory->createService($sm);
						},
						'StatelessAuthenticationService' => function ($sm) {
							$authSrvcFactory=new Factory\Authentication\StatelessAuthenticationServiceFactory();
							return $authSrvcFactory->createService($sm);
						}, */
						#'TfwAuthUserTableGateway' => function ($sm) { return Model\User::getMe($sm->get('TfwAuthDatabase')); },
						'AuthV2\\User\\UserTable'=>function ($sm){ return new UserTable(User::getMe($sm->get('TfwAuthDatabase'))); },
						'mailAnonUserInfoToAdmin'=>function ($sm){
							return false;
							#return $sm->get('wmsMailAnonUserInfoToAdmin');
						},
						'allowAnonymousLogin'=>function ($sm){
							return $sm->get('AuthV2\\ActiveCompany')->isAnonymousCartEnabled();
							#return $sm->get('wmsAllowAnonymousLogin');
						},
				);
		$rtrn=parent::getServiceConfig();
		foreach($srvc2rtrn as $k=>$srvc) $rtrn['factories'][$k]=$srvc;
		return $rtrn;
	}

	public function getViewHelperConfig() {
		$rtrn=parent::getViewHelperConfig();
		$rtrn['factories']['loginForm']=function(HelperPluginManager $helperPluginManager) {
				$serviceLocator = $helperPluginManager->getServiceLocator();
				$viewHelper = new View\Helper\LoginForm();
				$viewHelper->setServiceLocator($serviceLocator);
				return $viewHelper;
			};
		return $rtrn;
	}

}