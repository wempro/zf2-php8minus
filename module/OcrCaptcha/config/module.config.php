<?php
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

return array(
		'controllers' => array(
				'invokables' => array(
						'OcrCaptcha\\Controller\\OcrCaptcha' => 'OcrCaptcha\\Controller\\OcrCaptchaController',
					),
			),
		'router' => array(
				'routes' => array(
						'ocr-captcha' => array(
								'type'    => 'Literal',
								'options' => array(
										'route'    => '/ocr-captcha',
										'defaults' => array(
												'controller' => 'OcrCaptcha\\Controller\\OcrCaptcha',
												'action'     => 'index',
											),
									),
								'may_terminate' => true,
								'child_routes' => array(
										'actions'=>array(
												'type'    => 'Segment',
												'options' => array(
														'route'    => '/:action[/:param1[/:param2[/:param3]]]',
														'defaults' => array(
																'controller' => 'OcrCaptcha\\Controller\\OcrCaptcha',
															),
													),
											),
										'index'=>array(
												'type'    => 'Segment',
												'options' => array(
														'route'    => '/:id[/:param1[/:param2[/:param3]]]',
														'constraints' => array(
																'id' => '[a-zA-Z0-9]+',
															),
														'defaults' => array(
																'controller' => 'OcrCaptcha\\Controller\\OcrCaptcha',
																'action'=>'index'
															),
													),
											),
								),

							),
					),
			),
		'view_manager' => array(
				'template_path_stack' => array(
						'ocr-captcha' => realpath(__DIR__ . DS.'..'.DS.'view').DS,
					),
				'strategies' => array(
						'ViewJsonStrategy',
					),
			),
	);