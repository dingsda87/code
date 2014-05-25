<?php
namespace APF\core\database\mysqli;

use APF\core\database\DatabaseConnection;
use APF\core\logging\LogEntry;
use APF\core\logging\Logger;
use APF\core\database\DatabaseHandlerException;
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

    protected $dbLogTarget='mysqli';



}