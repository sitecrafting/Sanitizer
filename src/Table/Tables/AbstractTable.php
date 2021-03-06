<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Philip Elson <phil@pegasus-commerce.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Date: 18/05/15
 * Time: 12:42
 */
namespace Pegasus\Application\Sanitizer\Table\Tables;

use Pegasus\Application\Sanitizer\Columns\Types\AbstractDataType;
use Pegasus\Application\Sanitizer\Engine\Exceptions\FatalEngineException;
use Pegasus\Application\Sanitizer\Resource\Object;
use Pegasus\Application\Sanitizer\Table;
use Pegasus\Application\Sanitizer\Table\Exceptions\TableException;
use Pegasus\Application\Sanitizer\Table\Exceptions\TableCommandFoundException;
use Pegasus\Application\Sanitizer\Table\Exceptions\TableCommentException;
use Pegasus\Application\Sanitizer\Columns\Types;
use Pegasus\Application\Sanitizer\Engine\EngineInterface;

abstract class AbstractTable extends Object
{
    const KEY_TABLE_TYPE        = 'type';

    protected $_truncate         = false;

    protected $_delete           = false;

    protected $_engine           = null;

    protected $_primaryKeyName   = null;

    protected $_isQuick          = false;

    public function __construct(EngineInterface $engine)
    {
        if (null == $engine) {
            throw new TableException("Someone has passed this table a null engine");
        }

        $this->_engine = $engine;
    }

    public function getEngine() 
    {
        if (null == $this->_engine) {
            throw new TableException("No engine found!");
        }

        return $this->_engine;
    }

    protected function getIsQuickSanitisation() 
    {
        return $this->_isQuick;
    }

    public function setIsQuickSanitisation($isQuick) 
    {
        return $this->_isQuick = $isQuick;
    }

    public function addColumn(AbstractDataType $column)
    {
        if (null == $column) {
            return false;
        }

        if (false == isset($this->_data['columns'])) {
            $this->_data['columns'] = array();
        }

        if (false == in_array($column, $this->_data['columns'])) {
            $this->_data['columns'][] = $column;
            return true;
        }

        return false;
    }

    public function removeColumn(AbstractDataType $column)
    {
        if (false == isset($this->_data['columns'])) {
            return; //it's not in something that doesn't exist!.
        }

        foreach ($this->_data['columns'] as $key => $value) {
            if ($value == $column) {
                unset($this->_data['columns'][$key]);
            }
        }
    }

    /**
     * Returns the array of columns
     *
     * @return array
     */
    public function getColumns()
    {
        if (false == isset($this->_data['columns'])) {
            return array();
        }

        return $this->_data['columns'];
    }

    /**
     * Returns the type identifier
     *
     * @return string
     * @throws \Exception if not overridden
     */
    public static function getType()
    {
        throw new \Exception('Method to be re-written by children');
    }

    /**
     * Method returns true if the command is valid.
     *
     * @param  $command
     * @return bool
     */
    public function isCommandValid($command)
    {
        $this->_truncate             = false;
        $this->_delete               = false;

        switch($command) {
            case 'truncate' :
                $this->_truncate     = true;
                return true;
            case 'delete' :
                $this->_delete       = true;
                return true;
        }
        return false;
    }

    /**
     * Method to set the table data and have each class set up its own instance based on that data.
     * This is also where each class should validate that the data within it is correct.
     *
     * @param  array $tableData
     * @return mixed
     * @throws TableException for various reasons!...
     * @throws TableCommandFoundException when a command has been found rendering further analysis void.
     * @throws TableCommentException when the table definition only contains a comment.
     */
    public function setTableData(array $tableData)
    {
        unset($tableData['type']);  /* We have already loaded this via type */

        if (null == $this->getTableName()) {
            throw new TableException('Table name must be set for data manipulation');
        }

        //Only data is a comment for this row
        if (1 == sizeof($tableData) && true == isset($tableData['comment'])) {
            $this->getTerminalPrinter()->printLn("Comment[{$this->getTableName()}]: ".$tableData['comment'], 'general');
            $msg = "This table '{$this->getTableName()}' only has a comment in the config, skipping";
            throw new TableCommentException($msg);
        }

        //Command is the most important option, it will override all others.
        if (true == isset($tableData['command'])) {
            $command = $tableData['command'];

            if (false == $this->isCommandValid($command)) {
                throw new TableException("Command '{$command}' is set but not valid for table ".$this->getTableName());
            }

            $this->setCommand($command);
        }

        if (false == $this->exists()) {
            $msg = "Table '{$this->getTableName()}' not found in database '{$this->getDatabaseName()}'";
            throw new TableException($msg);
        }

        return true;
    }

    /**
     * @param   $configDataType
     * @param   $columnData
     * @throws  TableException when a type can not be found.
     * @return  AbstractDataType
     */
    protected function getInstanceFromType($configDataType, array $columnData)
    {
        $column = null;

        switch ($configDataType) {
            case 'timestamp' :
                $column = new Types\Timestamp($columnData);
                break;
            case 'text' :
                $column = new Types\Text($columnData);
                break;
            case 'varchar' :
                $column = new Types\Varchar($columnData);
                break;
            case 'integer' :
                $column = new Types\Integer($columnData);
                break;
            default :
                $msg = "No column types could be found by '{$configDataType}' on table '{$this->getTableName()}";
                throw new TableException($msg);
        }

        if (null != $column) {
            $column->setEngine($this->_engine);
            $column->setTableName($this->getTableName());
            $column->setTable($this);
        }

        return $column;
    }

    /**
     * Returns true if the operation is to do a truncate
     *
     * @return bool
     */
    public function doTruncate()
    {
        return $this->_truncate;
    }

    /**
     * Returns true if the operation is to do a delete
     *
     * @return bool
     */
    public function doDelete()
    {
        return $this->_delete;
    }

    /**
     * Returns true if this table is to execute a command rather than process data
     *
     * @return bool
     */
    public function doCommand()
    {
        return (true == $this->doDelete() || true == $this->doTruncate());
    }

    /**
     * Returns true if the table exists
     *
     * @return mixed
     */
    public function exists()
    {
        return $this->_engine->tableExists($this->getTableName());
    }

    /**
     * This method returns the name of the database
     *
     * @return mixed
     */
    public function getDatabaseName()
    {
        return $this->_engine->getDatabaseName();
    }

    /**
     * This method retusn the an array in the format required by the db abstraction class.
     *
     * array('primaty_key' => 'value')
     *
     * @param   $row Is the row which contains the primary key and the value etc,
     *          it is just a dumb array so there's no way to determine which key
     *          is the primary key without querying the db.
     * @return array
     */
    public function getPrimaryKeyData($row)
    {
        if (null == $this->_primaryKeyName) {
            $this->_primaryKeyName = $this->_engine->getPrimaryKeyName($this->getTableName());
        }

        return array($this->_primaryKeyName => $row[$this->_primaryKeyName]);
    }

    /**
     * Returns this tables primary key
     *
     * @param  null $tableName
     * @return string
     */
    public function getPrimaryKeyName($tableName=null)
    {
        if (null == $tableName) {
            $tableName = $this->getTableName();
        }

        return $this->_engine->getPrimaryKeyName($tableName);
    }

    /**
     * @return bool
     */
    public function hasExecutedCommand()
    {
        $printer = $this->getTerminalPrinter();

        if (true == $this->doCommand()) {

            if (true == $this->doTruncate()) {
                $printer->printLn("Truncating {$this->getTableName()} ", 'notice');
                if (false === $this->_engine->truncate($this->getTableName())) {
                    $message = "Truncation didn't occur correctly on {$this->getTableName()}, rows found";
                    throw new FatalEngineException($message);
                }
                $printer->printLn("Truncated {$this->getTableName()} ", 'notice');
                return true;
            }

            if (true == $this->doDelete()) {
                $printer->printLn("Deleting {$this->getTableName()} ", 'notice');
                $this->_engine->delete($this->getTableName(), null);
                $printer->printLn("Deleted {$this->getTableName()} ", 'notice');
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array of the columns which are to be included in the select.
     *
     * @return array
     */
    protected function getSelectColumns()
    {
        $selectColumns = array($this->getPrimaryKeyName($this->getTableName()));

        foreach ($this->getColumns() as $column) {
            $selectColumns[] = $column->getName();
        }

        return $selectColumns;
    }

    /**
     * Method to run the sanitation on the table.
     *
     * @return mixed
     */
    abstract function sanitize();
}
