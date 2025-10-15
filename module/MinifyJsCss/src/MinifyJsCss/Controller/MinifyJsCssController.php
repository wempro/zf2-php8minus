<?php
namespace MinifyJsCss\Controller;

use Lib3rdParty\Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\PhpEnvironment\Response;

if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
class MinifyJsCssController extends AbstractActionController {
	private $appHttpLocation;
	private function getAppHttpLocation(){
	    if(!isset($this->appHttpLocation)){
	        $appHttpLoc=$this->getServiceLocator()->get('minifyJsCssHttpLocation');
	        $this->appHttpLocation=rtrim((is_object($appHttpLoc)?(is_callable($appHttpLoc)?$appHttpLoc():strval($appHttpLoc)):strval($appHttpLoc)), DS).DS;
	    }
		return $this->appHttpLocation;
	}
    public function riAction(){
        $flNameToReturn=$imgLoc=null;
        $imgParams=$this->getImageParams();
        $b64encoded=(array_key_exists('b64encoded', $imgParams)?$imgParams['b64encoded']:null);
        if(is_null($b64encoded)){
            $paramsFromRoute=$this->params()->fromRoute();
            unset($paramsFromRoute['controller'], $paramsFromRoute['action']);
            $locationFromBasePath=$this->getLocationClosure();
            $imgLoc=$locationFromBasePath(implode('/', $paramsFromRoute));
            $flNameToReturn=trim(array_pop($paramsFromRoute), '.');
        }
        if($this->handleCache(md5($imgParams['identifier'].$imgLoc))) return $this->getResponse();
        if(!is_null($imgLoc)){
            if(!file_exists($imgLoc)) throw new \Exception('file '.$imgLoc.' NOT found!');
            $b64encoded=base64_encode(file_get_contents($imgLoc));
        }
        #die('@'.__LINE__.': '.$imgLoc.'<pre>'.print_r(array($appHttpLctn, $paramsFromRoute, $imgParams), true));
        if(empty($b64encoded)) throw new \Exception('no encoded string provided!!!');
        if(!empty($flNameToReturn)){
            $dotPos=strrpos($flNameToReturn, '.');
            if(false!==$dotPos) $flNameToReturn=substr($flNameToReturn, 0, $dotPos);
        }
        $flNameToReturn=(empty($flNameToReturn)?'img-'.$imgParams['identifier']:'copy of '.$flNameToReturn);
        $this->setDownloadableFileDefaultResponseHeader($flNameToReturn, $imgParams['ext']);
        return $this->getResponse()->setContent(\ProjectCore\Helper\Image::getImgFromString($b64encoded, $imgParams['dimension']['w'], $imgParams['dimension']['h'], (false===$imgParams['dimension']['resizeContainer']), $imgParams['alpha'])->get('jpg', array('jpeg_quality'=>80)));
    }
    private function getLocationClosure(){
        $basePath=$this->getRequest()->getBasePath();
        if(empty($basePath)) $basePath='/';
        $appHttpLctn=$this->getAppHttpLocation();
        #die('$appHttpLctn: '.$appHttpLctn.' | $basePath: '.$basePath);
        return function($pFile) use ($basePath, $appHttpLctn){
            $iFile=DS.ltrim(implode(DS, explode('/', $pFile)), DS);
            $l2r=$appHttpLctn.DS.$iFile;
            #die('$flLoc: '.$flLoc.' | $basePath: '.$basePath);
            if($basePath!='/'){
                $pf=substr($iFile, 0, strlen($basePath));
                #echo '$basePath: '.$basePath.' | $pf: '.$pf.'<br />';
                $l2r=$appHttpLctn.($pf==$basePath?substr($iFile, strlen($basePath)):DS.ltrim($iFile, DS));
            }
            #die('$pFile: '.$iFile.' | $l2r: '.$l2r);
            return $l2r;
        };
    }
	public function indexAction(){
	    #die('@'.__LINE__.': '.__FILE__.'<pre>'.print_r($this->getRequest()->getHeaders()->toArray(), true));
		#die('$basePath = $this->getRequest()->getBasePath();: '.$this->getRequest()->getBasePath());
		$locationFromBasePath=$this->getLocationClosure();
		#die('$basePath: '.$basePath);
		$response=$this->getResponse();
		$getQuery=$this->getRequest()->getUri()->getQueryAsArray();
		#$appHttpLctn=$this->getAppHttpLocation();
		#die('$appHttpLctn: '.$appHttpLctn);
		$fThis=$this;
		$getResponseByReadingFile=function($pQryVar, $pContentType) use ($fThis, $response, $getQuery, $locationFromBasePath){
			$errCssFiles=$cssFiles=array();
                        if($fThis->handleCache(md5($getQuery[$pQryVar]))) return $response;
			$cssFileCsv=explode(',', $getQuery[$pQryVar]);
			$maxFlTime=0;
			#$appHttpLctnX=rtrim($appHttpLctn, DS);
			foreach($cssFileCsv as $css){
				#echo 'ltrim(trim($css), $basePath): '.str_replace(array('/', '\\'), DS, ltrim(trim($css), $basePath)).'<br />';
				$css=trim(str_replace(array('/', '\\'), DS, $css), DS);
				$flLoc=$locationFromBasePath($css);
				#die('$flLoc: '.$flLoc.' | $basePath: '.$basePath);
//				if($basePath!='/'){
//					$pf=substr($css, 0, strlen($basePath));
//					#echo '$basePath: '.$basePath.' | $pf: '.$pf.'<br />';
//					$flLoc=$appHttpLctnX.DS.($pf==$basePath?substr($css, strlen($basePath)):$css);
//				}
				#$flLoc=$appHttpLctn.str_replace(array('/', '\\'), DS, ltrim(trim($css), $basePath));
				if(file_exists($flLoc)){
					$flTime=filemtime($flLoc);
					$cssFiles[md5($flLoc)]=array('location'=>str_replace(array('/', '\\'), DS, $flLoc), 'mtime'=>$flTime);
					if($maxFlTime<$flTime) $maxFlTime=$flTime;
				}else $errCssFiles[]=$flLoc;
			}
			#die('$$appHttpLctn: '.$appHttpLctn.'<pre>'.print_r(array($cssFiles, $errCssFiles), true));
			if(!empty($cssFiles)){
				ksort($cssFiles);
				$eTag=md5(implode(',', array_keys($cssFiles)));
				if(!$fThis->handleCache($eTag)){
					$flContents='';
					foreach($cssFiles as $cssFl) $flContents.='/* location: '.$cssFl['location'].' */'.file_get_contents($cssFl['location']);
					$response->getHeaders()
							->addHeaderLine('Content-Transfer-Encoding', 'binary')
							->addHeaderLine('Content-Type', $pContentType)
						;
					$response->setContent('/* '.$pQryVar.' file from minify. time: '.time().' */'.preg_replace('#\s+#', ' ', $flContents));
				}
			}else{
				$response->getHeaders()
						->addHeaderLine('Content-Transfer-Encoding', 'binary')
						->addHeaderLine('Content-Type', $pContentType)
						->addHeaderLine('Expires', '')
						->addHeaderLine('Cache-Control', 'public')
						->addHeaderLine('Cache-Control', 'max-age=1800', true)
						->addHeaderLine('Pragma', '')
					;
				$response->setContent('/* blank '.$pQryVar.' file from minify! time: '.time().' ['.$getQuery[$pQryVar].'] locations - '.implode(' | ', $errCssFiles).' */');
			}
			return $response;
		};
		if(array_key_exists('css', $getQuery)) return $getResponseByReadingFile('css', 'text/css');
		if(array_key_exists('js', $getQuery)) return $getResponseByReadingFile('js', 'text/javascript');
		if($this->handleCache(md5(__FILE__))){
			#die('sent 304');
			return $response;
		}
		$response->setContent('zf2 js and css minify project. cur time: '.time());
		return $response;
	}
	private function workingIndexCode(){
		$getQuery=array();
		$basePath=$appHttpLctn='';
		$response=$this->getResponse();
		if(array_key_exists('css', $getQuery)){
			$errCssFiles=$cssFiles=array();
			$cssFileCsv=explode(',', $getQuery['css']);
			$maxFlTime=0;
			foreach($cssFileCsv as $css){
				$css=rtrim($css, '/');
				$css=rtrim($css, '\\');
				$flLoc=$appHttpLctn.ltrim($css, '/');
				if($basePath!='/') $flLoc=$appHttpLctn.substr($css, strlen($basePath)+1);
				if(file_exists($flLoc)){
					$flTime=filemtime($flLoc);
					$cssFiles[$flLoc]=array('location'=>str_replace(array('/', '\\'), DS, $flLoc), 'mtime'=>$flTime);
					if($maxFlTime<$flTime) $maxFlTime=$flTime;
				}else $errCssFiles[]=$flLoc;
			}
			#die('$$appHttpLctn: '.$appHttpLctn.'<pre>'.print_r(array($basePath, $cssFiles, $errCssFiles), true));
			if(!empty($cssFiles)){
				ksort($cssFiles);
				$eTag=md5(implode(',', array_keys($cssFiles)));
				if(!$this->handleCache($eTag)){
					$flContents='';
					foreach($cssFiles as $cssFl) $flContents.='/* location: '.$cssFl['location'].' */'.file_get_contents($cssFl['location']);
					$response->getHeaders()
							->addHeaderLine('Content-Transfer-Encoding', 'binary')
							->addHeaderLine('Content-Type', 'text/css')
						;
					$response->setContent('/* css file from minify. time: '.time().' */'.preg_replace('#\s+#', ' ', $flContents));
				}
			}else{
				$response->getHeaders()
						->addHeaderLine('Content-Transfer-Encoding', 'binary')
						->addHeaderLine('Content-Type', 'text/css')
						->addHeaderLine('Expires', '')
						->addHeaderLine('Cache-Control', 'public')
						->addHeaderLine('Cache-Control', 'max-age=1800', true)
						->addHeaderLine('Pragma', '')
					;
				$response->setContent('/* blank css file from minify! time: '.time().' ['.$getQuery['css'].'] locations - '.implode(' | ', $errCssFiles).' */');
			}
			return $response;
		}
		if(array_key_exists('js', $getQuery)){
			$jsFiles=array();
			$cssFileCsv=explode(',', $getQuery['js']);
			$maxFlTime=0;
			foreach($cssFileCsv as $css){
				$css=rtrim($css, '/');
				$css=rtrim($css, '\\');
				$flLoc=$appHttpLctn.ltrim($css, '/');
				if($basePath!='/') $flLoc=$appHttpLctn.substr($css, strlen($basePath)+1);
				#$flLoc=$appHttpLctn.ltrim(trim($css), $basePath);
				if(file_exists($flLoc)){
					$flTime=filemtime($flLoc);
					$jsFiles[$flLoc]=array('location'=>str_replace(array('/', '\\'), DS, $flLoc), 'mtime'=>$flTime);
					if($maxFlTime<$flTime) $maxFlTime=$flTime;
				}
			}
			#die('$$appHttpLctn: '.$appHttpLctn.'<pre>'.print_r($cssFiles, true));
			if(!empty($jsFiles)){
				ksort($jsFiles);
				$eTag=md5(implode(',', array_keys($jsFiles)));
				if(!$this->handleCache($eTag)){
					$flContents='';
					foreach($jsFiles as $cssFl) $flContents.='/* location: '.$cssFl['location'].' */'.file_get_contents($cssFl['location']);
					$response->getHeaders()
							->addHeaderLine('Content-Transfer-Encoding', 'binary')
							->addHeaderLine('Content-Type', 'text/javascript')
						;
					$response->setContent('/* js file from minify. time: '.time().' */'.preg_replace('#\s+#', ' ', $flContents));
				}
			}else{
				$response->getHeaders()
						->addHeaderLine('Content-Transfer-Encoding', 'binary')
						->addHeaderLine('Content-Type', 'text/javascript')
						->addHeaderLine('Expires', '')
						->addHeaderLine('Cache-Control', 'public')
						->addHeaderLine('Cache-Control', 'max-age=1800', true)
						->addHeaderLine('Pragma', '')
					;
				$response->setContent('/* blank js file from minify. time: '.time().' ['.$getQuery['js'].'] */');
			}
			return $response;
		}
	}
	
}

