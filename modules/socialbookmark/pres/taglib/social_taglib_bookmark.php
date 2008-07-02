<?php
   import('modules::socialbookmark::biz','socialBookmarkManager');


   /**
   *  @package modules::socialbookmark::pres::taglib
   *  @class social_taglib_bookmark
   *
   *  Implementiert eine TagLib f�r die Ausgabe von Bookmarks per Tag.<br />
   *  Optional k�nnen die Parameter<br />
   *  <br />
   *  - width (Breite der Bookmark-Icons)<br />
   *  - height (H�he der Bookmark-Icons)<br />
   *  <br />
   *  angegeben werden. Beispiel:<br />
   *  <br />
   *  &lt;social:bookmark width="16" height="16"/&gt;<br />
   *  <br />
   *  Um das Tag verwenden zu k�nnen muss der BookmarkManager konfiguriert sein!<br />
   *
   *  @author Christian W. Sch�fer
   *  @version
   *  Version 0.1, 08.09.2007<br />
   */
   class social_taglib_bookmark extends Document
   {

      /**
      *  @public
      *
      *  Konstruktor der Klasse. Initialisiert die ben�tigten Attribute.<br />
      *
      *  @author Christian W. Sch�fer
      *  @version
      *  Version 0.1, 08.09.2007<br />
      */
      function social_taglib_bookmark(){
         $this->__Attributes['width'] = '20';
         $this->__Attributes['height'] = '20';
         $this->__Attributes['title'] = null;
         $this->__Attributes['url'] = null;
         $this->__Attributes['target'] = null;
       // end function
      }


      /**
      *  @public
      *
      *  Erzeugt die Ausgabe mit Hilfe des BookmarkManager.<br />
      *
      *  @author Christian W. Sch�fer
      *  @version
      *  Version 0.1, 08.09.2007<br />
      *  Version 0.2, 16.09.2007 (Attribute url, title und target hinzugef�gt)<br />
      */
      function transform(){

         // Bookmark-Manager holen
         $sBM = &$this->__getServiceObject('modules::socialbookmark::biz','socialBookmarkManager');

         // Breite und H�he konfigurieren
         $sBM->set('Width',$this->__Attributes['width']);
         $sBM->set('Height',$this->__Attributes['height']);

         // URL-Parameter konfigurieren
         if($this->__Attributes['url'] != null){
            $sBM->set('URL',$this->__Attributes['url']);
          // end if
         }
         if($this->__Attributes['title'] != null){
            $sBM->set('Title',$this->__Attributes['title']);
          // end if
         }
         if($this->__Attributes['target'] != null){
            $sBM->set('Target',$this->__Attributes['target']);
          // end if
         }

         // Bookmark-Quelltext zur�ckliefern
         return $sBM->getBookmarkCode();

       // end function
      }

    // end class
   }
?>