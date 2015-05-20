<?php
/**
 *
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
namespace Pegasus\Tables;

use Pegasus\Columns\Types\AbstractDataType;
use Pegasus\Resource\Object;
use Pegasus\Tables;

abstract class AbstractTable extends Object
{
    protected $truncate = false;

    protected $doCommand = false;

    public function addColumn(AbstractDataType $column)
    {
        if(null == $column)
        {
            return false;
        }
        if(false == isset($this->data['columns']))
        {
            $this->data['columns'] = array();
        }
        if(false == in_array($column, $this->data['columns']))
        {
            $this->data['columns'][] = $column;
            return true;
        }
        return false;
    }

    public function removeColumn(AbstractDataType $column)
    {
        if(false == isset($this->data['columns']))
        {
           return; //it's not in something that doesn't exist!.
        }
        foreach($this->data['columns'] as $key => $value)
        {
            if($value == $column)
            {
                unset($this->data['columns'][$key]);
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
        if(false == isset($this->data['columns']))
        {
            return array();
        }
        return $this->data['columns'];
    }

    public static function getType()
    {
        throw new \Exception('Method to be re-written by children');
    }

    /**
     * Method returns true if the command is valid.
     *
     * @param $command
     * @return bool
     */
    public function isCommandValid($command)
    {
        $this->truncate       = false;
        $this->doCommand      = false;
        switch($command)
        {
            case 'truncate' :
            {
                $this->truncate     = true;
                $this->doCommand    = true;
                return true;
            }
        }
        return false;
    }

    /**
     * Method to set the table data and have each class set up its own instance based on that data.
     * This is also where each class should validate that the data within it is correct.
     *
     * @param   array $tableData
     * @return  mixed
     * @throws  TableException for various reasons!...
     * @throws  TableCommandFoundException when a command has been found rendering further analysis void.
     * @throws  TableCommentException when the table definition only contains a comment.
     */
    public function setTableData(array $tableData)
    {
        unset($tableData['type']);  /* We have already loaded this via type */
        if(null == $this->getTableName())
        {
            throw new TableException('Table name must be set for data manipulation');
        }
        //Only data is a comment for this row
        if(1 == sizeof($tableData) && true == isset($tableData['comment']))
        {
            Collection::getSanitizer()->printLn("Comment[{$this->getTableName()}]: ".$tableData['comment'], 'general');
            throw new TableCommentException("This table '{$this->getTableName()}' only has a comment in the config, skipping");
        }
        //Command is the most important option, it will override all others.
        if(true == isset($tableData['command']))
        {
            $command = $tableData['command'];
            if(false == $this->isCommandValid($command))
            {
                throw new TableException("Command '{$command}' is set but not valid for table ".$this->getTableName());
            }
        }
        if(false == $this->exists())
        {
            $db = 
            throw new TableException("Table '{$this->getTableName()}' not found in database '{$db}'");
        }
        return true;
    }

    /**
     * Returns true if the operation is to do a truncate
     *
     * @return bool
     */
    public function doTruncate()
    {
        return $this->truncate;
    }

    /**
     * Returns true if this table is to execute a command rather than process data
     *
     * @return bool
     */
    public function doCommand()
    {
        return $this->doCommand;
    }

    public function exists()
    {
        return false;
    }
}
