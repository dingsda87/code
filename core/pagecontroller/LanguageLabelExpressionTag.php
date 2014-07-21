<?php
/**
 * <!--
 * This file is part of the adventure php framework (APF) published under
 * http://adventure-php-framework.org.
 *
 * The APF is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The APF is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with the APF. If not, see http://www.gnu.org/licenses/lgpl-3.0.txt.
 * -->
 */
namespace APF\core\pagecontroller;

use APF\core\expression\taglib\ExpressionEvaluationTag;
use APF\core\registry\Registry;
use InvalidArgumentException;

/**
 * Implements a class for the taglibs &lt;html:getstring /&gt; and &lt;template:getstring /&gt;. This
 * lib fetches the desired configuration value and returns it on transformation time. The configuration
 * files must be structured as follows:
 * <p/>
 * <pre>
 * [de]
 * key = "german value"
 *
 * [en]
 * key = "english value"
 *
 * ...
 * </pre>
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 21.04.2006<br />
 * Version 0.2, 17.09.2009 (Refactored due to form taglib changes)<br />
 * Version 0.3, 11.02.2012 (Added LanguageLabelTag to core (refactoring!))
 */
class LanguageLabelExpressionTag extends Document {

   /**
    * Implements the functionality to retrieve a language dependent value form a
    * configuration file. Checks the attributes needed for displaying data.
    *
    * @return string The desired translation text.
    * @throws InvalidArgumentException In case of parameter issues.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 21.04.2006<br />
    * Version 0.2, 17.10.2008 (Enhanced error messages)<br />
    */
   public function onParseTime() {

      // check for attribute "namespace"
      $namespace = $this->getAttribute('namespace');
      if ($namespace === null) {
         throw new InvalidArgumentException('[' . get_class($this) . '->transform()] No attribute '
               . '"namespace" given in tag definition!', E_USER_ERROR);
      }

      // check for attribute "config"
      $configName = $this->getAttribute('config');
      if ($configName === null) {
         throw new InvalidArgumentException('[' . get_class($this) . '->transform()] No attribute '
               . '"config" given in tag definition!', E_USER_ERROR);
      }

      // get configuration values
      $config = $this->getConfiguration($namespace, $configName);
      $section = $config->getSection($this->getLanguage());

      if ($section == null) {

         // get environment variable from registry to have nice exception message
         $env = Registry::retrieve('APF\core', 'Environment');

         throw new InvalidArgumentException('[' . get_class($this) . '::transform()] Given language "'
               . $this->getLanguage() . '" in configuration "'
               . $env . '_' . $configName . '" in namespace "' . $namespace . '" and context "'
               . $this->getContext() . '"!', E_USER_ERROR);
      }

      $this->setData('getstring',$section);

      $this->extractTagLibTags();
   }

}
