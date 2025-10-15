<?php
namespace MinifyJsCss\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\Url;
use Lib3rdParty\Helper\Zf2\Mvc\Controller\AbstractBaseController;

class ResizeImageHelper extends \Zend\View\Helper\AbstractHelper implements ServiceLocatorAwareInterface {

	protected $serviceLocator;
	public function setServiceLocator(ServiceLocatorInterface $sl){
		$this->serviceLocator=$sl;
		return $this;
	}
	public function getServiceLocator(){ return $this->serviceLocator; }

//	private $urlInstance;
//	public function setViewHelperUrl(Url $pUrl){
//		$this->urlInstance=$pUrl;
//		return $this;
//	}

	protected $minifyUrlBase;
	public function setResizeImageUrlBase($pUrlBase){
		$this->minifyUrlBase=$pUrlBase;
		return $this;
	}
//	private function getMinifyUrlBase(){
//		if(empty($this->minifyUrlBase)){
//			if(isset($this->urlInstance)) return $this->urlInstance->__invoke('minify');
//			return '/minify';
//		}
//		return $this->minifyUrlBase;
//	}

        public function __invoke($pImgPath, $pWidth=0, $pHeight=0, $pTrim=null, $pTransparent=null, $pQuality=80, $pB64Encoded=false){
            if(empty($pImgPath)) throw new \Exception('path not provided!');
            if(is_null($pWidth)) $pWidth=0;
            if(is_null($pHeight)) $pHeight=0;
            if(is_null($pTransparent)) $pTransparent=0;
            if(false===$pB64Encoded){
	            $qp=$qpX=array();
	            if(!empty($pWidth)) $qp['w']=$pWidth;
	            if(!empty($pHeight)) $qp['h']=$pHeight;
	            if(!is_null($pTrim)) $qp['trim']=(false===$pTrim?'no':'yes');
	            if(!empty($pTransparent)) $qp['alpha']=$pTransparent;
	            if(!empty($qp)){
	                foreach($qp as $k=>$v) $qpX[]=$k.'='.urlencode($v);
	            }
	            return $this->minifyUrlBase.'/'.trim($pImgPath, '/').(!empty($qpX)?'?'.implode('&', $qpX):'');
            }
            if(false==file_exists($pImgPath)) throw new \Exception('image NOT found!!!');
            $imgParams=AbstractBaseController::getImageParamsStatic($pWidth, $pHeight, $pTrim, $pTransparent);
            return base64_encode(\ProjectCore\Helper\Image::getImgFromString(base64_encode(file_get_contents($pImgPath)), $imgParams['dimension']['w'], $imgParams['dimension']['h'], (false===$imgParams['dimension']['resizeContainer']), $imgParams['alpha'])->get((is_null($pTransparent)?'jpg':'png'), array('jpeg_quality'=>$pQuality)));
        }

}
