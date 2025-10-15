<?php
namespace MinifyJsCss\Helper;

use stdClass;
use Zend\View\Helper\HeadLink as ZendViewHelperHeadLink;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\Url;

/**
 * Zend_Layout_View_Helper_HeadLink
 *
 * @see http://www.w3.org/TR/xhtml1/dtds.html
 *
 * Creates the following virtual methods:
 * @method HeadLink appendStylesheet($href, $media, $conditionalStylesheet, $extras)
 * @method HeadLink offsetSetStylesheet($index, $href, $media, $conditionalStylesheet, $extras)
 * @method HeadLink prependStylesheet($href, $media, $conditionalStylesheet, $extras)
 * @method HeadLink setStylesheet($href, $media, $conditionalStylesheet, $extras)
 * @method HeadLink appendAlternate($href, $type, $title, $extras)
 * @method HeadLink offsetSetAlternate($index, $href, $type, $title, $extras)
 * @method HeadLink prependAlternate($href, $type, $title, $extras)
 * @method HeadLink setAlternate($href, $type, $title, $extras)
 */
class HeadLink extends ZendViewHelperHeadLink implements ServiceLocatorAwareInterface {

	protected $serviceLocator;
	public function setServiceLocator(ServiceLocatorInterface $sl){
		$this->serviceLocator=$sl;
		return $this;
	}
	public function getServiceLocator(){ return $this->serviceLocator; }

	private $urlInstance;
	public function setViewHelperUrl(Url $pUrl){
		$this->urlInstance=$pUrl;
		return $this;
	}

	protected $minifyUrlBase;
	public function setMinifyUrlBase($pUrlBase){
	    #die('@'.__LINE__.': '.__FILE__.' | $pUrlBase - '.$pUrlBase);
		$this->minifyUrlBase=$pUrlBase;
		return $this;
	}
	private function getMinifyUrlBase(){
		if(empty($this->minifyUrlBase)){
			if(isset($this->urlInstance)) return $this->urlInstance->__invoke('minify');
			return '/minify';
		}
		return $this->minifyUrlBase;
	}

/*	private $appBasePath;
	public function setAppBasePath($pAppBasePath){
		$this->appBasePath=$pAppBasePath;
		return $this;
	}
	private function getAppBasePath(){
		if(empty($this->appBasePath)){
			if(isset($this->urlInstance)) return $this->urlInstance->_invoke('home');
			return '/';
		}
		return $this->appBasePath;
	}*/

	private $sortDesc;
	public function sortDesc(){
		$this->sortDesc=true;
		return $this;
	}
	public function sortAsc(){
		$this->sortDesc=false;
		return $this;
	}
	private function isSortDesc(){
		if(!isset($this->sortDesc)) return false;
		return $this->sortDesc;
	}

	public function getInternalStylesheetArray(){
		$rtrn=array();
		$lnk2load=$this->getHeadLinks();
		foreach($lnk2load as $l){
			if($this->isInternalStylesheet((array) $l)) $rtrn[]=$this->parseItemEntity($l);
		}
		return $rtrn;
	}
	public function getStringForOtherThanInternalStylesheet($indent = null){
		$rtrn=array();
		$lnk2load=$this->getHeadLinks();
		foreach($lnk2load as $l){
			if(!$this->isInternalStylesheet((array) $l)) $rtrn[]=$l;
		}
		return $this->getArrayToString($rtrn, $indent);
	}
	private function getArrayToString(array $pArr, $indent = null){
		foreach ($pArr as $hdLnkX) $items[]=$this->itemToString($hdLnkX);
		#die('<pre>'.print_r($items, true));
		$indent = (null !== $indent)
			? $this->getWhitespace($indent)
			: $this->getIndent();
		return $indent . implode($this->escape($this->getSeparator()) . $indent, $items);
	}
	private function getHeadLinks(){
		$css2loadX=array();
		$this->ksort();
		foreach ($this as $hdLnkX) $css2loadX[]=$hdLnkX;
		#die('<pre>'.print_r($css2loadX, true));
		return ($this->isSortDesc()?array_reverse($css2loadX):$css2loadX);
	}
    public function toString($indent = null) {
    	$items = array();
    	$css2load=$this->getHeadLinks();
    	foreach ($css2load as $hdLnkX) $items[]=$this->parseItemEntity($hdLnkX);
    	return $this->getArrayToString($items, $indent);
    }
    public function ksort(){
    	$hdLinks=$this->getContainer();
    	if(method_exists($hdLinks, 'ksort')) $hdLinks->ksort();
    	return $this;
    }
    /**
     * need to make this method private
     * @param object $pItem
     * @return stdClass
     */
    public function parseItemEntity($pItem){
    	$attributes = (array) $pItem;
    	if($this->isInternalStylesheet($attributes)){
    		$extraAttribs=(array_key_exists('extras', $attributes)?$attributes['extras']:array());
    		$getValueFromAttribs=function($pKey) use ($extraAttribs) {
    			$src='';
    			foreach ($extraAttribs as $key => $value){
    				if($key==$pKey && !empty($value)){
    					$src=$value;
    					break;
    				}
    			}
    			return $src;
    		};
    		$doMinify=true;
    		$minify=$getValueFromAttribs('minify');
    		if(!empty($minify)){
    			unset($attributes['extras']['minify']);
    			#$item->cmdMinify=$minify;
    			if(preg_match('#\bignore\b#i', $minify)) $doMinify=false; # die('ignoring request found! @'.__LINE__.' ['.time().']: '.__FILE__);
    			#die('got minify attribs: '.$minify.' @'.__LINE__.': '.__FILE__);
    		}
    		#echo('<pre>'.print_r($attributes, true));
    		$attr_href=$attributes['href'];
    		if(true==$doMinify) $attributes['href']=$this->getMinifyUrlBase().'?css='.urlencode($attr_href);
    		return $this->createData($attributes);
    	}
    	return $pItem; #$this->itemToString($pItem);
    }
	private function isStylesheet(array $item){ return (isset($item['rel']) && 'stylesheet'==strtolower(trim($item['rel'])) && isset($item['href']) && strlen(trim($item['href']))>0); }
	private function isInternalStylesheet(array $item){
		if($this->isStylesheet($item)){
			$arrLeadToExternal=array('http://', 'https://', '//');
			foreach($arrLeadToExternal as $x){
				if(strpos(trim($item['href']), $x)===0) return false;
			}
			return true;
		}
		return false;
	}


}
