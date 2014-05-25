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
use APF\core\benchmark\BenchmarkTimer;
use APF\core\database\AbstractDatabaseHandler;
use APF\core\database\DatabaseConnection;
use APF\core\database\DatabaseHandlerException;
use APF\core\database\Result;
use APF\core\database\Statement;
use APF\core\logging\LogEntry;
use APF\core\singleton\Singleton;
use string;

/**
 * @package APF\core\database\pdo
 * @class PDOHandler
 *
 * This class implements a connection handler for the ConnectionManager
 * to use with pdo interface.
 *
 * @author Tobias Lückel (megger)
 * @version
 * Version 0.1, 11.04.2012<br />
 */
class PDOHandler extends AbstractDatabaseHandler implements DatabaseConnection {

   /* @var $dbConn \PDO */
   protected $dbConn = null;

   /**
    * @var string Database type for pdo connection
    */
   protected $dbPDO = null;
   /**
    * @var null
    */
   protected $affectedRows = 0;

   /**
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function __construct() {
      $this->dbLogTarget = 'pdo';
   }

   /**
    * @param array|string $initParam
    */
   public function init($initParam) {

      // set database type for pdo connection
      if (isset($initParam['PDO'])) {
         $this->dbPDO = $initParam['PDO'];
      }

      parent::init($initParam);
   }

   /**
    * @inheritdoc
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function rollBack() {
      return $this->dbConn->rollBack();
   }

   /**
    * @inheritdoc
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 23.02.2008<br />
    */
   public function escapeValue($value) {
      $quoted = $this->dbConn->quote($value);

      return substr($quoted, 1, strlen($quoted) - 2);
   }

   /**
    * @inheritdoc
    */
   public function quoteValue($value) {
      return $this->dbConn->quote($value);
   }

   /**
    * @inheritdoc
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function commit() {
      return $this->dbConn->commit();
   }

   /**
    * @inheritdoc
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function beginTransaction() {
      return $this->dbConn->beginTransaction();
   }

   public function getAffectedRows() {
      return $this->affectedRows;
   }

   /**
    * @inheritdoc
    *
    * @author Christian Schäfer
    * @version
    * Version 0.1, 04.01.2006<br />
    */
   public function getLastID() {
      return $this->dbConn->lastInsertId();
   }

   /**
    *
    * Provides internal service to close a database connection.
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function close() {
      $this->dbConn = null;
   }

   /**
    * @param string $statement
    * @param bool $logStatement
    *
    * @return PDOStatementHandler
    * @throws DatabaseHandlerException
    */
   protected function execute($statement, $logStatement = false) {
      /* @var $t BenchmarkTimer */
      $t =& Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');
      $t->start(__METHOD__);
      // log statements in debug mode or when requested explicitly
      if ($this->dbDebug == true || $logStatement == true) {
         $this->dbLog->logEntry($this->dbLogTarget, '[PDOHandler::execute()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }
// prepare statement for execution
      try {
         $pdoResult = $this->dbConn->query($statement);
      } catch (\PDOException $e) {
         $errorNumber = $e->errorInfo[1];
         if ($errorNumber === 2014) {
            throw new DatabaseHandlerException('Cannot execute queries while other unbuffered queries are active. ' .
                  'Use PDOResult->freeResult to free up the connection.', $errorNumber, $e);
         }
         throw new DatabaseHandlerException(
               $e->getMessage() . '
               (Statement: ' . $statement . ')'
               , $errorNumber, $e
         );
      }
      if ($pdoResult->columnCount() === 0) {
         $this->affectedRows = $pdoResult->rowCount();
         $t->stop(__METHOD__);

         return null;
      }

      $this->affectedRows = 0;
      $t->stop(__METHOD__);

      return new PDOResultHandler($pdoResult);
   }


   /**
    *
    * Provides internal service to open a database connection.
    *
    * @throws DatabaseHandlerException
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   protected function connect() {
      /* @var $t BenchmarkTimer */
      $t =& Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');
      $t->start(__METHOD__);
      // get dsn based on the configuration
      $dsn = $this->getDSN();

      // log dsn if debugging is active
      if ($this->dbDebug === true) {
         $this->dbLog->logEntry($this->dbLogTarget, '[PDOHandler::connect()] Current DSN: ' . $dsn, LogEntry::SEVERITY_DEBUG);
      }
      // connect to database
      try {
         $this->dbConn = @new \PDO($dsn, $this->dbUser, $this->dbPass);
      } catch (\Exception $e) {
         throw new DatabaseHandlerException($e->getMessage(), $e->getCode(), $e);
      }

      // switch errormode of PDO to exceptions
      $this->dbConn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $this->dbConn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

      // configure client connection
      $this->initCharsetAndCollation();
      $t->stop(__METHOD__);
   }

   /**
    *
    * Returns the data source name (DSN) for the database connection.
    * The string is build bases on the configuration parameter 'PDO'
    * Actual following db drivers are supported:
    *  - mysql(i)
    *  - sqlite(2/3)
    *
    * @return string
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   protected function getDSN() {
      $dsn = '';
      switch (strtolower($this->dbPDO)) {
         case 'mysql':
         case 'mysqli':
            if (isset($this->dbSocket) && $this->dbSocket != '') {
               $dsn = 'mysql:unix_socket=' . $this->dbSocket;
            } else {
               $dsn = 'mysql:host=';
               if (isset($this->dbHost)) {
                  $dsn .= $this->dbHost;
               } else {
                  $dsn .= 'localhost';
               }
               if ($this->dbPort !== null) {
                  $dsn .= ';port=' . $this->dbPort;
               }
               if ($this->dbCharset !== null) {
                  $dsn .= ';charset=' . $this->dbCharset;
               }
            }
            $dsn .= ';dbname=' . $this->dbName;
            break;
         case 'sqlite':
         case 'sqlite2';
            if (isset($this->dbName)) {
               $dsn = $this->dbPDO . ':' . $this->dbName;
            }
            break;
      }

      return $dsn;
   }

   /**
    *
    * @inheritdoc
    *
    * @return PDOResultHandler
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 10.02.2008<br />
    */
   public function executeStatement($namespace, $statementName, array $params = array(), $logStatement = false, $emulatePrepare = null) {
      // TODO: Implement executeStatement() method.
   }

   /**
    *
    * @inheritdoc
    *
    * @return PDOResultHandler
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 10.02.2008<br />
    */
   public function executeTextStatement($statement, array $params = array(), $logStatement = false, $emulatePrepare = null) {
      // TODO: Implement executeTextStatement() method.
   }

   /**
    *
    * @inheritdoc
    *
    * @return PDOStatementHandler
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function prepareStatement($namespace, $fileName, $logStatement = false, $emulate = null) {
      // TODO: Implement prepareStatement() method.
   }

   /**
    * @inheritdoc
    *
    * @return PDOStatementHandler
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function prepareTextStatement($statement, $logStatement = false, $emulate = null) {

      return new PDOStatementHandler($statement,$this->dbConn,$this,$emulate);
   }
}
