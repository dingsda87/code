<?php
namespace APF\core\database\sqlite;

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
use APF\core\database\Result;


/**
 * @package APF\core\database\sqlite
 * @class SQLiteResultHandler
 */
class SQLite3ResultHandler implements Result {

   /* @var $resultObject \SQLiteResult */
   protected $resultObject = null;

   /**
    * @var array
    */
   protected $FetchMode = array(
         self::FETCH_ASSOC   => SQLITE_ASSOC,
         self::FETCH_NUMERIC => SQLITE_NUM
   );

   public function __construct($result) {
      $this->resultObject = $result;
   }

   public function fetchAll($type = self::FETCH_ASSOC) {
      if ($type === self::FETCH_OBJECT) {
         $return=array();
         while($rowObject=$this->resultObject->fetchObject('stdClass')){
            $return[]=$rowObject;
         }
         return $return;
      }

      return $this->resultObject->fetchAll($this->FetchMode[$type]);
   }

   /**
    * @public
    *
    * Fetches a record from the database.
    *
    * @param int $type The type the returned data should have. Use the static FETCH_* constants.
    *
    * @return mixed The result array. Returns <em>false</em> if no row was found.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 20.09.2009<br />
    * Version 0.2, 08.08.2010 (Added optional second parameter)<br />
    */
   public function fetchData($type = self::FETCH_ASSOC) {

      if ($type === self::FETCH_OBJECT) {
         return $this->resultObject->fetchObject('stdClass');
      }

      return $this->resultObject->fetch($this->FetchMode[$type]);

   }

   /**
    * @public
    *
    * Returns the number of selected rows by a select statement. Some databases do not support this so
    * you should not relied on this behavior for portable applications.
    *
    * @return int The number of selected rows.
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function getNumRows() {
      return $this->resultObject->numRows();
   }

   /**
    * @public
    *
    * Frees up the connection so that a new statement can be executed.
    */
   public function freeResult() {

   }
}