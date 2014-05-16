<?php
namespace APF\core\database\mysqli;

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
use APF\core\logging\LogEntry;


/**
 * @package APF\core\database\mysqli
 * @class MySQLiHandler
 *
 * This class implements a connection handler for the ConnectionManager
 * to use with mysqli extension.
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 27.02.2008<br />
 */
class MySQLiHandler extends AbstractDatabaseHandler {

   /**
    *
    * @var \mysqli $dbConn
    */
   protected $dbConn = null;

   public function __construct() {
      $this->dbLogTarget = 'mysqli';
      $this->dbPort = '3306';
   }

   public function escapeValue($value) {
      return $this->dbConn->real_escape_string($value);
   }

   /**
    * @inheritdoc
    */
   public function quoteValue($value) {
      return '\'' . $this->dbConn->real_escape_string($value) . '\'';
   }

   /**
    * @inheritdoc
    */
   public function beginTransaction() {
      return $this->dbConn->begin_transaction();
   }

   /**
    * @inheritdoc
    */
   public function commit() {
      return $this->dbConn->commit();
   }

   /**
    * @inheritdoc
    */
   public function rollback() {
      return $this->dbConn->rollback();
   }

   /**
    * @inheritdoc
    */
   public function getAffectedRows($unusedParam = null) {
      return $this->dbConn->affected_rows;
   }

   public function close() {
      if (!$this->dbConn->close()) {
         throw new DatabaseHandlerException('An error occurred during closing of the '
               . 'database connection (' . $this->dbConn->errno . ': ' . $this->dbConn->error . ')!', E_USER_WARNING);
      }
      $this->dbConn = null;
   }

   /**
    * @inheritdoc
    */
   public function getLastID() {
      return $this->dbConn->insert_id;
   }

   /**
    *
    */
   protected function connect() {
      // switch error mode of MySQLi to exceptions
      $driver = new \mysqli_driver();
      $driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
      // initiate connection
      try {
         $this->dbConn = new \mysqli();
         $this->dbConn->real_connect($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName, $this->dbPort, $this->dbSocket);
      } catch (\Exception $e) {
         throw new DatabaseHandlerException($e->getMessage(), $e->getCode(), $e);
      }

      // configure client connection
      if ($this->dbCharset !== null) {
         try {
            $this->dbConn->set_charset($this->dbCharset);
         } catch (\Exception $e) {
            throw new DatabaseHandlerException($e->getMessage(), $e->getCode(), $e);
         }
      }
      // configure client connection
      $this->initCharsetAndCollation();
      if ($this->dbCharset !== null) {
         if (!$this->dbConn->set_charset($this->dbCharset)) {
            throw new DatabaseHandlerException(
                  '[MySQLiHandler->connect()] Error loading character set ' . $this->dbCharset .
                  ' (' . $this->dbConn->error . ')!'
            );
         }
      }
   }

   /**
    * @inheritdoc
    *
    * @return MySQLiResultHandler
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 10.02.2008<br />
    */
   public function executeTextStatement($statement, array $params = array(), $logStatement = false, $emulatePrepare = null) {
      // log statements in debug mode or when requested explicitly
      if ($this->dbDebug === true || $logStatement === true) {
         $this->dbLog->logEntry($this->dbLogTarget, '[MySQLiHandler::executeTextStatement()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }

      if (empty($params)) {

         try {
            // execute the statement with use of the current connection!
            $this->dbConn->real_query($statement);
         } catch (\Exception $e) {
            throw new DatabaseHandlerException(
                  'SQLSTATE[' . $this->dbConn->sqlstate . ']: ' .
                  $e->getMessage() . ' (Statement: ' . $statement . ')',
                  $e->getCode(), $e);
         }

         if ($this->dbConn->field_count) {
            return new MySQLiResultHandler($this->dbConn->store_result());
         }

         return null;
      }

      $stmt = $this->prepareTextStatement($statement, $logStatement, $emulatePrepare);

      return $stmt->execute($params);

   }

   /**
    *
    * @inheritdoc
    *
    * @return MySQLiStatementHandler
    */
   public function prepareTextStatement($statement, $logStatement = false, $emulate = null) {
      $emulate = ($emulate === null) ? $this->emulate : $emulate;

      if ($this->dbDebug === true || $logStatement === true) {
         $this->dbLog->logEntry($this->dbLogTarget, '[MySQLiHandler::prepareTextStatement()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }

      return new MySQLiStatementHandler($statement, $this->dbConn, $this, $emulate);
   }

   /**
    *
    * @inheritdoc
    *
    * @return MySQLiStatementHandler
    */
   public function prepareStatement($namespace, $fileName, $logStatement = false, $emulate = null) {
      return $this->prepareTextStatement($this->getPreparedStatement($namespace, $fileName), $logStatement, $emulate);
   }

   /**
    *
    * @inheritdoc
    *
    * @return MySQLiResultHandler
    */
   public function executeStatement($namespace, $statementName, array $params = array(), $logStatement = false, $emulatePrepare = null) {
      $statement = $this->getPreparedStatement($namespace, $statementName);

      // execute the statement with use of the current connection!
      return $this->executeTextStatement($statement, $params, $logStatement, $emulatePrepare);
   }

}


