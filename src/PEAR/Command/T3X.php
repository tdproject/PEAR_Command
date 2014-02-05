<?php

/**
 * PEAR_Command_Mage
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

require_once 'PEAR/Command/Common.php';
require_once 'PEAR/Command/T3X/class.t3lib_div.php';
require_once 'PEAR/Command/T3X/class.t3lib_scbase.php';
require_once 'PEAR/Command/T3X/class.em_index.php';
require_once 'PEAR/Command/T3X/class.em_terconnection.php';

/**
 * @category   	PEAR
 * @package    	PEAR_Command
 * @copyright  	Copyright (c) 2009 <info@techdivision.com> - TechDivision GmbH
 * @license    	http://opensource.org/licenses/osl-3.0.php
 * 				Open Software License (OSL 3.0)
 * @author      TechDivision GmbH - Core Team <info@techdivision.com>
 */
class PEAR_Command_T3X extends PEAR_Command_Common
{

    /**
     * The commands implemented in this class.
     * @var array
     */
    public $commands = array(
        't3x-create' => array(
            'summary' => 'Generates a valid t3x-file',
            'function' => 'createT3X',
            'shortcut' => 'tcr',
            'options' => array(
                'extkey' => array(
                    'shortopt' => 'E',
                    'doc' => 'The key of the TYPO3 Extension',
                    'arg' => 'EXTKEY',
                ),
                'inputpath' => array(
                    'shortopt' => 'I',
                    'doc' => 'The input folder which will be parsed',
                    'arg' => 'INPUT',
                ),
                'outputpath' => array(
                    'shortopt' => 'O',
                    'doc' => 'The output folder where the t3x-file will be saved',
                    'arg' => 'OUTPUT',
                ),
            ),
           	'doc' => '[descfile] Creates a a valid t3x-file from a sourcefolder'
        ),
        't3x-update-emconf' => array(
            'summary' => 'Updates the em_conf.php from an extension',
            'function' => 'updateEmConf',
            'shortcut' => 'tue',
            'options' => array(
                'extkey' => array(
                    'shortopt' => 'E',
                    'doc' => 'The key of the TYPO3 Extension',
                    'arg' => 'EXTKEY',
                ),
                'path' => array(
                    'shortopt' => 'P',
                    'doc' => 'The folder where the em_conf.php resides',
                    'arg' => 'PATH',
                ),
            ),
            'doc' => '[descfile] Updates the em_conf.php of an extension'
        ),
    );

    /**
     * The options passed from the command line when excecuting the command.
     * @var array
     */
    private $options;


    /**
     * The constructor to initialize the
     * Command with.
     *
     * @param $ui
     * @param $config
     * @return void
     */
    public function PEAR_Command_T3X(&$ui, &$config)
    {
        parent::PEAR_Command_Common($ui, $config);
    }

    /**
     * The method automatically invoked when running the
     * pear with the 't3x-create' command.
     *
     * @param $command
     * @param $options
     * @param $params
     * @return boolean
     */
    public function createT3X($command, $options, $params)
    {
    	$this->options = $options;
    	// check all options
        if (!isset($this->options['extkey'])) {
        	$result = new PEAR_Error('No ExtKEY given. Please use option -E');
        	return $result;
        }
        if (!isset($this->options['inputpath'])) {
        	$result = new PEAR_Error('No inputpath given. Please use option -I');
        	return $result;
        }
        if (!isset($this->options['outputpath'])) {
        	$result = new PEAR_Error('No outputpath given. Please use option -O');
        	return $result;
        }

        // set vars by options
    	$extKey = $this->options['extkey'];
        $inputpath = $this->options['inputpath'];
        $outputpath = $this->options['outputpath'];
    	
        // add slashes to our dirs if there are none
        if (substr($inputpath, -1) != "/") $inputpath .= "/";
        if (substr($outputpath, -1) != "/") $outputpath .= "/";
        
    	$SOBE = t3lib_div::makeInstance('SC_mod_tools_em_index');
		$SOBE->init();
		$SOBE->path = $inputpath;
		$SOBE->outPath = $outputpath;
		$SOBE->checkExtObj();
		
		list($list,)=$SOBE->getInstalledExtensions();
		$SOBE->updateLocalEM_CONF($extKey, $list[$extKey]);
		$SOBE->extBackup($extKey, $list[$extKey]);

        return true;
    }
    
    /**
     * The method automatically invoked when running the
     * pear with the 't3x-update-emconf' command.
     *
     * @param $command
     * @param $options
     * @param $params
     * @return boolean
     */
    public function updateEmConf($command, $options, $params)
    {


    	// check all options
        if (!isset($params[0])) {
        	$result = new PEAR_Error('No ExtKEY given. Please use option -E');
        	return $result;
        }
        if (!isset($params[1])) {
        	$result = new PEAR_Error('No Path given. Please use option -I');
        	return $result;
        }
        
    	$extKey = $params[0];
        $inputpath = $params[1];
    	
        // add slashes to our dirs if there are none
        if (substr($inputpath, -1) != "/") $inputpath .= "/";
        
    	$SOBE = t3lib_div::makeInstance('SC_mod_tools_em_index');
		$SOBE->init();
		$SOBE->path = $inputpath;
		$SOBE->checkExtObj();
		
		list($list,)=$SOBE->getInstalledExtensions();
		$SOBE->updateLocalEM_CONF($extKey, $list[$extKey]);

        return true;
    }
    
}