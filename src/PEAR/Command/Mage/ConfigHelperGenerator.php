<?php

/**
 * generates the module's abstract configuration helper
 * based on the options defined in system.xml
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  	Copyright (c) 2011 Markus Stockbauer
 * 			 	<m.stockbauer@techdivision.com> -  TechDivision GmbH
 * @license    	http://opensource.org/licenses/osl-3.0.php
 * 				Open Software License (OSL 3.0)
 */

class PEAR_Command_Mage_ConfigHelperGenerator
{
    /**
     * Holds the path to the module's system.xml file
     *
     * @var string
     */
    protected $_pathToSystemXml;

    /**
     * Holds the path to the generated helper class file
     *
     * @var string
     */
    protected $_pathToHelper;

    /**
     * Holds the path to the generator's template file
     *
     * @var string
     */
    protected $_pathToTemplate = "PEAR/Command/Mage/ConfigHelperGenerator/Config.template.php";

    /**
     * Set the path to the source system.xml file (input)
     *
     * @param string $path
     * @return void
     */
	public function setPathToSystemXml($path) {
		$this->_pathToSystemXml = $path;
	}

    /**
     * Set the path to the generated helper class file (output)
     *
     * @param string $path
     * @return void
     */
	public function setPathToHelper($path) {
		$this->_pathToHelper = $path;
	}

	/**
	 * Checks for availability of the necessary files and
	 * tries to render the class file of the resulting
	 * configuration helper by using the template file.
	 *
	 * @param SimpleXMLElement $xml
	 * @throws Exception
	 * @return boolean
	 */
	protected function _generate(SimpleXMLElement $xml) {

	    /**
	     * Local copy of the class property which holds the
	     * path to the system.xml file (input file)
	     *
	     * @var $pathToSystemXml string
	     */
	    $pathToSystemXml = $this->_pathToSystemXml;

	    /**
	     * Local copy of the class property which holds the
	     * path to the helper class file (output file)
	     *
	     * @var $pathToHelper string
	     */
	    $pathToHelper = $this->_pathToHelper;

	    /**
	     * Local copy of the class property which holds the
	     * path to the template file
	     *
	     * @var $pathToTemplate string
	     */
	    $pathToTemplate = $this->_pathToTemplate;

	    /**
	     * Holds all the CONSTs which were created while
	     * parsing the system.xml file. The CONSTs are used in the
	     * generated helper to hold the XML_PATHs to the config values.
	     *
	     * @var $consts array
	     */
	    $consts = array();

	    /**
	     * Holds all the getters which were created while
	     * parsing the system.xml file. The getters are used in the
	     * generated helper to gain read access to the config values.
	     *
	     * @var $getters array
	     */
	    $getters = array();

	    // we do not know the module name here, so get all the <group>s
	    // which are available in the system.xml file, and gather them
	    // in an array
	    foreach($xml->xpath('//groups') as $group) {
	        $groups[] = $group;
	    }

	    // if there are no groups, this means that the system.xml file is
	    // empty or not valid. A module should always have a valid system.xml
	    if(!is_array($groups) || !count($groups)) {
	        throw new Exception(__METHOD__ . ": no groups found in ${$pathToSystemXml}, exiting");
	}

	// try to open the Helper Class file for writing, and throw
	// and exception if that fails
	if(!$output = fopen($pathToHelper, 'w')) {
	    throw new Exception(__METHOD__ . ": unable to open helper ${pathToHelper} for writing, exiting");
		}

		// iterate over the array containing all the
		// groups in the system.xml, giving us one <groups>-Tag at a time
		/* @var $_groups SimpleXMLElement */
		foreach($groups as $_groups) {

		    /**
		     * An array of all Module Sections (e.g. "techdivision_test"),
		     * which should usually be only one entry
		     *
		     * @var $moduleSections array
		     */
	    	$moduleSections = $_groups->xpath('parent::*');

		    /**
		     * The first (and only) entry of module section, which
		     * contains e.g. an xml-node like <techdivision_test>
		     *
		     * @var $moduleSection SimpleXMLElement
		     */
	    	$moduleSection = array_shift($moduleSections);

		    /**
		     * The module name we need later on parsed from
		     * the tag name of the section tag, e.g. "techdivision_test"
		     *
		     * @var $moduleName string
		     */
	    	$moduleName = $moduleSection->getName();

	    	// Iterate over the current <groups>-Tag, which gives us
	    	// one <group>-Tag at a time
	    	/* @var $group SimpleXMLElement */
		    foreach($_groups as $group) {

		        // The only part of the current <group> which is interesting
		        // to us is the <fields> section, which we iterate over.
		        // This gives us one <fields>-tag at a time
		    	foreach($group->fields as $fields) {

    		        // Iterate over the current <fields>-Tag, which gives
    		        // us a SimpleXMLElement containing one field at a time
		            foreach($fields as $field) {

		                // build the config path for the current field
		                $path = $moduleName . '/' . $group->getName() . '/' . $field->getName();

		                // build the XML_PATH variable name for the current field
		                $xmlPath = strtoupper($group->getName() . '_' . $field->getName());

		                // save the generated values as array member for
		                // the array holding our later CONSTs
		                $consts[] = array(
									'field' => $field,
									'path' => $path,
									'xmlPath' => $xmlPath,
		                );

		                // save the generated values as array member for
		                // the array holding our later getters
		                $getters[] = array(
									'path' => $path,
									'xmlPath' => $xmlPath,
									'pathCamelCased' => $this->_toCamelCase($xmlPath),
									'field' => $field
		                );

		            }
		        }
		    }
		}

		// start the output buffer to be able to render the included
		// template file into variable instead of STDOUT
		ob_start();

		// try to include the template file which renders our helper class
		// the include instantly renders the template into the output buffer
		if(!include($this->_pathToTemplate)) {
		    throw new Exception(__METHOD__ . ": template file ${pathToTemplate} not found, or errors while rendering, exiting");
		}

		// get the contents of the output buffer and save it into the
		// result variable
		/* @var $result string */
		$result = ob_get_contents();

		// close and empty the output buffer
		ob_end_clean();

		// finally, write the result string to the output file stream
		fwrite($output,$result);

		return true;
	}

	/**
	 * Convenience function to convert a string in the form of
	 * ab_cd_ef to the form of AbCdEf (camelize)
	 *
	 * @param string $str
	 * @param boolean $capitalise_first_char
	 * @return string
	 */
	protected function _toCamelCase($str, $capitalise_first_char = true) {
	    $str = strtolower($str);
	    if($capitalise_first_char) {
	        $str[0] = strtoupper($str[0]);
	    }
	    $func = create_function('$c', 'return strtoupper($c[1]);');
	    return preg_replace_callback('/_([a-z])/', $func, $str);
	}

	/**
	 * Generate the helper file by using the xml files
	 * as the basis. Wrapper function for _generate,
	 * which does the actual work
	 *
	 * @param void
	 * @throws Exception
	 * @return boolean
	 */
	public function generateHelper() {

	    /**
	     * Local copy of the class property which holds the
	     * path to the system.xml file (input file)
	     *
	     * @var $pathToSystemXml string
	     */
	    $pathToSystemXml = $this->_pathToSystemXml;

	    /**
	     * Local copy of the class property which holds the
	     * path to the helper class file (output file)
	     *
	     * @var $pathToHelper string
	     */
	    $pathToHelper = $this->_pathToHelper;

	    // try to generate the config helper class from system.xml file
	    // using the protected _generate method as worker. As there
	    // are plenty of things which can go wrong, we try/catch here locally.
	    try {

	        // try to read the contents of the system.xml file into
	        // $xmlString. If this fails, the file is not found or
	        // not readable, and thus the programm cannot continue
	        if(!$xmlString = file_get_contents($this->_pathToSystemXml))
	        {
	            throw new Exception
	            (
	                __METHOD__ . ": system.xml (${pathToSystemXml}) not found, exiting"
	            );
	        }

	        // try to parse the xml string into a SimpleXMLElement. If this
	        // fails, the XML in the file is not valid or empty. This should
	        // never be the case, for every magento module should have
	        // a valid system.xml file
	        if(!$xml = new SimpleXMLElement($xmlString)) {
	            throw new Exception
	            (
	                __METHOD__ . ": error parsing XML (${pathToSystemXml}), exiting"
	            );
	        }

	        // having the input file and content, we can try generating the
	        // output content using $this->_generate.
    	    if(!$this->_generate($xml))
    	    {
    	        throw new Exception
    	        (
    	            __METHOD__ . ": error generating helper (${pathToHelper}), exiting"
    	        );
    		}


    		return true;

		} catch (Exception $e) {
		    // Exception logging goes directly to STDOUT, for this
		    // functions will be called from console or script
		    echo "\n\n";
		    echo $e->getMessage();
		    echo "\n\n";
		    return false;
		}
	}
}