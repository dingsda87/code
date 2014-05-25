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
class MySQLiStatementHandler extends AbstractStatementHandler implements Statement
{

    /** @var $dbConn \mysqli */
    protected $dbConn = null;

    /** @var $dbStmt \mysqli_stmt */
    protected $dbStmt = null;

    protected $dbLog = null;

    protected $paramTypeMap = array(
        DatabaseConnection::PARAM_BLOB => 'b',
        DatabaseConnection::PARAM_FLOAT => 's',
        DatabaseConnection::PARAM_INTEGER => 's',
        DatabaseConnection::PARAM_STRING => 's'
    );

    /**
     * @inheritdoc
     *
     * @return MySQLiResultHandler
     */
    public function execute(array $params = array())
    {
        parent::execute($params);

        if ($this->emulate === true) {
            return $this->dbConn->query($this->preparedStatement, $this->dbDebug);

        }

        if ($this->dbStmt === null) {

            $this->dbStmt = $this->dbConn->prepare($this->preparedStatement, $this->dbDebug);
        }

        $this->bindValues();
        $this->dbStmt->execute();
        $this->dbConn->logWarnings($this->dbDebug);

        return new MySQLiResultHandler($this->dbStmt->get_result());
    }

    protected function bindValues()
    {
        if ($this->emulate === true) {
            return;
        }

        /** @var BenchmarkTimer $t */
        $t =& Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');
        $t->start(__METHOD__);

        $sortedParams = array(0 => null);
        foreach ($this->params as $key => $attribute) {
            $sortedParams[0] .= $this->paramTypeMap[$attribute['type']];
            $position = (isset($attribute['position'])) ? $attribute['position'] : $key + 1;
            $sortedParams[$position] = $attribute['value'];
        }

        sort($sortedParams, SORT_NUMERIC);

        $reflectionMethod = new \ReflectionMethod('mysqli_stmt', 'bind_param');
        $reflectionMethod->invokeArgs($this->dbStmt, $sortedParams);
        $t->stop(__METHOD__);

    }

    public function query($statement, $logStatement = false)
    {

        // log statements in debug mode or when requested explicitly
        if ($logStatement) {
            $this->dbLog->logEntry($this->dbLogTarget, '[MySQLiConnection::query()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
        }

        try {
            // execute the statement with use of the current connection!
            parent::real_query($statement);
        } catch (\mysqli_sql_exception $e) {
            throw new DatabaseHandlerException(
                'SQLSTATE[' . $this->sqlstate . ']: ' .
                $e->getMessage() . ' (Statement: ' . $statement . ')',
                $e->getCode(), $e);
        }

        if ($this->field_count) {
            $this->lastInsertID = $this->insert_id;
            $this->logWarnings($logStatement);
            return new MySQLiResultHandler(parent::store_result());
        }

        $this->logWarnings($logStatement);
        return null;
    }

    public function logWarnings($debug)
    {

        if ($debug && parent::$warning_count) {
            $warning = parent::get_warnings();

            do {
                $this->dbLog->logEntry($this->dbLogTarget, '[MySQLiConnection::logWarnings()] SQLSTATE[' . $warning->sqlstate . '] ' . $warning->message, LogEntry::SEVERITY_WARNING);
            } while ($warning->next());
        }
    }

    public function prepare($statement, $logStatement = false)
    {

        // log statements in debug mode or when requested explicitly
        if ($logStatement) {
            $this->dbLog->logEntry($this->dbLogTarget, '[MySQLiConnection::prepare()] Current statement: ' . $statement, LogEntry::SEVERITY_DEBUG);
        }

        try {
            $stmt = parent::prepare($statement);
        } catch (\mysqli_sql_exception $e) {
            throw new DatabaseHandlerException(
                'SQLSTATE[' . $this->sqlstate . ']: ' . $e->getMessage() .
                ' (Statement: ' . $statement . ' )',
                $e->getCode(), $e);
        }

        return $stmt;

    }

    protected function quote($value, $dataType = DatabaseConnection::PARAM_STRING)
    {
        switch ($dataType) {
            case DatabaseConnection::PARAM_BIT:
                return '\b\'' . $this->dbConn->real_escape_string($value) . '\'';
            case DatabaseConnection::PARAM_INTEGER:
            case DatabaseConnection::PARAM_FLOAT:
                return (is_numeric($value)) ? $value : '\'' . $this->dbConn->real_escape_string($value) . '\'';
            case DatabaseConnection::PARAM_STRING:
                return '\'' . $this->dbConn->real_escape_string($value) . '\'';
        }
    }

    protected function quoteIdentifier($identifier)
    {

        if (!is_array($identifier)) {
            return '`' . str_replace(array('.', '`'), array('`.`', '``'), $identifier) . '`';
        }

        array_walk($identifier, array($this, 'quoteIdentifier'));
        return implode(', ', $identifier);

    }
}
