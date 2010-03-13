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

   import('tools::form::taglib','select_taglib_option');
   import('tools::form::taglib','form_taglib_select');

   /**
    * @package tools::form::taglib
    * @class form_taglib_multiselect
    *
    * Represents the APF multiselect field.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 15.01.2007<br />
    * Version 0.2, 07.06.2008 (Reimplemented the transform() method)<br />
    * Version 0.3, 08.06.2008 (Reimplemented the __validate() method)<br />
    * Version 0.3, 12.02.2010 (Introduced attribute black and white listing)<br />
    */
   class form_taglib_multiselect extends form_taglib_select {

      /**
       * @public
       *
       * Initializes the known child taglibs, sets the validator style and addes the multiple attribute.
       *
       * @author Christian Schäfer
       * @version
       * Version 0.1, 07.01.2007<br />
       * Version 0.2, 03.03.2007 (Removed the "&" before the "new" operator)<br />
       * Version 0.3, 26.08.2007 (Added the "multiple" attribut)<br />
       */
      function form_taglib_multiselect(){
         $this->__TagLibs[] = new TagLib('tools::form::taglib','select','option');
         $this->setAttribute('multiple','multiple');
         $this->attributeWhiteList[] = 'disabled';
         $this->attributeWhiteList[] = 'name';
         $this->attributeWhiteList[] = 'size';
         $this->attributeWhiteList[] = 'tabindex';
         $this->attributeWhiteList[] = 'multiple';
       // end function
      }

      /**
       * @public
       *
       * Parses the child tags and checks the name of the element to contain "[]".
       *
       * @author Christian Achatz
       * @version
       * Version 0.1, 15.01.2007<br />
       * Version 0.2, 07.06.2008 (Extended error message)<br />
       * Version 0.3, 15.08.2008 (Extended error message with the name of the control)<br />
       */
      function onParseTime(){

         // parses the option tags
         $this->__extractTagLibTags();

         // check, whether the name of the control has no "[]" defined, to ensure
         // that we can address the element with it's plain name in the template.
         $name = $this->getAttribute('name');
         if(substr_count($name,'[') > 0 || substr_count($name,']') > 0){
            $doc = &$this->__ParentObject->getParentObject();
            $docCon = $doc->getDocumentController();
            throw new FormException('[form_taglib_multiselect::onParseTime()] The attribute "name" of the '
               .'&lt;form:multiselect /&gt; tag with name "'.$this->__Attributes['name']
               .'" in form "'.$this->__ParentObject->getAttribute('name').'" and document '
               .'controller "'.$docCon.'" must not contain brackets! Please ensure, that the '
               .'appropriate form control has a suitable name. The brackets are automatically '
               .'generated by the taglib!',E_USER_ERROR);
         }

         $this->__presetValue();

       // end function
      }

      /**
       * @public
       *
       * Creates the HTML output of the select field.
       *
       * @return string The HTML code of the select field.
       *
       * @author Christian Achatz
       * @version
       * Version 0.1, 07.06.2008 (Reimplemented the transform() method because of a presetting error)<br />
       */
      function transform(){

         // add brackets for the "name" attribute to ensure multi select capability!
         $name = array('name' => $this->getAttribute('name').'[]');
         $select = '<select '.$this->__getAttributesAsString(array_merge($this->__Attributes,$name)).'>';
         $select .= $this->__Content.'</select>';

         if(count($this->__Children) > 0){

            $controlName = $this->getAttribute('name');

            foreach($this->__Children as $objectId => $DUMMY){

               // check, if $_REQUEST[$controlName] is an array and if the value
               // of the select field is included there.
               if(isset($_REQUEST[$controlName]) && is_array($_REQUEST[$controlName])){
                  if(in_array($this->__Children[$objectId]->getAttribute('value'),$_REQUEST[$controlName])){
                     $this->__Children[$objectId]->setAttribute('selected','selected');
                   // end if
                  }
                  else{
                     $this->__Children[$objectId]->deleteAttribute('selected');
                   // end else
                  }

                // end if
               }

               $select = str_replace('<'.$objectId.' />',
                  $this->__Children[$objectId]->transform(),
                  $select
               );

             // end foreach
            }

          // end if
         }

         return $select;

       // end function
      }

      /**
       * @public
       *
       * Returns the selected options.
       *
       * @return select_taglib_option[] List of the options, that are selected.
       *
       * @author Christian Achatz
       * @version
       * Version 0.1, 08.06.2008<br />
       */
      function &getSelectedOptions(){

         // call presetting lazy
         $this->__presetValue();

         // create list
         $options = array();

         foreach($this->__Children as $ObjectID => $DUMMY){

            if($this->__Children[$ObjectID]->getAttribute('selected') == 'selected'){
               $options[] = &$this->__Children[$ObjectID];
             // end if
            }

          // end foreach
         }

         return $options;

       // end function
      }

      /**
       * @protected
       *
       * Reimplements the presetting method for the multiselect field.
       *
       * @author Christian Achatz
       * @version
       * Version 0.1, 15.01.2007<br />
       * Version 0.2, 16.01.2007 (Now checks, if the request param is set)<br />
       */
      protected function __presetValue(){

         // generate the offset of the request array from the name attribute
         $controlName = $this->getAttribute('name');

         // get the request value
         if(isset($_REQUEST[$controlName])){
            $values = $_REQUEST[$controlName];
          // end if
         }
         else{
            $values = array();
          // end else
         }

         // preselect options
         if(count($this->__Children) > 0){

            foreach($this->__Children as $objectId => $DUMMY){
               if(in_array($this->__Children[$objectId]->getAttribute('value'),$values)){
                  $this->__Children[$objectId]->setAttribute('selected','selected');
               }
             // end foreach
            }

          // end if
         }

       // end function
      }

    // end class
   }
?>