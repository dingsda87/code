<?php
namespace APF\core\database;

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
use APF\core\configuration\ConfigurationException;
use APF\core\database\config\StatementConfiguration;
use APF\core\logging\Logger;
use APF\core\pagecontroller\APFObject;
use APF\core\singleton\Singleton;

/**
 * @package APF\core\database
 * @class AbstractDatabaseHandler
 * @abstract
 *
 * Defines the scheme of a database handler. Forms the base class for all database
 * abstraction layer classes.
 * <p/>
 * To initialize database connections using the DIServiceManager, you may use the
 * following service definition section:
 * <code>
 * [news-store-db]
 * servicetype = "SINGLETON"
 * class = "APF\core\database\mysqli\MySQLiHandler"
 * setupmethod = "setup"
 *
 * conf.host.method = "setHost"
 * conf.host.value = "..."
 *
 * conf.port.method = "setPort"
 * conf.port.value = "..."
 *
 * conf.name.method = "setDatabaseName"
 * conf.name.value = "..."
 *
 * conf.user.method = "setUser"
 * conf.user.value = "..."
 *
 * conf.pass.method = "setPass"
 * conf.pass.value = "..."
 *
 * [conf.socket.method = "setSocket"
 * conf.socket.value = "..."]
 *
 * conf.charset.method = "setCharset"
 * conf.charset.value = "..."
 *
 * conf.collation.method = "setCollation"
 * conf.collation.value = "..."
 *
 * [conf.debug.method = "setDebug"
 * conf.debug.value = "..."]
 *
 * [conf.log-file-name.method = "setLogTarget"
 * conf.log-file-name.value = "..."]
 * </code>
 * This connection definition may be injected into another service using some kind of
 * service definition like this:
 * <code>
 * [GORM]
 * servicetype = "SINGLETON"
 * class = "APF\modules\genericormapper\data\GenericORRelationMapper"
 * setupmethod = "setup"
 * ...
 * init.db-conn.method = "setDatabaseConnection"
 * init.db-conn.namespace = "VENDOR\sample\namespace"
 * init.db-conn.name = "news-store-db"
 * </code>
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 10.02.2008<br />
 * Version 0.2, 07.08.2010 (Added *_FETCH_MODE constants and optional second fetchData() parameter)<br />
 */
abstract class AbstractDatabaseHandler extends APFObject implements DatabaseConnection {

   /**
    * @var boolean Indicates, if the handler runs in debug mode. This means, that all
    * statements executed are written into a dedicated logfile.
    */
   protected $dbDebug = false;

   /**
    * @var resource Database connection resource.
    */
   protected $dbConn = null;

   /**
    * @var Logger Instance of the logger.
    */
   protected $dbLog = null;

   /**
    * @var string Name of the log target. Must be defined within the implementation class!
    */
   protected $dbLogTarget;

   /**
    * @var int Auto increment id of the last insert.
    */
   protected $lastInsertId;

   /**
    * @var bool
    */
   protected $emulate = false;

   protected $defaultFetchMode = DatabaseConnection::FETCH_ASSOC;


   /**
    *
    * Defines the name of the log target for the debugging feature.
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param string $logTarget The name of debug log file.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setLogTarget($logTarget) {
      $this->dbLogTarget = $logTarget;
   }

/*
    public function init($initParam) {

      if ($this->isInitialized == false) {


         $this->isInitialized = true;
         $this->setup();
      }
   }*/

   /**
    *
    * Defines the database host to connect to.
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param string $host The database host to connect to.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setHost($host) {
      $this->dbHost = $host;
   }

   /**
    *
    * Defines the user that is used to connect to the database.
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param string $user The database user.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setUser($user) {
      $this->dbUser = $user;
   }

   /**
    *
    * Defines the password used to connect to the database.
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param string $pass The database password.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setPass($pass) {
      $this->dbPass = $pass;
   }

   /**
    *
    * Defines the database name to connect to.
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param string $name Th name of the database to connect to.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setDatabaseName($name) {
      $this->dbName = $name;
   }

   /**
    *
    * Defines the database port to connect to.
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param int $port The database port to connect to.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setPort($port) {
      $this->dbPort = $port;
   }

   /**
    *
    * Defines the socket to connect to.
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param string $socket The socket descriptor.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setSocket($socket) {
      $this->dbSocket = $socket;
   }

   /**
    *
    * Enables (true) or disables (false) the internal debugging feature (=statement logging).
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param boolean $debug <em>True</em> in case the logging feature should be switched on, <em>false</em> otherwise.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setDebug($debug) {
      $this->dbDebug = ($debug == 'true' || $debug == '1') ? true : false;
   }

   /**
    *
    * Defines the character set of the database connection.
    * <p/>
    * Can be used for manual or DI configuration.
    *
    * @param string $charset The desired character set.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setCharset($charset) {
      $this->dbCharset = $charset;
   }

   /**
    *
    * Implements an initializer method to setup derived classes using the
    * DIServiceManager.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.05.2012<br />
    */
   public function setup() {
      $this->connect();
      $this->dbLog=& Singleton::getInstance('APF\core\logging\Logger');
   }

   /**
    *
    * Loads a statement file.
    *
    * @param string $namespace The namespace of the statement file.
    * @param string $name The name of the statement's file body (e.g. load_entries.sql).
    *
    * @return string The statement.
    * @throws DatabaseHandlerException In case the statement file cannot be loaded.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 03.02.2011<br />
    */
   protected function getPreparedStatement($namespace, $name) {
      try {
         $config = $this->getConfiguration($namespace, $name);
      } catch (ConfigurationException $e) {
         throw new DatabaseHandlerException($e->getMessage(), E_USER_ERROR, $e);
      }

      /* @var $config StatementConfiguration */

      return $config->getStatement();
   }

   /**
    *
    * @deprecated Use executeStatement() with fetchAll() instead.
    *
    * @param $namespace
    * @param $statementFile
    * @param array $params
    * @param bool $logStatement
    *
    * @return array
    */
   public function executeBindStatement($namespace, $statementFile, array $params = array(), $logStatement = false) {
      $result = $this->executeStatement($namespace, $statementFile, $params, $logStatement, false);

      return $result->fetchAll(Result::FETCH_ASSOC);
   }


   /**
    *
    * @deprecated  Use executeTextStatement with fetchAll instead
    *
    * @param $statement
    * @param array $params
    * @param bool $logStatement
    *
    * @return array
    */
   public function executeTextBindStatement($statement, array $params = array(), $logStatement = false) {
      $result = $this->executeTextStatement($statement, $params, $logStatement, false);

      return ($result!==null)?$result->fetchAll():null;
   }

   /**
    *
    * @deprecated Use Result->fetchData() instead.
    *
    * Fetches a record from the database.
    *
    * @param Result $result The result of the current statement.
    * @param int $type The type the returned data should have. Use the static FETCH_* constants.
    *
    * @return mixed The result array. Returns false if no row was found.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 20.09.2009<br />
    * Version 0.2, 08.08.2010 (Added optional second parameter) <br />
    */
   public function fetchData(Result $result, $fetchMode = null) {

      if($fetchMode===null){
         $fetchMode=$this->defaultFetchMode;
      }

      return $result->fetchData($fetchMode);
   }

   /**
    *
    * @deprecated Use Result->getNumRows() instead.
    *
    * Returns the number of selected rows by a select Statement. Some databases do not support
    * this so you should not relied on this behavior for portable applications.
    *
    * @param Result $result The result of the current statement.
    *
    * @return int The number of selected rows.
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function getNumRows(Result $result) {
      return $result->getNumRows();
   }

   public function setDefaultFetchMode($fetchMode) {
      $this->defaultFetchMode = $fetchMode;
   }

   public function getDefaultFetchMode() {
      return $this->defaultFetchMode;
   }

}
