<?php
   /**
   *  @package tools::html::taglib::documentcontroller
   *  @class iteratorBaseController
   *
   *  Implementiert den Basis-DocumentController f�r die Verwendung des Iterator-Tags. Konkrete<br/>
   *  DocumentController m�ssen von diesem Controller erben.<br />
   *
   *  @author Christian Achatz
   *  @version
   *  Version 0.1, 02.06.2008<br />
   */
   class iteratorBaseController extends baseController
   {

      function iteratorBaseController(){
      }


      /**
      *  @private
      *
      *  Gibt die Referenz auf ein Iterator-Objekt zur�ck.<br />
      *
      *  @param string $Name; Name des Iterators.
      *  @return html_taglib_iterator $Iterator; Referenz auf den Iterator
      *
      *  @author Christian Achatz
      *  @version
      *  Version 0.1, 02.06.2008<br />
      */
      function &__getIterator($Name){

         // Deklariert das notwendige TagLib-Modul
         $TagLibModule = 'html_taglib_iterator';


         // Falls TagLib-Modul nicht vorhanden -> Fehler!
         if(!class_exists($TagLibModule)){
            trigger_error('['.get_class($this).'::__getIteratorTemplate()] TagLib module "'.$TagLibModule.'" is not loaded!',E_USER_ERROR);
          // end if
         }


         // Pr�fen, ob Kinder existieren
         if(count($this->__Document->__Children) > 0){

            // Templates aus dem aktuellen Document bereitstellen
            foreach($this->__Document->__Children as $ObjectID => $Child){

               // Klassen mit dem Namen "$TagLibModule" aus den Child-Objekten des
               // aktuellen "Document"s als Referenz zur�ckgeben
               if(get_class($Child) == $TagLibModule){

                  // Pr�fen, ob das gefundene Template $Name hei�t.
                  if($Child->getAttribute('name') == $Name){
                     return $this->__Document->__Children[$ObjectID];
                   // end if
                  }

                // end if
               }

             // end foreach
            }

          // end if
         }
         else{

            // Falls keine Kinder existieren -> Fehler!
            trigger_error('['.get_class($this).'::__getIteratorTemplate()] No iterator object with name "'.$Name.'" composed in current document for document controller "'.get_class($this).'"! Perhaps tag library html:iterator is not loaded in current template!',E_USER_ERROR);
            exit();

          // end else
         }


         // Falls das Template nicht gefunden werden kann -> Fehler!
         trigger_error('['.get_class($this).'::__getIteratorTemplate()] Iterator with name "'.$Name.'" cannot be found!',E_USER_ERROR);
         exit();

       // end function
      }

    // end class
   }
?>