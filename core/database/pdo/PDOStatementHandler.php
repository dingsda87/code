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
use APF\core\database\DatabaseConnection;
use APF\core\database\DatabaseHandlerException;
use APF\core\database\Statement;
use APF\core\database\AbstractStatementHandler;
use APF\core\singleton\Singleton;

/**
 * @package APF\core\database\pdo
 * @class PDOStatement
 */
class PDOStatementHandler extends AbstractStatementHandler implements Statement {

   /**
    * @var $dbStmt \PDOStatement
    */
   protected $dbStmt = null;
   /**
    * @var null|\PDO $dbConn
    */
   protected $dbConn = null;

   protected $paramTypeMap = array(
         DatabaseConnection::PARAM_STRING  => \PDO::PARAM_STR,
         DatabaseConnection::PARAM_INTEGER => \PDO::PARAM_INT,
         DatabaseConnection::PARAM_BLOB    => \PDO::PARAM_LOB,
         DatabaseConnection::PARAM_FLOAT   => \PDO::PARAM_STR
   );

   /**
    * @param string $statement
    * @param PDOHandler $wrappedConnection
    * @param \PDO $dbConn
    * @param bool $emulate
    */
   public function __construct($statement, \PDO $dbConn, PDOHandler $wrappedConnection, $emulate = false) {
      parent::__construct($statement, $dbConn, $wrappedConnection, $emulate);
   }

   public function execute(array $params = array()) {
      parent::execute($params);

      /** @var BenchmarkTimer $t */
      $t=Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');

      if ($this->emulate === true) {
         $t->start('emulate');
         try {
            $pdoResult = $this->dbConn->query($this->preparedStatement);
         } catch (\PDOException $e) {
            $errorNumber = $e->errorInfo[1];
            if ($errorNumber === 2014) {
               throw new DatabaseHandlerException('Cannot execute queries while other unbuffered queries are active. ' .
                     'Use PDOResult->freeResult to free up the connection.', $errorNumber, $e);
            }
            throw new DatabaseHandlerException(
                  $e->getMessage() . '
               (Statement: ' . $this->preparedStatement . ')'
                  , $errorNumber, $e
            );
         }
         if ($pdoResult->columnCount() === 0) {
            $this->affectedRows = $pdoResult->rowCount();

            return null;
         }
         $t->stop('emulate');

         return new PDOResultHandler($pdoResult);
      }

      if ($this->dbStmt === null) {
         $t->start('first-prepare');

         try {
            $this->dbStmt = $this->dbConn->prepare($this->preparedStatement);
         } catch (\PDOException $e) {
            $errorNumber = $e->errorInfo[1];
            if ($errorNumber === 2014) {
               throw new DatabaseHandlerException('Cannot execute queries while other unbuffered queries are active. ' .
                     'Use PDOResult->freeResult to free up the connection.', $errorNumber, $e);
            }
            throw new DatabaseHandlerException(
                  $e->getMessage() . '
               (Statement: ' . $this->statement . ')'
                  , $errorNumber, $e
            );
         }
         $t->stop('first-prepare');
      }

      $this->bindValues();
      $this->dbStmt->execute();

      return new PDOResultHandler($this->dbStmt);

   }

   protected function bindValues() {
      /** @var BenchmarkTimer $t */
      $t=Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');
      $t->start(__METHOD__);
      foreach ($this->params as $attributes) {
         $this->dbStmt->bindValue(
               $attributes['position'],
               $attributes['value'],
               $this->paramTypeMap[$attributes['type']]
         );
      }
      $t->stop(__METHOD__);
   }
}
