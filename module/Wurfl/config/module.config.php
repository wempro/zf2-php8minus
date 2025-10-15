<?php
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

return array(
		'controllers' => array(
				'invokables' => array(
						'Wurfl\\Controller\\Wurfl' => 'Wurfl\\Controller\\WurflController',
						#'Wurfl\\Controller\\' => 'Wurfl\\Controller\\Controller',
				),
		),
		'router' => array(
				'routes' => array(
						'wurfl' => array(
								'type'    => 'segment',
								'options' => array(
										'route'    => '/wurfl',
										'defaults' => array(
												'controller' => 'Wurfl\\Controller\\Wurfl',
												'action'     => 'index',
										),
								),
						),
				),
		),
		'view_manager' => array(
				'template_path_stack' => array(
						'wurfl' => realpath(__DIR__ . DS.'..'.DS.'view').DS,
				),
		),
);