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
use APF\core\benchmark\BenchmarkTimer;
use APF\core\database\DatabaseConnection;
use APF\core\database\DatabaseHandlerException;
use APF\core\database\AbstractStatementHandler;
use APF\core\database\Statement;
use APF\core\singleton\Singleton;


/**
 * @package APF\core\database\mysqli
 * @class MySQLiStatement
 */
class MySQLiStatementHandler extends AbstractStatementHandler implements Statement {

   /** @var $wrappedConnection MySQLiHandler */
   protected $wrappedConnection = null;

   /** @var $dbConn \mysqli */
   protected $dbConn = null;

   /** @var $dbStmt \mysqli_stmt */
   protected $dbStmt = null;

   protected $paramTypeMap = array(
         DatabaseConnection::PARAM_BLOB    => 'b',
         DatabaseConnection::PARAM_FLOAT   => 's',
         DatabaseConnection::PARAM_INTEGER => 's',
         DatabaseConnection::PARAM_STRING  => 's'
   );


   protected function bindValues() {
      if ($this->emulate === true) {
         return;
      }
      /** @var BenchmarkTimer $t */
      $t =& Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');
      $t->start(__METHOD__);

      $sortedParams = array(0 => null);
      foreach ($this->params as $key => $attribute) {
         $sortedParams[0] .= $this->paramTypeMap[$attribute['type']];
         $sortedParams[$attribute['position']] = $attribute['value'];
      }

      sort($sortedParams, SORT_NUMERIC);

      $reflectionMethod = new \ReflectionMethod('mysqli_stmt', 'bind_param');
      $reflectionMethod->invokeArgs($this->dbStmt, $sortedParams);
      $t->stop(__METHOD__);

   }


   /**
    * @inheritdoc
    *
    * @return MySQLiResultHandler
    */
   public function execute(array $input_params = array()) {
      parent::execute($input_params);

      if ($this->emulate === true) {
         try {
            // execute the statement with use of the current connection!
            $this->dbConn->real_query($this->preparedStatement);
         } catch (\Exception $e) {
            throw new DatabaseHandlerException(
                  'SQLSTATE[' . $this->dbConn->sqlstate . ']: ' .
                  $e->getMessage() . ' (Statement: ' . $this->preparedStatement . ')',
                  $e->getCode(), $e);
         }

         if ($this->dbConn->field_count) {
            return new MySQLiResultHandler($this->dbConn->store_result());
         }

         return null;
      }

      if ($this->dbStmt === null) {

         try {
            $preparedStatement = $this->dbConn->prepare($this->preparedStatement);
         } catch (\mysqli_sql_exception $e) {
            throw new DatabaseHandlerException(
                  'SQLSTATE[' . $this->dbConn->sqlstate . ']: ' . $e->getMessage() .
                  ' (Statement: ' . $this->statement . ' )',
                  $e->getCode(), $e);
         }
      }

      $this->bindValues();
      $this->dbStmt->execute();

      return new MySQLiResultHandler($this->dbStmt->get_result());
   }

}
