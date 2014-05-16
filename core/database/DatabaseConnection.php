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
use APF\core\service\APFDIService;

/**
 * @package APF\core\database
 * @class DatabaseHandlerException
 *
 * Represents an exception, that is thrown during database operations.
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 08.03.2010<br />
 */
class DatabaseHandlerException extends \Exception {

}

/**
 * @package APF\core\database
 * @interface DatabaseConnection
 *
 * This interface defines the structure and functionality of APF database connections.
 *
 * @since 1.15
 *
 * @author Christian Achatz, dingsda
 * @version
 * Version 0.1, 07.05.2012<br />
 * Version 0.2, 03.0.5.2014 (Enhanced interface definition)<br />
 */
interface DatabaseConnection extends APFDIService {

   const ASSOC_FETCH_MODE = 1;
   const OBJECT_FETCH_MODE = 2;
   const NUMERIC_FETCH_MODE = 3;

   const FETCH_ASSOC = 1;
   const FETCH_OBJECT = 2;
   const FETCH_NUMERIC = 3;

   const PARAM_STRING = 1;
   const PARAM_INTEGER = 2;
   const PARAM_BLOB = 3;
   const PARAM_FLOAT = 4;

   /**
    * Setups the connection using the DIServiceManager.
    */
   public function setup();

   /**
    *
    * Returns the last insert id generated by auto_increment or trigger.
    *
    * @return int The last insert id.
    *
    * @author Christian Schäfer
    * @version
    * Version 0.1, 04.01.2006<br />
    */
   public function getLastID();

   /**
    *
    * Executes a statement, located within a statement file. The place holders contained in the
    * file are replaced by the given values.
    *
    * @param string $namespace Namespace of the statement file.
    * @param string $statementName Name of the statement file (file body!).
    * @param string[] $params A list of statement parameters.
    * @param bool $logStatement Indicates, if the statement is logged for debug purposes.
    * @param bool $emulatePrepare Emulates statement preparation.
    *
    * @internal param int $placeHolderType Type of place holder used within this statement (see PLACE_HOLDER_* for details).
    *
    * @return Result The database result resource.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 10.02.2008<br />
    */
   public function executeStatement($namespace, $statementName, array $params = array(), $logStatement = false, $emulatePrepare = null);

   /**
    *
    * Executes a statement applied as a string to the method and returns the
    * result pointer.
    *
    * @param string $statement The statement string.
    * @param string[] $params A list of statement parameters.
    * @param boolean $logStatement Indicates, whether the given statement should be logged for debug purposes.
    * @param bool $emulatePrepare Emulates statement preparation.
    *
    * @return Result The database result resource.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 10.02.2008<br />
    */
   public function executeTextStatement($statement, array $params = array(), $logStatement = false, $emulatePrepare = null);

   /**
    *
    * @deprecated Use quoteValue which escapes AND quotes a string instead.
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
   public function escapeValue($value);

   /**
    *
    * Quotes and escapes given values to be SQL injection save.
    *
    * @param string $value The un-escaped value.
    *
    * @return string The quoted and escaped string.
    */
   public function quoteValue($value);

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
   public function prepareStatement($namespace, $fileName, $logStatement = false, $emulate = null);

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
   public function prepareTextStatement($statement, $logStatement = false, $emulate = null);

   /**
    * @public
    *
    * Turns off autocommit mode! Changes to the database via PDO are not committed until calling <em>commit()</em>.
    * <em>rollBack()</em> will roll back all changes and turns on the autocommit mode!
    *
    * @return boolean
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function beginTransaction();

   /**
    * @public
    *
    * Commits a transaction and turns on the autocommit mode!
    *
    * @return boolean
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function commit();

   /**
    * @public
    *
    * Rolls back the current transaction
    *
    * @return boolean
    *
    * @author Tobias Lückel (megger)
    * @version
    * Version 0.1, 11.04.2012<br />
    */
   public function rollback();

   /**
    * @public
    *
    * Returns the amount of rows, that are affected by a previous update, insert or delete call.
    *
    * @param int $unusedParam
    *
    * @return int An integer greater than zero indicates the number of rows affected or retrieved.
    * Zero indicates that no records where updated for an UPDATE statement,
    * no rows matched the WHERE clause in the query or that no query has yet been executed.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 24.02.2008<br />
    */
   public function getAffectedRows($unusedParam = null);

}
