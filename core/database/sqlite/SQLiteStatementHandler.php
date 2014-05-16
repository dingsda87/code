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
class SQLiteStatementHandler extends AbstractStatementHandler implements Statement {

   /**
    * @var \SQLiteDatabase $dbConn
    */
   protected $dbConn = null;

   protected $dbStmt=null;


   /**
    * @param string $statement
    * @param resource $wrappedConnection
    * @param SQLiteHandler $dbConn
    * @param bool $emulate
    */
   public function __construct($statement, $dbConn, SQLiteHandler $wrappedConnection, $emulate = false) {
      parent::__construct($statement, $dbConn, $wrappedConnection, $emulate);
   }

   public function execute(array $params = array()) {
      parent::execute($params);

      /** @var BenchmarkTimer $t */
      $t = Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');

      $t->start('emulate');

      $result = @$this->dbConn->query($this->preparedStatement);

      if ($result === false) {
         $errorCode=$this->dbConn->lastError();
         $message = sqlite_error_string($errorCode);
         $message .= ' (Statement: ' . $this->preparedStatement . ')';
         throw new DatabaseHandlerException( $message, $errorCode);
      }

      $t->stop('emulate');

      return new SQLiteResultHandler($result);

   }

}
