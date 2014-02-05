<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2005-2009 Karsten Dambekalns <karsten@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module: Extension manager
 *
 * $Id: class.em_index.php 6766 2010-01-13 23:53:50Z steffenk $
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <karsten@typo3.org>
 */
/**
 *
 */

	// Include classes needed:

require_once('class.em_terconnection.php');



	// from tx_ter by Robert Lemke
define('TX_TER_RESULT_EXTENSIONSUCCESSFULLYUPLOADED', '10504');

define('EM_INSTALL_VERSION_MIN', 1);
define('EM_INSTALL_VERSION_MAX', 2);
define('EM_INSTALL_VERSION_STRICT', 3);

/**
 * Module: Extension manager
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <karsten@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_tools_em_index extends t3lib_SCbase {

		// Internal, static:
	var $versionDiffFactor = 1;		// This means that version difference testing for import is detected for sub-versions only, not dev-versions. Default: 1000
	var $systemInstall = 0;				// If "1" then installs in the sysext directory is allowed. Default: 0
	var $requiredExt = '';				// List of required extension (from TYPO3_CONF_VARS)
	var $maxUploadSize = 31457280;		// Max size in bytes of extension upload to repository
	var $kbMax = 500;					// Max size in kilobytes for files to be edited.
	var $doPrintContent = true;			// If set (default), the function printContent() will echo the content which was collected in $this->content. You can set this to FALSE in order to echo content from elsewhere, fx. when using outbut buffering
	var $listingLimit = 500;		// List that many extension maximally at one time (fixing memory problems)
	var $listingLimitAuthor = 250;		// List that many extension maximally at one time (fixing memory problems)

	/**
	 * Internal variable loaded with extension categories (for display/listing). Should reflect $categories above
	 * Dynamic var.
	 */
	var $defaultCategories = Array(
		'cat' => Array (
			'be' => array(),
			'module' => array(),
			'fe' => array(),
			'plugin' => array(),
			'misc' => array(),
			'services' => array(),
			'templates' => array(),
			'example' => array(),
			'doc' => array()
		)
	);

	var $categories = array();			// Extension Categories (static var); see init()

	var $states = array();				// Extension States; see init()

	/**
	 * "TYPE" information; labels, paths, description etc. See init()
	 */
	var $typeLabels = array();
	var $typeDescr = array();
	var $typePaths = Array();			// Also static, set in init()
	var $typeBackPaths = Array();		// Also static, set in init()

	var $typeRelPaths = Array (
		'S' => 'sysext/',
		'G' => 'ext/',
		'L' => '../typo3conf/ext/',
	);

	var $detailCols = Array (
		0 => 2,
		1 => 5,
		2 => 6,
		3 => 6,
		4 => 4,
		5 => 1
	);

	var $fe_user = array(
		'username' => '',
		'password' => '',
	);

	var $privacyNotice;					// Set in init()
	var $securityHint;					// Set in init()
	var $editTextExtensions = 'html,htm,txt,css,tmpl,inc,php,sql,conf,cnf,pl,pm,sh,xml,ChangeLog';
	var $nameSpaceExceptions = 'beuser_tracking,design_components,impexp,static_file_edit,cms,freesite,quickhelp,classic_welcome,indexed_search,sys_action,sys_workflows,sys_todos,sys_messages,direct_mail,sys_stat,tt_address,tt_board,tt_calender,tt_guest,tt_links,tt_news,tt_poll,tt_rating,tt_products,setup,taskcenter,tsconfig_help,context_help,sys_note,tstemplate,lowlevel,install,belog,beuser,phpmyadmin,aboutmodules,imagelist,setup,taskcenter,sys_notepad,viewpage,adodb';





		// Default variables for backend modules
	var $MCONF = array();				// Module configuration
	var $MOD_MENU = array();			// Module menu items
	var $MOD_SETTINGS = array();		// Module session settings
	/**
	 * Document Template Object
	 *
	 * @var noDoc
	 */
	var $doc;
	var $content;						// Accumulated content

	var $inst_keys = array();			// Storage of installed extensions
	var $gzcompress = 0;				// Is set true, if system support compression.

	/**
	 * instance of TER connection handler
	 *
	 * @var SC_mod_tools_em_terconnection
	 */
	var $terConnection;

	/**
	 * XML handling class for the TYPO3 Extension Manager
	 *
	 * @var SC_mod_tools_em_xmlhandler
	 */
	var $xmlhandler;
	var $JScode;						// JavaScript code to be forwared to $this->doc->JScode

		// GPvars:
	var $CMD = array();					// CMD array
	var $listRemote;					// If set, connects to remote repository
	var $lookUpStr;						// Search string when listing local extensions




	/*********************************
	*
	* Standard module initialization
	*
	*********************************/

	/**
	 * Standard init function of a module.
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TYPO3_CONF_VARS;

		

			// Setting paths of install scopes:
		$this->typePaths = Array (
			'S' => 'sysext/',
			'G' => 'ext/',
			'L' => 'typo3conf/ext/'
		);
		$this->typeBackPaths = Array (
			'S' => '../../../',
			'G' => '../../../',
			'L' => '../../../../'
		);

		$this->excludeForPackaging = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];

			// Setting GPvars:
		$this->CMD = is_array(t3lib_div::_GP('CMD')) ? t3lib_div::_GP('CMD') : array();
		$this->lookUpStr = trim(t3lib_div::_GP('lookUp'));
		$this->listRemote = t3lib_div::_GP('ter_connect');
		$this->listRemote_search = trim(t3lib_div::_GP('ter_search'));

			// Setting internal static:
		if ($TYPO3_CONF_VARS['EXT']['allowSystemInstall'])	$this->systemInstall = 1;
		$this->requiredExt = t3lib_div::trimExplode(',',$TYPO3_CONF_VARS['EXT']['requiredExt'],1);

	}

	/**
	 * This function is a copy of the same function in t3lib_SCbase with one modification:
	 * In contrast to t3lib_SCbase::handleExternalFunctionValue() this function merges the $this->extClassConf array
	 * instead of overwriting it. That was necessary for including the Kickstarter as a submodule into the 'singleDetails'
	 * selectorbox as well as in the main 'function' selectorbox.
	 *
	 * @param	string		Mod-setting array key
	 * @param	string		Mod setting value, overriding the one in the key
	 * @return	void
	 * @see t3lib_SCbase::handleExternalFunctionValue()
	 */
	function handleExternalFunctionValue($MM_key='function', $MS_value=NULL)	{
		$MS_value = is_null($MS_value) ? $this->MOD_SETTINGS[$MM_key] : $MS_value;
		$externalItems = $this->getExternalItemConfig($this->MCONF['name'],$MM_key,$MS_value);
		if (is_array($externalItems))	$this->extClassConf = array_merge($externalItems,is_array($this->extClassConf)?$this->extClassConf:array());
		if (is_array($this->extClassConf) && $this->extClassConf['path'])	{
			$this->include_once[]=$this->extClassConf['path'];
		}
	}

	/**
	 * Download extension as file / make backup
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content
	 */
	function extBackup($extKey,$extInfo)	{
		$uArr = $this->makeUploadArray($extKey,$extInfo);
		if (is_array($uArr))	{
			$this->terConnection = t3lib_div::makeInstance('SC_mod_tools_em_terconnection');
			$backUpData = $this->terConnection->makeUploadDataFromArray($uArr);
			$filename = 'T3X_'.$extKey.'-'.str_replace('.','_',$extInfo['EM_CONF']['version']).'-z-'.date('YmdHi').'.t3x';
			$fp = fopen($this->outPath . $filename, 'w+');
			fwrite($fp, $backUpData);
			fclose($fp);
			echo "successfully created T3X-Package";
			exit;
		} else die('error');
	}

	
	/**
	 * Checks whether the passed dependency is TER2-style (array) and returns a single string for displaying the dependencies.
	 *
	 * It leaves out all version numbers and the "php" and "typo3" dependencies, as they are implicit and of no interest without the version number.
	 *
	 * @param	mixed		$dep Either a string or an array listing dependencies.
	 * @param	string		$type The dependency type to list if $dep is an array
	 * @return	string		A simple dependency list for display
	 */
	function depToString($dep,$type='depends') {
		if(is_array($dep)) {
			unset($dep[$type]['php']);
			unset($dep[$type]['typo3']);
			$s = (count($dep[$type])) ? implode(',', array_keys($dep[$type])) : '';
			return $s;
		}
		return '';
	}

	/**
	 * Checks whether the passed dependency is TER-style (string) or TER2-style (array) and returns a single string for displaying the dependencies.
	 *
	 * It leaves out all version numbers and the "php" and "typo3" dependencies, as they are implicit and of no interest without the version number.
	 *
	 * @param	mixed		$dep Either a string or an array listing dependencies.
	 * @param	string		$type The dependency type to list if $dep is an array
	 * @return	string		A simple dependency list for display
	 */
	function stringToDep($dep) {
		$constraint = array();
		if(is_string($dep) && strlen($dep)) {
			$dep = explode(',',$dep);
			foreach($dep as $v) {
				$constraint[$v] = '';
			}
		}
		return $constraint;
	}








	/********************************
	*
	* Read information about all available extensions
	*
	*******************************/

	/**
	 * Returns the list of available (installed) extensions
	 *
	 * @return	array		Array with two arrays, list array (all extensions with info) and category index
	 * @see getInstExtList()
	 */
	function getInstalledExtensions()	{
		$list = array();
		$cat = $this->defaultCategories;

		$path = $this->path;
		$this->getInstExtList($path,$list,$cat,'G');

		$path = $this->path;
		$this->getInstExtList($path,$list,$cat,'L');

		return array($list,$cat);
	}

	/**
	 * Gathers all extensions in $path
	 *
	 * @param	string		Absolute path to local, global or system extensions
	 * @param	array		Array with information for each extension key found. Notice: passed by reference
	 * @param	array		Categories index: Contains extension titles grouped by various criteria.
	 * @param	string		Path-type: L, G or S
	 * @return	void		"Returns" content by reference
	 * @access private
	 * @see getInstalledExtensions()
	 */
	function getInstExtList($path,&$list,&$cat,$type)	{

		if (@is_dir($path))	{
			$extList = t3lib_div::get_dirs($path);
			if (is_array($extList))	{
				foreach($extList as $extKey)	{
					if (@is_file($path.$extKey.'/ext_emconf.php'))	{
						$emConf = $this->includeEMCONF($path.$extKey.'/ext_emconf.php', $extKey);
						if (is_array($emConf))	{
							if (is_array($list[$extKey]))	{
								$list[$extKey]=array('doubleInstall'=>$list[$extKey]['doubleInstall']);
							}
							$list[$extKey]['doubleInstall'].= $type;
							$list[$extKey]['type'] = $type;
							$list[$extKey]['EM_CONF'] = $emConf;
							$list[$extKey]['files'] = t3lib_div::getFilesInDir($path.$extKey, '', 0, '', $this->excludeForPackaging);

							$this->setCat($cat,$list[$extKey], $extKey);
						}
					}
				}
			}
		}
	}

	/**
	 * Splits a version range into an array.
	 *
	 * If a single version number is given, it is considered a minimum value.
	 * If a dash is found, the numbers left and right are considered as minimum and maximum. Empty values are allowed.
	 *
	 * @param	string		$ver A string with a version range.
	 * @return	array
	 */
	function splitVersionRange($ver)	{
		$versionRange = array();
		if (strstr($ver, '-'))	{
			$versionRange = explode('-', $ver, 2);
		} else {
			$versionRange[0] = $ver;
			$versionRange[1] = '';
		}

		if (!$versionRange[0])	{ $versionRange[0] = '0.0.0'; }
		if (!$versionRange[1])	{ $versionRange[1] = '0.0.0'; }

		return $versionRange;
	}

	/**
	 * Fixes an old style ext_emconf.php array by adding constraints if needed and removing deprecated keys
	 *
	 * @param	array		$emConf
	 * @return	array
	 */
	function fixEMCONF($emConf) {
		if(!isset($emConf['constraints']) || !isset($emConf['constraints']['depends']) || !isset($emConf['constraints']['conflicts']) || !isset($emConf['constraints']['suggests'])) {
			if(!isset($emConf['constraints']) || !isset($emConf['constraints']['depends'])) {
				$emConf['constraints']['depends'] = $this->stringToDep($emConf['dependencies']);
				if(strlen($emConf['PHP_version'])) {
					$versionRange = $this->splitVersionRange($emConf['PHP_version']);
					if (version_compare($versionRange[0],'3.0.0','<')) $versionRange[0] = '3.0.0';
					if (version_compare($versionRange[1],'3.0.0','<')) $versionRange[1] = '0.0.0';
					$emConf['constraints']['depends']['php'] = implode('-',$versionRange);
				}
				if(strlen($emConf['TYPO3_version'])) {
					$versionRange = $this->splitVersionRange($emConf['TYPO3_version']);
					if (version_compare($versionRange[0],'3.5.0','<')) $versionRange[0] = '3.5.0';
					if (version_compare($versionRange[1],'3.5.0','<')) $versionRange[1] = '0.0.0';
					$emConf['constraints']['depends']['typo3'] = implode('-',$versionRange);
				}
			}
			if(!isset($emConf['constraints']) || !isset($emConf['constraints']['conflicts'])) {
				$emConf['constraints']['conflicts'] = $this->stringToDep($emConf['conflicts']);
			}
			if(!isset($emConf['constraints']) || !isset($emConf['constraints']['suggests'])) {
				$emConf['constraints']['suggests'] = array();
			}
		} elseif (isset($emConf['constraints']) && isset($emConf['dependencies'])) {
			$emConf['suggests'] = isset($emConf['suggests']) ? $emConf['suggests'] : array();
			$emConf['dependencies'] = $this->depToString($emConf['constraints']);
			$emConf['conflicts'] = $this->depToString($emConf['constraints'], 'conflicts');
		}

			// sanity check for version numbers, intentionally only checks php and typo3
		if(isset($emConf['constraints']['depends']) && isset($emConf['constraints']['depends']['php'])) {
			$versionRange = $this->splitVersionRange($emConf['constraints']['depends']['php']);
			if (version_compare($versionRange[0],'3.0.0','<')) $versionRange[0] = '3.0.0';
			if (version_compare($versionRange[1],'3.0.0','<')) $versionRange[1] = '0.0.0';
			$emConf['constraints']['depends']['php'] = implode('-',$versionRange);
		}
		if(isset($emConf['constraints']['depends']) && isset($emConf['constraints']['depends']['typo3'])) {
			$versionRange = $this->splitVersionRange($emConf['constraints']['depends']['typo3']);
			if (version_compare($versionRange[0],'3.5.0','<')) $versionRange[0] = '3.5.0';
			if (version_compare($versionRange[1],'3.5.0','<')) $versionRange[1] = '0.0.0';
			$emConf['constraints']['depends']['typo3'] = implode('-',$versionRange);
		}

		unset($emConf['private']);
		unset($emConf['download_password']);
		unset($emConf['TYPO3_version']);
		unset($emConf['PHP_version']);

		return $emConf;
	}

	/**
	 * Set category array entries for extension
	 *
	 * @param	array		Category index array
	 * @param	array		Part of list array for extension.
	 * @param	string		Extension key
	 * @return	array		Modified category index array
	 */
	function setCat(&$cat,$listArrayPart,$extKey)	{

		// Getting extension title:
		$extTitle = $listArrayPart['EM_CONF']['title'];

		// Category index:
		$index = $listArrayPart['EM_CONF']['category'];
		$cat['cat'][$index][$extKey] = $extTitle;

		// Author index:
		$index = $listArrayPart['EM_CONF']['author'].($listArrayPart['EM_CONF']['author_company']?', '.$listArrayPart['EM_CONF']['author_company']:'');
		$cat['author_company'][$index][$extKey] = $extTitle;

		// State index:
		$index = $listArrayPart['EM_CONF']['state'];
		$cat['state'][$index][$extKey] = $extTitle;

		// Type index:
		$index = $listArrayPart['type'];
		$cat['type'][$index][$extKey] = $extTitle;

		// Return categories:
		return $cat;
	}

	





	/**
	 * Returns the absolute path where the extension $extKey is installed (based on 'type' (SGL))
	 *
	 * @param	string		Extension key
	 * @param	string		Install scope type: L, G, S
	 * @return	string		Returns the absolute path to the install scope given by input $type variable. It is checked if the path is a directory. Slash is appended.
	 */
	function getExtPath($extKey,$type)	{
		if ($type)	{
			$path = $this->path.$extKey.'/';
			return @is_dir($path) ? $path : '';
		} else {
			return '';
		}
	}




	/**
	 * Returns the $EM_CONF array from an extensions ext_emconf.php file
	 *
	 * @param	string		Absolute path to EMCONF file.
	 * @param	string		Extension key.
	 * @return	array		EMconf array values.
	 */
	function includeEMCONF($path, $_EXTKEY)	{
		$EM_CONF = NULL;
		@include($path);
		if(is_array($EM_CONF[$_EXTKEY])) {
			return $this->fixEMCONF($EM_CONF[$_EXTKEY]);
		}
		return false;
	}


	/**
	 * Make upload array out of extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	mixed		Returns array with extension upload array on success, otherwise an error string.
	 */
	function makeUploadArray($extKey,$conf)	{
		$extPath = $this->getExtPath($extKey,$conf['type']);
		if ($extPath)	{

			// Get files for extension:
			$fileArr = array();
			$fileArr = t3lib_div::getAllFilesAndFoldersInPath($fileArr,$extPath,'',0,99,$this->excludeForPackaging);

			// Calculate the total size of those files:
			$totalSize = 0;
			foreach($fileArr as $file)	{
				$totalSize+=filesize($file);
			}

			// If the total size is less than the upper limit, proceed:
			if ($totalSize < $this->maxUploadSize)	{

				// Initialize output array:
				$uploadArray = array();
				$uploadArray['extKey'] = $extKey;
				$uploadArray['EM_CONF'] = $conf['EM_CONF'];
				$uploadArray['misc']['codelines'] = 0;
				$uploadArray['misc']['codebytes'] = 0;


				// Read all files:
				foreach($fileArr as $file)	{
					$relFileName = substr($file,strlen($extPath));
					$fI = pathinfo($relFileName);
					if ($relFileName!='ext_emconf.php')	{		// This file should be dynamically written...
						$uploadArray['FILES'][$relFileName] = array(
						'name' => $relFileName,
						'size' => filesize($file),
						'mtime' => filemtime($file),
						'is_executable' => (is_executable($file)),
						'content' => t3lib_div::getUrl($file)
						);
						if (t3lib_div::inList('php,inc',strtolower($fI['extension'])))	{
							$uploadArray['FILES'][$relFileName]['codelines']=count(explode(chr(10),$uploadArray['FILES'][$relFileName]['content']));
							$uploadArray['misc']['codelines']+=$uploadArray['FILES'][$relFileName]['codelines'];
							$uploadArray['misc']['codebytes']+=$uploadArray['FILES'][$relFileName]['size'];

							// locallang*.php files:
							if (substr($fI['basename'],0,9)=='locallang' && strstr($uploadArray['FILES'][$relFileName]['content'],'$LOCAL_LANG'))	{
								$uploadArray['FILES'][$relFileName]['LOCAL_LANG']=$this->getSerializedLocalLang($file,$uploadArray['FILES'][$relFileName]['content']);
							}
						}
						$uploadArray['FILES'][$relFileName]['content_md5'] = md5($uploadArray['FILES'][$relFileName]['content']);
					}
				}

				// Return upload-array:
				return $uploadArray;
			} else {
				die('error');
			}
		} else {
			die('error');
		}
	}
	
	/**
	 * Forces update of local EM_CONF. This will renew the information of changed files.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		Status message
	 */
	function updateLocalEM_CONF($extKey,$extInfo)	{
		$extInfo['EM_CONF']['_md5_values_when_last_written'] = serialize($this->serverExtensionMD5Array($extKey,$extInfo));
		$emConfFileContent = $this->construct_ext_emconf_file($extKey,$extInfo['EM_CONF']);

		$absPath = $this->getExtPath($extKey,$extInfo['type']);
		$emConfFileName = $absPath.'ext_emconf.php';
		if($emConfFileContent)	{

			if(@is_file($emConfFileName))	{
				if(t3lib_div::writeFile($emConfFileName,$emConfFileContent) === true) {
					return true;
				} else {
					die('could not write em_conf.php');
				}
			} else {
				die('em_conf.php not found');
			}
		} else {
			die('not content for em_conf.php');
		}
	}
	
	/**
	 * Creates a MD5-hash array over the current files in the extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	array		MD5-keys
	 */
	function serverExtensionMD5Array($extKey,$conf)	{

		// Creates upload-array - including filelist.
		$mUA = $this->makeUploadArray($extKey,$conf);

		$md5Array = array();
		if (is_array($mUA['FILES']))	{

			// Traverse files.
			foreach($mUA['FILES'] as $fN => $d)	{
				if ($fN!='ext_emconf.php')	{
					$md5Array[$fN] = substr($d['content_md5'],0,4);
				}
			}
		} else debug($mUA);
		return $md5Array;
	}
	
	/*******************************************
	*
	* Compiling upload information, emconf-file etc.
	*
	*******************************************/

	/**
	 * Compiles the ext_emconf.php file
	 *
	 * @param	string		Extension key
	 * @param	array		EM_CONF array
	 * @return	string		PHP file content, ready to write to ext_emconf.php file
	 */
	function construct_ext_emconf_file($extKey,$EM_CONF)	{

			// clean version number:
		$vDat = $this->renderVersion($EM_CONF['version']);
		$EM_CONF['version']=$vDat['version'];

		$code = '<?php

########################################################################
# Extension Manager/Repository config file for ext "'.$extKey.'".
#
# Auto generated '.date('d-m-Y H:i').'
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = '.$this->arrayToCode($EM_CONF, 0).';

?>';
		return str_replace(chr(13), '', $code);
	}
	
	/**
	 * Parses the version number x.x.x and returns an array with the various parts.
	 *
	 * @param	string		Version code, x.x.x
	 * @param	string		Increase version part: "main", "sub", "dev"
	 * @return	string
	 */
	function renderVersion($v,$raise='')	{
		$parts = t3lib_div::intExplode('.',$v.'..');
		$parts[0] = t3lib_div::intInRange($parts[0],0,999);
		$parts[1] = t3lib_div::intInRange($parts[1],0,999);
		$parts[2] = t3lib_div::intInRange($parts[2],0,999);

		switch((string)$raise)	{
			case 'main':
				$parts[0]++;
				$parts[1]=0;
				$parts[2]=0;
				break;
			case 'sub':
				$parts[1]++;
				$parts[2]=0;
				break;
			case 'dev':
				$parts[2]++;
				break;
		}

		$res = array();
		$res['version'] = $parts[0].'.'.$parts[1].'.'.$parts[2];
		$res['version_int'] = intval($parts[0]*1000000+$parts[1]*1000+$parts[2]);
		$res['version_main'] = $parts[0];
		$res['version_sub'] = $parts[1];
		$res['version_dev'] = $parts[2];

		return $res;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$array
	 * @param	unknown_type		$lines
	 * @param	unknown_type		$level
	 * @return	unknown
	 */
	function arrayToCode($array, $level=0) {
		$lines = 'array('.chr(10);
		$level++;
		foreach($array as $k => $v)	{
			if(strlen($k) && is_array($v)) {
				$lines .= str_repeat(chr(9),$level)."'".$k."' => ".$this->arrayToCode($v, $level);
			} elseif(strlen($k)) {
				$lines .= str_repeat(chr(9),$level)."'".$k."' => ".(t3lib_div::testInt($v) ? intval($v) : "'".t3lib_div::slashJS(trim($v),1)."'").','.chr(10);
			}
		}

		$lines .= str_repeat(chr(9),$level-1).')'.($level-1==0 ? '':','.chr(10));
		return $lines;
	}

	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/tools/em/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/tools/em/index.php']);
}

?>
