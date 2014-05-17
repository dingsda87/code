<?php
namespace APF\core\database\mysqli;

use APF\core\logging\LogEntry;
use APF\core\logging\Logger;

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
class MySQLiConnection extends \mysqli {

   protected $lastInsertID=null;

   /** @var null|Logger $dbLog */
   protected $dbLog = null;

   public function quoteValue($value) {
      return '\'' . parent::real_escape_string($value) . '\'';
   }

   public function setLogger(&$logger) {
      $this->dbLog = $logger;
   }

   public function query($statement, $logStatement) {

      // log statements in debug mode or when requested explicitly
      if ($logStatement) {
         $this->dbLog->logEntry($this->dbLogTarget, '[MySQLiConnection::query()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }

      try {
         // execute the statement with use of the current connection!
         parent::real_query($statement);
      } catch (\Exception $e) {
         throw new DatabaseHandlerException(
               'SQLSTATE[' . parent::sqlstate . ']: ' .
               $e->getMessage() . ' (Statement: ' . $statement . ')',
               $e->getCode(), $e);
      }

      if (parent::field_count) {
         $this->lastInsertID=$this->insert_id;
         $this->logWarnings($logStatement);
         return parent::store_result();
      }

      $this->logWarnings($logStatement);
      return null;
   }

   public function prepare($statement, $logStatement) {

      // log statements in debug mode or when requested explicitly
      if ($logStatement) {
         $this->dbLog->logEntry($this->dbLogTarget, '[MySQLiConnection::prepare()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
      }

      try {
         $stmt = parent::prepare($statement);
      } catch (\mysqli_sql_exception $e) {
         throw new DatabaseHandlerException(
               'SQLSTATE[' . parent::sqlstate . ']: ' . $e->getMessage() .
               ' (Statement: ' . $statement . ' )',
               $e->getCode(), $e);
      }

      return $stmt;

   }

   public function logWarnings($debug) {

      if (parent::$warning_count&&$debug) {
         $warning = parent::get_warnings();

         do {
            $this->dbLog->logEntry($this->dbLogTarget, '[MySQLiConnection::logWarnings()] SQLSTATE['.$warning->sqlstate.'] ' .  $warning->message, LogEntry::SEVERITY_WARNING);
         } while ($warning->next());
      }
   }

}