<?php
namespace APF\core\database\pdo;

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
use APF\core\database\DatabaseConnection;
use APF\core\database\Result;


/**
 * @package APF\core\database\pdo
 * @class PDOResult
 */
class PDOResultHandler implements Result {

   /* @var $resultObject \PDOStatement */
   protected $resultObject = null;

   /**
    * @var array
    */
   protected $FetchMode = array(
         DatabaseConnection::FETCH_ASSOC   => \PDO::FETCH_ASSOC,
         DatabaseConnection::FETCH_OBJECT  => \PDO::FETCH_OBJ,
         DatabaseConnection::FETCH_NUMERIC => \PDO::FETCH_NUM
   );

   public function __construct(\PDOStatement $pdoResult) {
      $this->resultObject = $pdoResult;
   }

   /**
    * @inheritdoc
    *
    * @author dingsda
    * @version
    * Version 0.1, 08.04.2014<br />
    */
   public function fetchAll($type = DatabaseConnection::FETCH_ASSOC) {
      return $this->resultObject->fetchAll($this->FetchMode[$type]);
   }

   /**
    * @inheritdoc
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 20.09.2009<br />
    * Version 0.2, 08.08.2010 (Added optional second parameter) <br />
    */
   public function fetchData($type = DatabaseConnection::FETCH_ASSOC) {
      $return = null;
      $return = $this->resultObject->fetch($this->FetchMode[$type]);
      if ($return === null) {
         return false;
      }

      return $return;
   }


   /**
    * @inheritdoc
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function getNumRows() {
      return $this->resultObject->rowCount();
   }

   /**
    * @inheritdoc
    */
   public function freeResult() {
      $this->resultObject->closeCursor();
   }

   /**
    * @inheritdoc
    *
    * @return bool
    */
   public function nextRowset(){

      return $this->resultObject->nextRowset();

   }
}