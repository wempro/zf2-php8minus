<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ModuleManager\Listener;

use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleEvent;

/**
 * Init trigger
 */
class InitTrigger extends AbstractListener
{
    /**
     * @param ModuleEvent $e
     * @return void
     */
    public function __invoke(ModuleEvent $e)
    {
        $module = $e->getModule();
        if (!$module instanceof InitProviderInterface
            && !method_exists($module, 'init')
        ) {
            return;
        }
        /*
        $bkTrace=function ($stack) {
        	$output = '';
        
        	$stackLen = count($stack);
        	for ($i = 1; $i < $stackLen; $i++) {
        		$entry = $stack[$i];
        
        		$func = (array_key_exists('class', $entry)?$entry['class'].'\\':'').$entry['function'] . '(';
        		$argsLen = count($entry['args']);
        		for ($j = 0; $j < $argsLen; $j++) {
        			$my_entry = $entry['args'][$j];
        			if (is_string($my_entry)) {
        				$func .= $my_entry;
        			}
        			if ($j < $argsLen - 1) $func .= ', ';
        		}
        		$func .= ')';
        
        		$entry_file = 'NO_FILE';
        		if (array_key_exists('file', $entry)) {
        			$entry_file = $entry['file'];
        		}
        		$entry_line = 'NO_LINE';
        		if (array_key_exists('line', $entry)) {
        			$entry_line = $entry['line'];
        		}
        		$output .= $entry_file . ':' . $entry_line . ' - ' . $func . PHP_EOL;
        	}
        	return $output;
        };
        echo '$module: '.get_class($module).'<pre>'.$bkTrace(debug_backtrace()); exit();
        #*/
        $module->init($e->getTarget());
    }
}
