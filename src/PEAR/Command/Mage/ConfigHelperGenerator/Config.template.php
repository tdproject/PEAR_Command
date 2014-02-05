<?php echo "<?php"; ?>

/**
 * <Namespace>_<Module>
 *
 * NOTICE OF LICENSE
 *
 * <Namespace>_<Module> is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * <Namespace>_<Module> is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with <Namespace>_<Module>.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade <Namespace>_<Module> to newer
 * versions in the future. If you wish to customize <Namespace>_<Module> for your
 * needs please refer to http://www.techdivision.com for more information.
 *
 * @category   	<Namespace>
 * @package    	<Namespace>_<Module>
 * @copyright  	Copyright (c) 2011 <info@techdivision.com> TechDivision GmbH
 * @license    	<http://www.gnu.org/licenses/>
 * 			   	GNU General Public License (GPL 3)
 * @author  	Firstname Lastname <xy@techdivision.com>
 */

/**
 * <Namespace>_<Module>_Helper_Config_Abstract
 *
 * Implements abstract block functionality for module.
 *
 * @category   	<Namespace>
 * @package    	<Namespace>_<Module>
 * @copyright  	Copyright (c) 2011 <info@techdivision.com> TechDivision GmbH
 * @license    	<http://www.gnu.org/licenses/>
 * 				GNU General Public License (GPL 3)
 * @author      Firstname Lastname <xy@techdivision.com>
 */

abstract class <Namespace>_<Module>_Helper_Config_Abstract
extends Mage_Core_Helper_Abstract
{
     <?php foreach($consts as $const): ?>

     /**
      * Holds the xml path to the config value
      * <?php echo $const['path'] ?> .
      *
      * @var string
      */
     const XML_PATH_<?php echo $const['xmlPath'] ?>

     	= '<?php echo $const['path']; ?>';

     <?php endforeach; ?>

     <?php foreach($getters as $getter): ?>

     /**
      * Returns the configured value for the config value
      * <?php echo $getter['path'] ?> .
      *
      * @param void
      * @return <?php if($getter['field']->frontend_type == "multiselect"): ?>array<?php else: ?>mixed<?php endif;?>

      */
     public function get<?php echo $getter['pathCamelCased']?>($storeId = null) {
         $config = Mage::getStoreConfig(
             self::XML_PATH_<?php echo $getter['xmlPath']?> , $storeId
         );
         <?php if($getter['field']->frontend_type == "multiselect"): ?>

         $config = explode(",", $config);

         <?php endif;?>

         return $config;
     }
     <?php endforeach;?>

}