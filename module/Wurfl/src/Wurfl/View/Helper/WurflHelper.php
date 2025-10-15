<?php
namespace Wurfl\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use WMS\WMSConfig;

if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

class WurflHelper extends AbstractHelper implements ServiceLocatorAwareInterface {
	protected $wurflManager;
	protected $requestingDevice;
	protected $serviceLocator;
	public function setServiceLocator(ServiceLocatorInterface $sl){
		$this->serviceLocator=$sl;
		#die('@'.__LINE__.': '.__FILE__.'<pre>'.print_r($config, true));
		return $this;
	}
	private $storageLocation;
	private function setStorageLocation($pLocation){
		#if(!is_dir($pLocation)) throw new \Exception('wurfl storage location ['.$pLocation.'] not found &amp; unable to create');
		if(!is_dir($pLocation)) @mkdir($pLocation);
		clearstatcache();
		if(!is_dir($pLocation)) throw new \Exception('wurfl storage location ['.$pLocation.'] not found &amp; unable to create');
		$this->storageLocation=$pLocation;
		#die('is it? APP_ROOT_PATH: '.APP_ROOT_PATH.' ... '.WMSConfig::getWebServerTempLocation().' @'.__LINE__.': '.__FILE__);
		#if(!is_dir(WMSConfig::getWebServerTempLocation().'wurfl-storage')) @mkdir(WMSConfig::getWebServerTempLocation().'wurfl-storage');
		#if(is_dir(WMSConfig::getWebServerTempLocation().'wurfl-storage')) $storageDir=WMSConfig::getWebServerTempLocation().'wurfl-storage'.DS;
		return $this;
	}
	private function getStorageLocation(){
		if(!isset($this->storageLocation)){
			$config=$this->getServiceLocator()->get('Config');
			if(array_key_exists('wurfl', $config) && array_key_exists('locations', $config['wurfl']) && array_key_exists('storage', $config['wurfl']['locations'])) $this->setStorageLocation($config['wurfl']['locations']['storage']);
			else $this->setStorageLocation($this->getResourcesLocation().'storage'.DS);
		}
		return $this->storageLocation;
	}
	private $apiDirectory;
	private function getApiDirectory(){
		if(!isset($this->apiDirectory)){
			$wurflApiDir = dirname(__FILE__) . DS.'..'.DS.'..'.DS.'Api'.DS;
			if(function_exists('realpath')) $wurflApiDir=realpath($wurflApiDir).DS;
			$this->apiDirectory=$wurflApiDir;
		}
		return $this->apiDirectory;
	}
	private $resourcesLocation;
	private function getResourcesLocation(){
		if(!isset($this->resourcesLocation)){
			$this->resourcesLocation=$this->getApiDirectory().'resources'.DS;
			if(!is_dir($this->resourcesLocation)) @mkdir($this->resourcesLocation);
		}
		return $this->resourcesLocation;
	}
	public function getServiceLocator(){ return $this->serviceLocator; }
	public function setWurflManager(\WURFL_WURFLManager $pWurflMgr){
		$this->wurflManager=$pWurflMgr;
		return $this;
	}
	public function getWurflManager(){
		if(!isset($this->wurflManager)){
			$wurflApiDir = $this->getApiDirectory();
			$wurflDir = $wurflApiDir.'wurfl-php'.DS.'WURFL'.DS;
			$resourcesDir = $this->getResourcesLocation();
			require_once $wurflDir.'Application.php';
			$storageDir=$this->getStorageLocation();
			$persistenceDir = $storageDir.'persistence';
			if(!is_dir($persistenceDir)) @mkdir($persistenceDir);
			$cacheDir = $storageDir.'cache';
			if(!is_dir($cacheDir)) @mkdir($cacheDir);
			#die('$storageDir: '.$storageDir.' @'.__LINE__.': '.__FILE__);
			#die('WMSConfig::getWebServerTempLocation(): '.WMSConfig::getWebServerTempLocation().' | $cacheDir: '.$cacheDir.' | $persistenceDir: '.$persistenceDir.' @'.__LINE__.': '.__FILE__);
			// Create WURFL Configuration
			$wurflConfig = new \WURFL_Configuration_InMemoryConfig();
			// Set location of the WURFL File
			$wurflConfig->wurflFile($resourcesDir.'wurfl.zip');
			// Set the match mode for the API (\WURFL_Configuration_Config::MATCH_MODE_PERFORMANCE or \WURFL_Configuration_Config::MATCH_MODE_ACCURACY)
			$wurflConfig->matchMode(\WURFL_Configuration_Config::MATCH_MODE_ACCURACY);
			// Automatically reload the WURFL data if it changes
			$wurflConfig->allowReload(true);
			/*
			//	Optionally specify which capabilities should be loaded
			//  This is disabled by default as it would cause the demo/index.php
			//  page to fail due to missing capabilities
			# more info at http://wurfl.sourceforge.net/php_index.php
			$wurflConfig->capabilityFilter(array(
					'is_wireless_device',
					'preferred_markup',
					'xhtml_support_level',
					'xhtmlmp_preferred_mime_type',
					'device_os',
					'device_os_version',
					'is_tablet',
					'mobile_browser_version',
					'pointing_method',
					'mobile_browser',
					'resolution_width',
				));
			*/
			// Setup WURFL Persistence
			$wurflConfig->persistence('file', array('dir' => $persistenceDir));
			// Setup Caching
			$wurflConfig->cache('file', array('dir' => $cacheDir, 'expiration' => 36000));
			// Create a WURFL Manager Factory from the WURFL Configuration
			$wurflManagerFactory = new \WURFL_WURFLManagerFactory($wurflConfig);
			// Create a WURFL Manager
			/* @var $wurflManager WURFL_WURFLManager */
			$this->setWurflManager($wurflManagerFactory->create());
		}
		return $this->wurflManager;
	}
	public function getWURFLInfo(){
		if(isset($this->wurflManager)) return $this->wurflManager->getWURFLInfo();
		return ;
	}
	/**
	 * @return array
	 */
	public function getDeviceInfoToDebug(){
		
	}
	public function getDeviceForUserAgent($pUa){
		if(isset($this->wurflManager) && !empty($pUa)) return $this->wurflManager->getDeviceForUserAgent($pUa);
		return ;
	}
	public function getDeviceForHttpRequest(){
		if(isset($this->wurflManager)){
			# This line detects the visiting device by looking at its HTTP Request ($_SERVER)
			if(!isset($this->requestingDevice)) $this->setRequestingDevice($this->wurflManager->getDeviceForHttpRequest($_SERVER));
			return $this->requestingDevice;
		}
		return ;
	}
	protected function setRequestingDevice(\WURFL_CustomDevice $pModelDevice){
		$this->requestingDevice=$pModelDevice;
		return $this;
	}
	public function getRequestingDevice(){ return $this->getDeviceForHttpRequest(); }
	/* xhtml_support_level possible values:
	 * -1: No XHTML Support
	*  0: Poor XHTML Support
	*  1: Basic XHTML with Basic CSS Support
	*  2: Same as Level 1
	*  3: XHTML Support with Excellent CSS Support
	*  4: Level 3 + AJAX Support
	*/
	public function isWmlOnly(){
		$xhtml_lvl = $this->getCapability('xhtml_support_level');
		return ($xhtml_lvl < 1);
	}
	public function isXhtmlCompatible(){ return !$this->isWmlOnly(); }
	public function isAjaxSupported(){
		$xhtml_lvl = $this->getCapability('xhtml_support_level');
		return ($xhtml_lvl >= 4);
	}
	public function getPreferredContentType(){ return $this->getCapability('xhtmlmp_preferred_mime_type'); }
	private $capabilitiesCache;
	public function getCapability($pCapability){
		if(!isset($this->capabilitiesCache)) $this->capabilitiesCache=array();
		if(!array_key_exists($pCapability, $this->capabilitiesCache)){
			$this->getDeviceForHttpRequest();
			$this->capabilitiesCache[$pCapability]=$this->requestingDevice->getCapability($pCapability);
		}
		return $this->capabilitiesCache[$pCapability];
	}
	public function isWidthNotDetected(){ return intval($this->getCapability('resolution_width')) <= 0; }
	public function getResolutionWidth(){ return intval($this->getCapability('resolution_width')); }
	public function checkMaxWidth($pMaxWidth, $pStrict=false){
		$fWidth=$this->getResolutionWidth();
		if(true==$pStrict) return ($fWidth<=$pMaxWidth);
		return ($fWidth<=$pMaxWidth || $fWidth<=0);
	}
	public function checkMinWidth($pMinWidth, $pStrict=false){
		$fWidth=$this->getResolutionWidth();
		if(true==$pStrict) return ($fWidth>$pMinWidth);
		return ($fWidth>=$pMinWidth || $fWidth<=0);
	}
}

