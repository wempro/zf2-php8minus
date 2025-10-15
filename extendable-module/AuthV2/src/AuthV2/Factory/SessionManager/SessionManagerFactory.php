<?php
namespace AuthV2\Factory\SessionManager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Config\SessionConfig;
use Zend\Session\SessionManager;

class SessionManagerFactory implements FactoryInterface {
	public function createService(ServiceLocatorInterface $serviceLocator) {
		#die('@'.__LINE__.': '.__FILE__);
		$config = $serviceLocator->has('config') ? $serviceLocator->get('config') : array();
		$config = (array_key_exists('session', $config) && !empty($config['session'])) ? $config['session'] : array();
		
		$sessionConfig = new SessionConfig();
		$sessionConfig->setOptions($config);
		
		// You could also configure the storage adapter, save handler, and
		// validators here and pass them to the session manager constructor,
		// if desired.
		return new SessionManager($sessionConfig);
	}
}

