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

   /**
    * @package tools::form::taglib
    * @class form_taglib_button
    *
    * Represents an APF form button.
    *
    * @author Christian Schäfer
    * @version
    * Version 0.1, 05.01.2007<br />
    * Version 0.2, 14.04.2007 (Added the onAfterAppend() method)<br />
    * Version 0.3, 12.02.2010 (Introduced attribute black and white listing)<br />
    */
   class form_taglib_button extends form_control {

      public function form_taglib_button(){
         $this->attributeWhiteList[] = 'name';
         $this->attributeWhiteList[] = 'accesskey';
         $this->attributeWhiteList[] = 'disabled';
         $this->attributeWhiteList[] = 'tabindex';
         $this->attributeWhiteList[] = 'value';
      }

      /**
       * @public
       * @since 0.2
       *
       * Indicates, if the form was sent.
       *
       * @author Christian Schäfer
       * @version
       * Version 0.1, 14.04.2007<br />
       */
      function onParseTime(){

         $buttonName = $this->getAttribute('name');
         if($buttonName === null){
            $formName = $this->__ParentObject->getAttribute('name');
            throw new FormException('[form_taglib_button::onAfterAppend()] Missing required attribute '
               .'"name" in &lt;form:button /&gt; tag in form "'.$formName.'". '
               .'Please check your form definition!',E_USER_ERROR);
         }

         // check name attribute in request to indicate, that the
         // form was sent. Mark button as sent, too.
         if(isset($_REQUEST[$buttonName])){
            $this->__ControlIsSent = true;
          // end if
         }

       // end function
      }

      /**
       * @public
       *
       * Returns the HTML code of the button.
       *
       * @return string The HTML code of the button.
       *
       * @author Christian Schäfer
       * @version
       * Version 0.1, 05.01.2007<br />
       */
      function transform(){
         return '<input type="submit" '.$this->getSanitizedAttributesAsString($this->__Attributes).' />';
       // end function
      }

    // end class
   }
?>