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
use APF\core\database\DatabaseConnection;
use APF\core\database\DatabaseHandlerException;
use APF\core\database\Result;
use APF\core\database\Statement;
use APF\core\logging\LogEntry;

/**
 * @package APF\core\database
 * @class SQLiteHandler
 *
 * This class provides APF-style access to sqlite databases.
 *
 * @author Christian Achatz, dingsda
 * @version
 * Version 0.1, 23.02.2008<br />
 * Version 0.2, 03.05.2014 (Adapted to new interface scheme)<br />
 */
class SQLiteHandler extends AbstractDatabaseHandler implements DatabaseConnection {

   /**
    * @protected
    * @var int File system permission mode of the database.
    */
   protected $dbMode = 0666;

   /**
    * @protected
    * @var string Error tracking container for SQLite errors.
    */
   protected $dbError = null;
   /**
    * @var null|\SQLiteDatabase $dbConn
    */
   protected $dbConn = null;


   public function __construct() {
      $this->dbLogTarget = 'sqlite';
   }

   protected function connect() {

      $this->dbConn = @new \SQLiteDatabase($this->dbName, $this->dbMode, $this->dbError);


      if (!is_object($this->dbConn)) {
         throw new DatabaseHandlerException('[SQLiteHandler->connect()] Database "'
               . $this->dbName . '" cannot be opened! Message: ' . $this->dbError, E_USER_ERROR);
      }

   }

   protected function close() {
      $this->dbConn = null;
   }

   public function executeTextStatement($statement, array $params = array(), $logStatement = false, $emulatePrepare = null, $placeHolderType = null) {

      if ($logStatement == true) {
         $this->dbLog->logEntry($this->dbLogTarget, '[SQLiteHandler::executeTextStatement()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }

      $result = $this->dbConn->query($statement);

      if ($result === false) {
         $message = sqlite_error_string($this->dbConn->lastError());
         $message .= ' (Statement: ' . $statement . ')';
         $this->dbLog->logEntry($this->dbLogTarget, $message, LogEntry::SEVERITY_ERROR);
      }

      // remember last insert id for further usage
      $this->lastInsertId = $this->dbConn->lastInsertRowid();

      return $result;
   }

   public function executeStatement($namespace, $statementName, array $params = array(), $logStatement = false, $emulatePrepare = null) {

      $statement = $this->getPreparedStatement($namespace, $statementName, $params);

      if ($logStatement == true) {
         $this->dbLog->logEntry($this->dbLogTarget, '[SQLiteHandler::executeTextStatement()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }

      $result = $this->dbConn->query($statement);

      if ($result === false) {
         $errorCode = $this->dbConn->lastError();
         $message = sqlite_error_string($errorCode);
         $message .= ' (Statement: ' . $statement . ')';
         throw new DatabaseHandlerException($message, $errorCode);
      }

      // remember last insert id for further usage
      $this->lastInsertId = $this->dbConn->lastInsertRowid();

      return $result;
   }

   /**
    *
    * @inheritdoc
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 20.09.2009<br />
    * Version 0.2, 08.08.2010 (Added optional second parameter) <br />
    */
   public function fetchData(Result $resultCursor, $type = DatabaseConnection::FETCH_ASSOC) {
      return $resultCursor->fetchData($type);
   }

   /**
    * @inheritdoc
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 23.02.2008<br />
    */
   public function escapeValue($value) {
      return sqlite_escape_string($value);
   }

   /**
    * @inheritdoc
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 24.02.2008<br />
    */
   public function getAffectedRows($unusedParam = null) {
      return $this->dbConn->changes();
   }

   /**
    * @inheritdoc
    */
   public function getNumRows(Result $result) {
      return $result->getNumRows();
   }

   /**
    * @inheritdoc
    */
   public function getLastID() {
      return $this->dbConn->lastInsertRowid();
   }

   /**
    * @inheritdoc
    */
   public function quoteValue($value) {
      return '\''. sqlite_escape_string($value) .'\'';
   }

   /**
    * @inheritdoc
    */
   public function beginTransaction() {
      $this->dbConn->queryExec('BEGIN TRANSACTION');
   }

   /**
    * @inheritdoc
    */
   public function commit() {
      $this->dbConn->queryExec('COMMIT TRANSACTION');

      // TODO: Implement commit method.
   }

   /**
    * @inheritdoc
    */
   public function rollback() {
      $this->dbConn->queryExec('ROLLBACK TRANSACTION');
   }

   /**
    *
    * @inheritdoc
    *
    * @return SQLiteStatementHandler
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function prepareStatement($namespace, $fileName, $logStatement = false, $emulate = null) {
      $statement = $this->getPreparedStatement($namespace, $fileName);

      return new SQLiteStatementHandler($statement, $this->dbConn, $this, true);
   }

   /**
    *
    * @inheritdoc
    *
    * @return SQLiteStatementHandler
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function prepareTextStatement($statement, $logStatement = false, $emulate = null) {
      return new SQLiteStatementHandler($statement, $this->dbConn, $this, true);
   }
}
