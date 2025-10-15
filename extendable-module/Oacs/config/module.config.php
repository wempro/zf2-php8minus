<?php
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

return array(
		'controllers' => array(
				'invokables' => array(
						'Oacs\Controller\Oacs' => 'Oacs\Controller\OacsController',
				),
		),
		'router' => array(
				'routes' => array(
						'oacs' => array(
								'type'    => 'segment',
								'options' => array(
										'route'    => '/oacs[/[:action[/:param1[/:param2[/:param3[/:param4]]]]]]',
										'constraints' => array(
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
										),
										'defaults' => array(
												'controller' => 'Oacs\Controller\Oacs',
												'action'     => 'index',
										),
								),
						),
				),
		),
		'view_manager' => array(
				'template_path_stack' => array(
						'oacs' => __DIR__ . DS.'..'.DS.'view',
				),
		),
);