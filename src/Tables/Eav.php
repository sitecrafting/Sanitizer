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

use Pegasus\Resource\Object;
use Pegasus\Resource\SanitizerException;
use Pegasus\Tables;

class Eav extends AbstractTable
{
    public static function getType()
    {
        return 'eav';
    }

    /**
     * Method to set the table data and have each class set up its own instance based on that data.
     *
     * @param array $tableData
     * @return mixed
     */
    function setTableData(array $tableData)
    {
        parent::setTableData($tableData);
        if(true == $this->doCommand())
        {
            return true;
        }
        $this->setColumn($this->getColumnFromTableData($tableData));
        $this->setDataType($this->getDataTypeFromTableData($tableData));
        $column = $this->getInstanceFromType($this->getDataType(), $tableData);
        $this->configureEavColumn($column, $tableData);
        $this->addColumn($column);

        return true;
    }

    public function getDataTypeFromTableData($tableData)
    {
        if(true == isset($tableData['data_type']))
        {
            return $tableData['data_type'];
        }
        return 'text';
    }

    public function getColumnFromTableData($tableData)
    {
        if(false == isset($tableData['column']))
        {
            throw new TableException('Column name not defined for EAV table '.$this->getTableName());
        }
        return $tableData['column'];
    }

    private function configureEavColumn($column, $tableData)
    {
        //(print_r($tableData));
        if(false == isset($tableData['control_column']))
        {
            throw new TableException("Control column undefined for column '{$column->getName()}' on table '{$this->getTableName()}'");
        }
        $controlColumn = new Object($tableData['control_column']);
        //die($controlColumn->getName());
        //die(print_r($controlColumn->getValues()));
        $column->setControlColumn($controlColumn);
    }

    public function sanitize()
    {
//        //throw new SanitizerException('EAV sanitize function needs implementing');
//        return true;
//
//        $rowsEffected = $this->executedCommand();
//        if(false !== $rowsEffected)
//        {
//            return $rowsEffected;
//        }
    }
}
