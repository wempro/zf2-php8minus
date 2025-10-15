<?php
namespace MinifyJsCss\Helper;

use stdClass;
use Zend\View\Helper\HeadStyle as ZendViewHelperHeadStyle;

/**
 * Helper for setting and retrieving stylesheets
 *
 * Allows the following method calls:
 * @method HeadStyle appendStyle($content, $attributes = array())
 * @method HeadStyle offsetSetStyle($index, $content, $attributes = array())
 * @method HeadStyle prependStyle($content, $attributes = array())
 * @method HeadStyle setStyle($content, $attributes = array())
 */
class HeadStyle extends ZendViewHelperHeadStyle {

	public function createData($content, array $attributes){
		$getValueFromAttribs=function($pKey) use ($attributes) {
			$src='';
			foreach ($attributes as $key => $value){
				if($key==$pKey && !empty($value)){
					$src=$value;
					break;
				}
			}
			return $src;
		};
		$doMinify=true;
		$minify=$getValueFromAttribs('minify');
		$d2r=parent::createData($content, $attributes);
		$d2r->cmdMinify='';
		if(!empty($minify)){
			unset($attributes['minify']);
			$d2r->cmdMinify=$minify;
			if(preg_match('#\bignore\b#i', $minify)) $doMinify=false; # die('ignoring request found! @'.__LINE__.' ['.time().']: '.__FILE__);
			#die('got minify attribs: '.$minify.' @'.__LINE__.': '.__FILE__);
		}
		if(false==$doMinify) return $d2r;
		$d2r->sourceOrgi=$d2r->content;
		if(!empty($d2r->content)) $d2r->content=preg_replace('#\s+#', ' ', $d2r->sourceOrgi);
		return $d2r;
	}

}
