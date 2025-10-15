<?php
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

return array(
	/* 'dependencies'=>array(
		'factories'=>array(
			'Zend\Session\SessionManager' => 'AuthV2\Factory\SessionManager\SessionManagerFactory',
		)
	), */
		'controllers' => array(
				'factories' => array(
						'AuthV2\Controller\Auth' => 'AuthV2\Factory\Controller\AuthControllerServiceFactory',
						'AuthV2\Controller\AuthStateless' => 'AuthV2\Factory\Controller\StatelessAuthControllerServiceFactory'
					),
				'invokables' => array(
						'AuthV2\Controller\Success' => 'AuthV2\Controller\SuccessController'
					),
				/* 'invokables' => array(
						'Login\Controller\Login' => 'Login\Controller\LoginController',
				), */
		),
		'service_manager' => array(
				'aliases' => array(
						'Zend\Authentication\AuthenticationService' => 'AuthenticationService',
				),
				'factories' => array(
						'AuthStorage' => 'AuthV2\Factory\Storage\AuthStorageFactory',
						'AuthenticationService' => 'AuthV2\Factory\Authentication\AuthenticationServiceFactory',
						'StatelessAuthenticationService' => 'AuthV2\Factory\Authentication\StatelessAuthenticationServiceFactory',
					#'Zend\Session\SessionManager' => 'AuthV2\Factory\SessionManager\SessionManagerFactory', ### NOT a good idea
				),
		),
    /*
		'login_config'=>array(
				'urls'=>array(
						'forgot'=>function ($piUrl) { return $piUrl->fromRoute('registration', array('action'=>'forgot-password'), array('force_canonical' => true)); },
						'registration'=>function ($piUrl) { return $piUrl->fromRoute('registration', array(), array('force_canonical' => true)); },
					),
			),
                                                        # */
		'router' => array(
				'routes' => array(
						'login' => array(
								'type'    => 'segment',
								'options' => array(
										'route'    => '/login[/[:action[/[:param1[/[:param2[/[:param3[/[:param4[/[:param5]]]]]]]]]]]]',
										'constraints' => array(
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
											),
										'defaults' => array(
												'controller' => 'AuthV2\Controller\Auth', /* this settings depend on invokables array of controllers configuration above */
												'action'     => 'index',
											),
									),
							),

						'login-stateless' => array(
								'type'    => 'segment',
								'options' => array(
										'route'    => '/api/login[/[:action[/[:param1[/[:param2[/[:param3[/[:param4[/[:param5]]]]]]]]]]]]',
										'constraints' => array(
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
											),
										'defaults' => array(
												'controller' => 'AuthV2\Controller\AuthStateless',
												'action'     => 'index',
											),
									),
							),
						'login-success' => array(
								'type'    => 'segment',
								'options' => array(
										'route'    => '/login/success[/[:action[/[:param1[/[:param2[/[:param3]]]]]]]]',
										'defaults' => array(
												'controller' => 'AuthV2\Controller\Success',
												'action'     => 'index',
											),
									),
							),

				),
		),
		'view_manager' => array(
				'template_path_stack' => array(
						'authv2' => __DIR__ . DS.'..'.DS.'view',
				),
				'strategies' => array(
						'ViewJsonStrategy',
				),
		),

/*
    'view_helpers' => array(  
        'invokables' => array(  
            'loginForm' => 'TFWAuth\View\Helper\LoginForm',  
            // more helpers here ...  
        )  
    ),
*/
		
		
);

/*
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:939 - call_user_func(, , zendsessionsessionmanager, Zend\Session\SessionManager)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:1099 - Zend\ServiceManager\ServiceManager\createServiceViaCallback(, zendsessionsessionmanager, Zend\Session\SessionManager)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:638 - Zend\ServiceManager\ServiceManager\createFromFactory(zendsessionsessionmanager, Zend\Session\SessionManager)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:598 - Zend\ServiceManager\ServiceManager\doCreate(Zend\Session\SessionManager, zendsessionsessionmanager)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:530 - Zend\ServiceManager\ServiceManager\create()
/home/elementsearch/application/app-src/vendor/ZF2/extendable-module/AuthV2/src/AuthV2/Factory/Storage/AuthStorageFactory.php:11 - Zend\ServiceManager\ServiceManager\get(Zend\Session\SessionManager)
NO_FILE:NO_LINE - AuthV2\Factory\Storage\AuthStorageFactory\createService(, authstorage, AuthStorage)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:939 - call_user_func(, , authstorage, AuthStorage)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:1097 - Zend\ServiceManager\ServiceManager\createServiceViaCallback(, authstorage, AuthStorage)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:638 - Zend\ServiceManager\ServiceManager\createFromFactory(authstorage, AuthStorage)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:598 - Zend\ServiceManager\ServiceManager\doCreate(AuthStorage, authstorage)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:530 - Zend\ServiceManager\ServiceManager\create()
/home/elementsearch/application/app-src/vendor/ZF2/extendable-module/AuthV2/src/AuthV2/Factory/Authentication/AuthenticationServiceFactory.php:32 - Zend\ServiceManager\ServiceManager\get(AuthStorage)
NO_FILE:NO_LINE - AuthV2\Factory\Authentication\AuthenticationServiceFactory\createService(, authenticationservice, AuthenticationService)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:939 - call_user_func(, , authenticationservice, AuthenticationService)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:1097 - Zend\ServiceManager\ServiceManager\createServiceViaCallback(, authenticationservice, AuthenticationService)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:638 - Zend\ServiceManager\ServiceManager\createFromFactory(authenticationservice, AuthenticationService)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:598 - Zend\ServiceManager\ServiceManager\doCreate(AuthenticationService, authenticationservice)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:530 - Zend\ServiceManager\ServiceManager\create()
/home/elementsearch/application/app-src/vendor/ZF2/extendable-module/AuthV2/AuthV2BaseModule.php:126 - Zend\ServiceManager\ServiceManager\get(AuthenticationService)
NO_FILE:NO_LINE - AuthV2\AuthV2BaseModule\AuthV2\{closure}(, wvmuserloginhandler, WvmUserLoginHandler)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:939 - call_user_func(, , wvmuserloginhandler, WvmUserLoginHandler)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:1099 - Zend\ServiceManager\ServiceManager\createServiceViaCallback(, wvmuserloginhandler, WvmUserLoginHandler)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:638 - Zend\ServiceManager\ServiceManager\createFromFactory(wvmuserloginhandler, WvmUserLoginHandler)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:598 - Zend\ServiceManager\ServiceManager\doCreate(WvmUserLoginHandler, wvmuserloginhandler)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:530 - Zend\ServiceManager\ServiceManager\create()
/home/elementsearch/application/app-src/applications/WemproMarketplace/module/Index/Module.php:133 - Zend\ServiceManager\ServiceManager\get(WvmUserLoginHandler)
NO_FILE:NO_LINE - Index\Module\Index\{closure}(, loggedinuserhelper, loggedInUserHelper)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:939 - call_user_func(, , loggedinuserhelper, loggedInUserHelper)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/AbstractPluginManager.php:284 - Zend\ServiceManager\ServiceManager\createServiceViaCallback(, loggedinuserhelper, loggedInUserHelper)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/AbstractPluginManager.php:244 - Zend\ServiceManager\AbstractPluginManager\createServiceViaCallback(, loggedinuserhelper, loggedInUserHelper)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:638 - Zend\ServiceManager\AbstractPluginManager\createFromFactory(loggedinuserhelper, loggedInUserHelper)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:598 - Zend\ServiceManager\ServiceManager\doCreate(loggedInUserHelper, loggedinuserhelper)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/ServiceManager.php:530 - Zend\ServiceManager\ServiceManager\create()
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/ServiceManager/AbstractPluginManager.php:116 - Zend\ServiceManager\ServiceManager\get(loggedInUserHelper, )
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/View/Renderer/PhpRenderer.php:372 - Zend\ServiceManager\AbstractPluginManager\get(loggedInUserHelper, )
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/View/Renderer/PhpRenderer.php:390 - Zend\View\Renderer\PhpRenderer\plugin(loggedInUserHelper)
/home/elementsearch/application/app-src/applications/WemproMarketplace/module/Index/view/layout/layout.phtml:202 - Zend\View\Renderer\PhpRenderer\__call(loggedInUserHelper, )
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/View/Renderer/PhpRenderer.php:501 - include(/home/elementsearch/application/app-src/applications/WemproMarketplace/module/Index/view/layout/layout.phtml)
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/View/View.php:205 - Zend\View\Renderer\PhpRenderer\render()
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/Mvc/View/Http/DefaultRenderingStrategy.php:103 - Zend\View\View\render()
NO_FILE:NO_LINE - Zend\Mvc\View\Http\DefaultRenderingStrategy\render()
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/EventManager/EventManager.php:444 - call_user_func(, )
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/EventManager/EventManager.php:205 - Zend\EventManager\EventManager\triggerListeners(render, , )
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/Mvc/Application.php:353 - Zend\EventManager\EventManager\trigger(render, )
/home/elementsearch/application/app-src/vendor/ZF2/library/Zend/Mvc/Application.php:328 - Zend\Mvc\Application\completeRequest()
/home/elementsearch/public_html/index.php:30 - Zend\Mvc\Application\run()
 */






