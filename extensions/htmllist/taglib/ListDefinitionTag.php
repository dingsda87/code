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
namespace APF\extensions\htmllist\taglib;

/**
 * Represents a HTMLList definition list.
 *
 * @author Florian Horn
 * @version 1.0, 03.04.2010<br />
 */
class ListDefinitionTag extends AbstractListTag {

   /**
    * Adds a definition term element.
    *
    * @param string $sContent
    * @param string $sClass
    *
    * @return string
    */
   public function addDefinitionTerm($sContent, $sClass = '') {
      $this->addElement($sContent, $sClass, 'elem_defterm');
   }

   /**
    * Adds a definition element.
    *
    * @param string $sContent
    * @param string $sClass
    *
    * @return string
    */
   public function addDefinition($sContent, $sClass = '') {
      $this->addElement($sContent, $sClass, 'elem_defdef');
   }

   protected function getListIdentifier() {
      return 'dl';
   }

}