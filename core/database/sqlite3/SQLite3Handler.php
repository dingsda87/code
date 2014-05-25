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
use APF\core\database\AbstractDatabaseHandler;
use APF\core\database\DatabaseHandlerException;
use APF\core\database\Result;
use APF\core\database\Statement;
use APF\core\logging\LogEntry;

/**
 * @package APF\core\database
 * @class SQLite3Handler
 *
 * This class provides APF-style access to sqlite3 databases.
 *
 * @author Christian Achatz, dingsda
 * @version
 * Version 0.1, 23.02.2008<br />
 * Version 0.2, 03.05.2014 (Adapted to new interface scheme)<br />
 */
class SQLite3Handler extends AbstractDatabaseHandler {
   /** @var null|\SQLite3 $dbConn */
   protected $dbConn=null;

   /**
    * @var int File system permission mode of the database.
    */
   protected $dbMode = 0666;

   /**
    * @var string Error tracking container for SQLite errors.
    */
   protected $dbError = null;

   public function __construct() {
      $this->dbLogTarget = 'sqlite3';
   }

   protected function connect() {

      $this->dbConn=new \SQLite3($this->dbName,$this->dbMode);

      $this->dbConn->enableExceptions(true);

   }

   protected function close() {
      $this->dbConn->close();
      $this->dbConn=null;
   }

   public function executeTextStatement($statement, array $params = array(), $logStatement = false, $emulatePrepare = null, $placeHolderType = null) {

      if(!empty($params)){
         $stmt=new SQLite3StatementHandler($statement,$this->dbConn,$this,$emulatePrepare);
         return $stmt->execute($params);
      }

      if ($logStatement == true) {
         $this->dbLog->logEntry($this->dbLogTarget, '[SQLite3Handler::executeTextStatement()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }

      try{
         $result = $this->dbConn->query($statement);
      }


      if ($result === false) {
         $message = sqlite_error_string(sqlite_last_error($this->dbConn));
         $message .= ' (Statement: ' . $statement . ')';
         $this->dbLog->logEntry($this->dbLogTarget, $message, LogEntry::SEVERITY_ERROR);
      }

      // remember last insert id for further usage
      $this->lastInsertId = sqlite_last_insert_rowid($this->dbConn);

      return $result;
   }

   public function executeStatement($namespace, $statementName, array $params = array(), $logStatement = false, $emulatePrepare = null) {

      $statement = $this->getPreparedStatement($namespace, $statementName, $params);

      if ($logStatement == true) {
         $this->dbLog->logEntry($this->dbLogTarget, '[SQLiteHandler::executeTextStatement()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }

      $result = sqlite_query($this->dbConn, $statement);

      if ($result === false) {
         $message = sqlite_error_string(sqlite_last_error($this->dbConn));
         $message .= ' (Statement: ' . $statement . ')';
         $this->dbLog->logEntry($this->dbLogTarget, $message, LogEntry::SEVERITY_DEBUG);
      }

      // remember last insert id for further usage
      $this->lastInsertId = sqlite_last_insert_rowid($this->dbConn);

      return $result;
   }

   /**
    *
    * Fetches a record from the database using the given result resource.
    *
    * @param resource $resultCursor The result resource returned by executeStatement() or executeTextStatement().
    * @param int $type The type the returned data should have. Use the static *_FETCH_MODE constants.
    *
    * @return string[] The associative result array.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 20.09.2009<br />
    * Version 0.2, 08.08.2010 (Added optional second parameter) <br />
    */
   public function fetchData($resultCursor, $type = self::ASSOC_FETCH_MODE) {
      if ($type === self::ASSOC_FETCH_MODE) {
         return sqlite_fetch_array($resultCursor, SQLITE_ASSOC);
      } elseif ($type === self::OBJECT_FETCH_MODE) {
         return sqlite_fetch_object($resultCursor);
      } else {
         return sqlite_fetch_array($resultCursor, SQLITE_NUM);
      }
   }

   /**
    *
    * Escapes given values to be SQL injection save.
    *
    * @param string $value The un-escaped value.
    *
    * @return string The escaped string.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 23.02.2008<br />
    */
   public function escapeValue($value) {
      return sqlite_escape_string($value);
   }

   /**
    *
    * Returns the amount of rows, that are affected by a previous update or delete call.
    *
    * @param resource $unusedParam The result resource pointer.
    *
    * @return int The number of affected rows.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 24.02.2008<br />
    */
   public function getAffectedRows() {
      return $this->dbConn->changes();
   }

   /**
    *
    * Returns the number of selected rows by the given result resource.
    *
    * @param Result $result The sqlite result resource.
    *
    * @return int The number of selected rows.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 12.03.2011 (Added missing method.)<br />
    */
   public function getNumRows(Result $result) {
      return sqlite_num_rows($result);
   }

   protected function execute($statement, $logStatement = false) {
      // TODO: Implement execute() method.
   }

   protected function prepare($statement, array $params, $logStatement) {
      // TODO: Implement prepare() method.
   }

   public function getLastID() {
      // TODO: Implement getLastID() method.
   }

   public function quoteValue($value) {
      // TODO: Implement quoteValue() method.
   }

   public function beginTransaction() {
      // TODO: Implement beginTransaction() method.
   }

   public function commit() {
      // TODO: Implement commit() method.
   }

   public function rollback() {
      // TODO: Implement rollback() method.
   }

   /**
    *
    * Prepares a statement statement, located within a statement file and returns a Statement object
    *
    * @param string $namespace Namespace of the statement file.
    * @param string $fileName Name of the statement file (with the .sql ending).
    * @param bool $logStatement Indicates, if the statement is logged for debug purposes.
    * @param int $placeHolderType Type of place holder used within this statement (see PLACE_HOLDER_* for details).
    *
    * @return Statement A StatementObject object to work with
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function prepareStatement($namespace, $fileName, $logStatement = false, $emulate = null) {
      $statement = $this->getPreparedStatement($namespace, $fileName);

      return new SQLiteStatementHandler($statement, $this->dbConn, $this, false);
   }

   /**
    *
    * Prepares a statement for execution and returns a statement object.
    *
    * @param string $statement The statement string.
    * @param bool $logStatement Indicates, if the statement is logged for debug purposes.
    * @param int $placeHolderType Type of place holder used within this statement (see PLACE_HOLDER_* for details).
    *
    * @return Statement A statement object to work with.
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function prepareTextStatement($statement, $logStatement = false, $emulate = null) {
      return new SQLiteStatementHandler($statement, $this->dbConn, $this, false);
   }
}
