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
abstract class AbstractDatabaseConnection extends APFObject{

    /**
     * @var string Database server.
     */
    protected $dbHost = null;

    /**
     * @var string Database user.
     */
    protected $dbUser = null;

    /**
     * @var string Password for the database.
     */
    protected $dbPass = null;

    /**
     * @var string Name of the database.
     */
    protected $dbName = null;

    /**
     * @var string Port for connection.
     */
    protected $dbPort = null;

    /**
     * @var string Socket for connection.
     */
    protected $dbSocket = null;

    /**
     * @var resource Database connection resource.
     */
    protected $dbConn = null;

    /**
     * @var string Name of the log target. Must be defined within the implementation class!
     */
    protected $dbLogTarget;

    /**
     * @var int Auto increment id of the last insert.
     */
    protected $lastInsertId;

    /**
     * @var string Indicates the charset of the database connection.
     *
     * For mysql databases, see http://dev.mysql.com/doc/refman/5.0/en/charset-connection.html
     * for more details.
     */
    protected $dbCollation = null;

    /**
     * @var string Indicates the collation of the database connection.
     *
     * For mysql databases, see http://dev.mysql.com/doc/refman/5.0/en/charset-connection.html
     * for more details.
     */
    protected $dbCharset = null;

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

    public function init($initParam) {

        if ($this->isInitialized == false) {

            if (isset($initParam['Host'])) {
                $this->setHost($initParam['Host']);
            }

            if (isset($initParam['User'])) {
                $this->setUser($initParam['User']);
            }

            if (isset($initParam['Pass'])) {
                $this->setPass($initParam['Pass']);
            }

            $this->setDatabaseName($initParam['Name']);

            if (isset($initParam['Port'])) {
                $this->setPort($initParam['Port']);
            }

            if (isset($initParam['Socket'])) {
                $this->setSocket($initParam['Socket']);
            }

            if (isset($initParam['DebugMode'])) {
                $this->setDebug($initParam['DebugMode']);
            }

            if (isset($initParam['Charset'])) {
                $this->setCharset($initParam['Charset']);
            }
            if (isset($initParam['Collation'])) {
                $this->setCollation($initParam['Collation']);
            }

            $this->isInitialized = true;
            $this->setup();
        }
    }

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
     * Defines the collation of the database connection.
     * <p/>
     * Can be used for manual or DI configuration.
     *
     * @param string $collation The desired collation.
     *
     * @author Christian Achatz
     * @version
     * Version 0.1, 07.05.2012<br />
     */
    public function setCollation($collation) {
        $this->dbCollation = $collation;
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
     * @abstract
     *
     * Provides internal service to open a database connection.
     *
     * @throws DatabaseHandlerException In case of connection issues.
     *
     * @author Christian Achatz
     * @version
     * Version 0.1, 10.02.2008<br />
     */
    abstract protected function connect();

    /**
     * @abstract
     *
     * Provides internal service to close a database connection.
     *
     * @throws DatabaseHandlerException In case of connection issues.
     *
     * @author Christian Achatz
     * @version
     * Version 0.1, 10.02.2008<br />
     */
    abstract protected function close();

    /**
     *
     * Configures the client connection's charset and collation.
     *
     * @see http://dev.mysql.com/doc/refman/5.0/en/charset-connection.html
     *
     * @author Christian Achatz
     * @version
     * Version 0.1, 08.02.2010<br />
     */
    protected function initCharsetAndCollation() {
        if ($this->dbCharset !== null || $this->dbCollation !== null) {
            $setArray = array();
            if ($this->dbCharset !== null) {
                $setArray[] = ' NAMES \'' . $this->dbCharset . '\'';
            }
            if ($this->dbCollation !== null) {
                $setArray[] = ' collation_connection = \'' . $this->dbCollation . '\'';
                $setArray[] = ' collation_database = \'' . $this->dbCollation . '\'';
            }
            $statement = 'SET' . implode(',', $setArray);
            $this->executeTextStatement($statement);
        }
    }

}
