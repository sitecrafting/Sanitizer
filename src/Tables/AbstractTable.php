<?php
namespace Pegasus\Tables;

use Pegasus\Columns\Types\AbstractDataType;
use Pegasus\Resource\Object;
use Pegasus\Tables;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 18/05/15
 * Time: 12:42
 */

abstract class AbstractTable extends Object
{
    public function addColumn(AbstractDataType $column)
    {
        if(false == isset($this->_data['columns']))
        {
            $this->_data['columns'] = array();
        }
        if(false == in_array($column, $this->_data['columns']))
        {
            $this->_data['columns'][] = $column;
            return true;
        }
        return false;
    }

    public function removeColumn(AbstractDataType $column)
    {
        if(false == isset($this->_data['columns']))
        {
           return; //it's not in something that doesn't exist!.
        }
        foreach($this->_data['columns'] as $key => $value)
        {
            if($value == $column)
            {
                unset($this->_data['columns'][$key]);
            }
        }
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
        $commands = array('truncate');
        return (true == in_array($command, $commands));
    }

    /**
     * Method to set the table data and have each class set up its own instance based on that data.
     * This is also where each class should validate that the data within it is correct.
     *
     * @param   array $tableData
     * @return  mixed
     * @throws  TableException for various reasons!...
     * @throws TableCommandFoundException when a command has been found rendering further analysis void.
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
            return false;
        }
        //Command is the most important option, it will override all others.
        if(true == isset($tableData['command']))
        {
            $command = $tableData['command'];
            if(false == $this->isCommandValid($command))
            {
                throw new TableException("Command '{$command}' is set but not valid for table ".$this->getTableName());
            }
            else throw new TableCommandFoundException("Command found, no other data needed '{{$command}}'");
        }
        return true;
    }
}
