<?php
namespace AuthV2\Storage;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Authentication\Storage;
use Zend\Session\Config\SessionConfig;
use Zend\Db\TableGateway\TableGateway;
use Zend\Session\SaveHandler\DbTableGateway;
use Zend\Session\SaveHandler\DbTableGatewayOptions;

class TfwAuthStorageX extends Storage\Session implements ServiceLocatorAwareInterface {
	protected $serviceLocator;
	protected $namespace;

	public function __construct($namespace = null) {
		parent::__construct($namespace);
		$this->namespace = $namespace;
	}
	 
	public function setDbHandler() {
		#$tableGateway = new TableGateway('TfwUserSession', $this->getServiceLocator()->get('TfwAuthDatabase'));
		$saveHandler = new DbTableGateway(new TableGateway($this->getServiceLocator()->get('UserSessTableName'), $this->getServiceLocator()->get('TfwAuthDatabase')), new DbTableGatewayOptions());
		//open session
		$sessionConfig = new SessionConfig();
		$saveHandler->open($sessionConfig->getOption('save_path'), $this->getNamespace());
		//set save handler with configured session
		$this->getSessionManager()->setSaveHandler($saveHandler);
		return $this;
	}

	public function write($contents) {
		parent::write($contents);
		/**
		 when $this->authService->authenticate(); is valid, the session
		 automatically called write('username')
		 in this case, i want to save data like
		 ["storage"] => array(4) {
		 ["id"] => string(1) "1"
		 ["username"] => string(5) "admin"
		 ["ip_address"] => string(9) "127.0.0.1"
		 ["user_agent"] => string(81) "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7;
		 rv:18.0)
		 Gecko/20100101    Firefox/18.0"
		}*/
		if (is_array($contents) && !empty($contents)) {
			$this->getSessionManager()->getSaveHandler()
				->write($this->getSessionId(), \Zend\Json\Json::encode($contents));
		}
	}

	public function clear() {
		$this->getSessionManager()->getSaveHandler()->destroy($this->getSessionId());
		parent::clear();
	}

	public function getSessionManager() { return $this->session->getManager(); }
	public function getSessionId() { return $this->session->getManager()->getId(); }
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) { $this->serviceLocator = $serviceLocator; }
	public function getServiceLocator() { return $this->serviceLocator; }

}