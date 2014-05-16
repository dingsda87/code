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
use APF\core\benchmark\BenchmarkTimer;
use APF\core\singleton\Singleton;
use APF\core\database\DatabaseConnection;


/**
 * @package APF\core\database
 * @class AbstractStatementHandler
 * @abstract
 *
 * @since 2.1
 * @author Dingsda
 * @version
 * Version 0.1, 07.05.2014<br />
 */
abstract class AbstractStatementHandler implements Statement {

   /**
    * @protected
    * @var string the Statement
    */
   protected $statement = null;

   /** @var string $preparedStatement */
   protected $preparedStatement = null;

   /** @var null $emulate */
   protected $emulate = null;

   /** @var array $params */
   protected $params = array();

   protected $position = 0;

   protected $defaultFetchMode = DatabaseConnection::FETCH_ASSOC;

   /**
    * @param int $defaultFetchMode
    */
   public function setDefaultFetchMode($defaultFetchMode) {
      $this->defaultFetchMode = $defaultFetchMode;
   }

   /**
    * @return int
    */
   public function getDefaultFetchMode() {
      return $this->defaultFetchMode;
   }


   public function __construct($statement, $connection, DatabaseConnection $wrappedConnection, $emulate) {
      $this->statement = $statement;
      $this->dbConn = $connection;
      $this->wrappedConnection = $wrappedConnection;
      $this->emulate = $emulate;
   }

   /**
    * @public
    *
    * Binds a variable to a corresponding named or question mark placeholder in the prepared SQL statement.
    *
    * @param mixed $parameter Name of the parameter if you used named placeholder or the position of the place holder.
    * @param mixed $variable The variable to be bound given by reference.
    * @param int $dataType
    *
    * @throws DatabaseHandlerException
    * @return $this
    */
   public function bindParam($parameter, &$variable, $dataType = DatabaseConnection::PARAM_STRING) {

      $this->params[$parameter]['value'] = & $variable;
      $this->params[$parameter]['type'] = $dataType;

      return $this;
   }

   /**
    * @public
    *
    * Binds a value to a corresponding named or question mark placeholder in the prepared SQL statement.
    *
    * @param mixed $parameter name of the parameter if you used named placeholder or the position of the placeholder
    * @param mixed $value the value to be bound
    * @param int $dataType
    *
    * @throws DatabaseHandlerException
    * @return $this
    */
   public function bindValue($parameter, $value, $dataType = DatabaseConnection::PARAM_STRING) {
      return $this->bindParam($parameter, $value, $dataType);
   }

   /**
    * @public
    *
    * Executes a prepared statement.
    *
    * @param array $params Binds the values of the array to the prepared statement (optional). See Statement::bindValues().
    *
    * @throws DatabaseHandlerException
    * @return Result
    */
   public function execute(array $params = array()) {

      if (!empty($params)) {
         foreach ($params as $param => $value) {
            $this->bindParam($param, $value);
         }
      }
      if ($this->dbStmt === null) {
         $this->generateStatement();
      }
   }

   public function generateStatement() {

      $isPositionalPlaceholder = isset($this->params[1]);

      if ($isPositionalPlaceholder && $this->emulate === false) {
         $this->preparedStatement = $this->statement;

         return;
      }

      /** @var BenchmarkTimer $t */
      $t =& Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');
      $t->start(__METHOD__);

      $token = '(?=["])(?:(?:.(?!"))*.?)"|(?=[`])(?:(?:.(?!`))*.?)`';

      if ($isPositionalPlaceholder) {
         $token .= '|([?])';
      } else {
         $token .= '|:(\w+)|\[([A-Za-z0-9_\-]+)\]';
      }

      if ($this->wrappedConnection->quoteValue('\'') === '\\\'') {
         $token .= '|(?=[\'])(?:(?:.(?!(?<![\\\])\'))*.?)\'';
      } else {
         $token .= '|(?=[\'])(?:(?:.(?!\'))*.?)\'';
      }

      $this->position = 0;

      $this->preparedStatement = preg_replace_callback('#' . $token . '#uxs', array($this, 'replacePlaceholder'), $this->statement);

      $t->stop(__METHOD__);
   }

   protected function replacePlaceholder($match) {

      if (empty($match[1]) && empty($match[2])) {
         return $match[0];
      }


      $this->position++;

      if ($match[0] === '?') {

         $paramName = $this->position;

      } else {

         $paramName = (!empty($match[1])) ? $match[1] : $match[2];

      }

      if (!isset($this->params[$paramName])) {
         throw new DatabaseHandlerException('No value provided for parameter ' . $paramName, E_USER_ERROR);
      }

      if ($this->emulate === true) {

         $paramValue = $this->params[$paramName]['value'];
         $paramType = $this->params[$paramName]['type'];

         if ($paramValue === null) {
            return 'NULL';
         }

         switch ($paramType) {
            case DatabaseConnection::PARAM_STRING:
            case DatabaseConnection::PARAM_BLOB:
               $value = $this->wrappedConnection->quoteValue($paramValue);
               break;
            case DatabaseConnection::PARAM_INTEGER:
               $value = (is_int($paramValue)) ? $paramValue : $this->wrappedConnection->quoteValue($paramValue);
               break;
            case DatabaseConnection::PARAM_FLOAT:
               $value = (is_float($paramValue)) ? $paramValue : $this->wrappedConnection->quoteValue($paramValue);
               break;
         }

         return $value;

      }

      $this->params[$paramName]['position'] = $this->position;

      return '?';

   }
}
