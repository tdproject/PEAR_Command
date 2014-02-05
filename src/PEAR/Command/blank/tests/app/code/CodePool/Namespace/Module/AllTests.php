<?php
/**
 * <Namespace>_<Module>_AllTests
 *
 * Implements the PHPUnit Test Suite for <Namespace>_<Module>
 *
 * License: GNU General Public License
 *
 * Copyright (c) 2011 TechDivision GmbH. All rights reserved.
 * Note: Original work copyright to respective authors
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   <Namespace>
 * @package    <Namespace>_<Module>
 * @subpackage Block
 * @copyright  Copyright (c) 1996-2011 TechDivision GmbH (http://www.techdivision.com)
 * @license	   http://www.gnu.org/licenses/gpl.html GPL, version 3
 * @version    $Id:$
 * @link       http://www.techdivision.com
 * @since      File available since Release 0.1.0
 * @author     TechDivision Core Team <core@techdivision.com>
 */

// set the include path necessary for running the tests
set_include_path(
    get_include_path() . PATH_SEPARATOR
    . getcwd() . PATH_SEPARATOR
    . '${php-target.dir}/pear/php'
);

require_once 'TechDivision/XHProfPHPUnit/TestSuite.php';
require_once '<Namespace>/<Module>/DummyTest.php';

/**
 * <Namespace>_<Module>_AllTests
 *
 * Implements the PHPUnit Test Suite for <Namespace>_<Module>
 *
 * @category   <Namespace>
 * @package    <Namespace>_<Module>
 * @subpackage Block
 * @copyright  Copyright (c) 1996-2011 TechDivision GmbH (http://www.techdivision.com)
 * @license	   http://www.gnu.org/licenses/gpl.html GPL, version 3
 * @version	   Release: @package_version@
 * @since	   Class available since Release 0.1.0
 * @author     TechDivision Core Team <core@techdivision.com>
 */
class <Namespace>_<Module>_AllTests
{

    /**
     * Initializes the TestSuite.
     *
     * @return TechDivision_XHProfPHPUnit_TestSuite The initialized TestSuite
     */
    public static function suite() {
        // initialize the TestSuite
        $suite = new TechDivision_XHProfPHPUnit_TestSuite(
        	'${ant.project.name}',
        	'',
            '${release.version}'
        );
        // add some tests
        $suite->addTestSuite('<Namespace>_<Module>_DummyTest');
        // return the initialized suite
        return $suite;
    }
}