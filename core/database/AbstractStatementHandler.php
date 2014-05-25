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
use APF\core\database\mysqli\MySQLiConnection;
use APF\core\logging\Logger;
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
abstract class AbstractStatementHandler implements Statement
{

    /**
      * @var string the Statement
     */
    protected $statement = null;

    /** @var MySQLiConnection $dbConn */
    protected $dbConn = null;

    /** @var string $preparedStatement */
    protected $preparedStatement = null;

    /** @var null $emulate */
    protected $emulate = null;

    /** @var array $params */
    protected $params = array();

    /**
      * @var string Name of the log target. Must be defined within the implementation class!
     */
    protected $dbLogTarget;

    protected $position = 1;

    /**
     * @var Logger Instance of the logger.
     */
    protected $dbLog = null;

    protected $defaultFetchMode = DatabaseConnection::FETCH_ASSOC;

    protected $paramsToBind = array();

    /**
     * @param $statement
     * @param $connection
     * @param $emulate
     * @param $logStatement
     */
    public function __construct($statement, $connection, $emulate, $dbLogger, $logTarget)
    {
        $this->statement = $statement;
        $this->dbConn = $connection;
        $this->emulate = $emulate;
        $this->dbLog = $dbLogger;
        $this->dbLogTarget = $logTarget;
    }

    /**
     * @return int
     */
    public function getDefaultFetchMode()
    {
        return $this->defaultFetchMode;
    }

    /**
     * @param int $defaultFetchMode
     */
    public function setDefaultFetchMode($defaultFetchMode)
    {
        $this->defaultFetchMode = $defaultFetchMode;
    }

    /**
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
    public function bindValue($parameter, $value, $dataType = DatabaseConnection::PARAM_STRING, $paramArray = false)
    {
        return $this->bindParam($parameter, $value, $dataType);
    }

    /**
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
    public function bindParam($parameter, &$variable, $dataType = DatabaseConnection::PARAM_STRING, $paramArray = false)
    {
        if(!$paramArray && (is_array($variable) || is_array($dataType))){
            throw new DatabaseHandlerException(/** todo */);
        }

        $this->params[$parameter]['value'] = & $variable;

        if (!$paramArray) {

            $this->params[$parameter]['type'] = $dataType;

            return $this;
        }

        $this->params[$parameter]['value']= &$variable;

        if(!is_array($dataType)){
            foreach($variable as $key => $dummy){
                $this->params[$parameter]['type'][$key]=$dataType;
            }
        }

        return $this;
    }

    /**
      *
     * Executes a prepared statement.
     *
     * @param array $params Binds the values of the array to the prepared statement (optional). See Statement::bindValues().
     *
     * @throws DatabaseHandlerException
     * @return Result
     */
    public function execute(array $params = array())
    {
        echo 'blub';

        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $this->bindParam($param, $value);
            }
        }
        if ($this->dbStmt === null) {
            echo('blo');
            $this->generateStatement();
        }
    }

    public function generateStatement()
    {

        $isPositionalPlaceholder = isset($this->params[1]);

//      if ($isPositionalPlaceholder && $this->emulate === false) {
//         $this->preparedStatement = $this->statement;
//
//         return;
//      }

        /** @var BenchmarkTimer $t */
        $t =& Singleton::getInstance('APF\core\benchmark\BenchmarkTimer');
        $t->start(__METHOD__);

        $token = '(?=["])(?:(?:.(?!"))*.?)"|(?=[`])(?:(?:.(?!`))*.?)`';

        if ($isPositionalPlaceholder) {
            $token .= '|([?])';
        } else {
            $token .= '|:(\w+)|\[([A-Za-z0-9_\-]+)\]';
        }

        if ($this->dbConn->quote('\'') === '\\\'') {
            $token .= '|(?=[\'])(?:(?:.(?!(?<![\\\])\'))*.?)\'';
        } else {
            $token .= '|(?=[\'])(?:(?:.(?!\'))*.?)\'';
        }

        $this->position = 0;

        $this->preparedStatement = preg_replace_callback('#' . $token . '#uxs', array($this, 'replacePlaceholder'), $this->statement);
        var_dump($this->preparedStatement);
        $t->stop(__METHOD__);
    }

    protected function replacePlaceholder($match)
    {

        if (empty($match[1]) && empty($match[2])) {
            return $match[0];
        }


        if ($match[0] === '?') {

            $paramName = $this->position;

        } else {

            $paramName = (!empty($match[1])) ? $match[1] : $match[2];

        }

        if (!isset($this->params[$paramName])) {
            throw new DatabaseHandlerException('No value provided for parameter ' . $paramName, E_USER_ERROR);
        }

        if ($this->params[$paramName]['type'] === DatabaseConnection::PARAM_IDENTIFIER) {
            if ()
        }

        if ($this->emulate === true) {
            return $this->quote($this->params[$paramName]['value'], $this->params[$paramName][])
        }

        $this->params[$paramName]['position'] = $this->position++;

        return '?';

    }

    abstract protected function quote($value, $type);

    abstract protected function quoteIdentifier($identifier);


}
