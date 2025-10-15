<?php
namespace AuthV2\Factory\Storage;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\TableGateway\TableGateway;

class AuthStorageFactory implements FactoryInterface {
	public function createService(ServiceLocatorInterface $serviceLocator) {
            #$sess=$serviceLocator->get('Zend\\Session\\SessionManager');
		return new \Lib3rdParty\Zend\Authentication\Storage\Session('TfwAuthStorage', null, $serviceLocator->get('Zend\\Session\\SessionManager'));
		try {
			return new \Lib3rdParty\Zend\Authentication\Storage\Session('TfwAuthStorage', null, $serviceLocator->get('Zend\\Session\\SessionManager'));
            #            $storage->setSessionTableGateway(new TableGateway($serviceLocator->get('UserSessTableName'), $serviceLocator->get('TfwAuthDatabase')));
			#return $storage;
		} catch (\Lib3rdParty\Helper\Zend\Session\Exception\InvalidSessionException $e) {
			#die('@'.__LINE__.': '.__FILE__.'<br />'.PHP_EOL.get_class($e).': '.$e->getMessage().'<pre>'.print_r(array('old'=>$e->getSessionValidationOldData(), 'new'=>$e->getSessionValidationNewData()), true));
		} catch (\Exception $e) {
			#die('@'.__LINE__.': '.__FILE__.'<br />'.PHP_EOL.get_class($e).': '.$e->getMessage().'<pre>'.$e->getTraceAsString());
		}
		#return new \Lib3rdParty\Zend\Authentication\Storage\Session('TfwAuthStorage');
		#$storage->setSessionTableGateway(new TableGateway($serviceLocator->get('UserSessTableName'), $serviceLocator->get('TfwAuthDatabase')));
		#return $storage;
	}
}

