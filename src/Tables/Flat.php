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

use Pegasus\Sanitizer;
use Pegasus\Engine\Engine;
use Pegasus\Columns\Types;

class Flat extends AbstractTable
{
    const FIELD_DATA_TYPE = 'data_type';

    const FIELD_COLUMN = 'column';

    public static function getType()
    {
        return 'flat';
    }

    /**
     * Method to set the table data and have each class set up its own instance based on that data.
     *
     * @param array $tableData
     * @return mixed
     * @throws TableException when column type could not be found.
     */
    function setTableData(array $tableData)
    {
        parent::setTableData($tableData);
        if(true == $this->doCommand())
        {
            return true;
        }

        if(true == $this->skip($tableData))
        {
            return false;
        }
        $this->loadColumnInstances($tableData);
        foreach($this->getColumns() as $column)
        {
            if(false == $column->exists())
            {
                $db = $this->getTerminalPrinter()->getConfig()->getDatabase()->getDatabaseName();
                throw new TableException("Column '{$column->getName()}' in table '{$this->getTableName()}' not found in database '{$db}'");
            }
        }
        return true;
    }

    private function loadColumnInstances($tableData)
    {
        foreach($tableData as $columnData)
        {
            if(false == isset($columnData[self::FIELD_COLUMN]))
            {
                $data = $columnData;
                if(true == is_array($columnData))
                {
                    $data = implode(',', $columnData);
                }
                throw new TableException("No column name could be found by on table '{$this->getTableName()}' for data ".$data);
            }
            if(true == isset($columnData[self::FIELD_DATA_TYPE]))
            {
                $configDataType = $columnData[self::FIELD_DATA_TYPE];
                $this->addColumn($this->getInstanceFromType($configDataType, $columnData));
            }
        }
    }

    /**
     * Returns true to skip.
     *
     * @param $tableData
     * @return bool
     */
    private function skip($tableData)
    {
        if (0 == sizeof($tableData)) /* Flat tables are simple, if the array has now data then we are screwed. */
        {
            $this->getTerminalPrinter()->printLn("No columns to manipulate could be found for table '{$this->getTableName()}', skipping", 'general');
            return true;
        }
        return false;
    }

    /**
     * Sanitizes in 1 of 2 modes, quick and everything else.
     * Quick changes every value with the table to a random selection - but they will all be the same.
     * otherwise each column has data set individually set.
     */
    public function sanitize()
    {
        $rowsEffected = $this->hasExecutedCommand();
        if(false !== $rowsEffected)
        {
            return $rowsEffected;
        }
        $quick = ('quick' == $this->getTerminalPrinter()->getConfig()->getDatabase()->getSanitizationMode());
        $columns = $this->getColumnsForEngineQuery();
        if(true == $quick)
        {
            $rows = $this->engine->update($this->getTableName(), $columns);
            $this->getTerminalPrinter()->printLn("Sanitized Flat {$this->getTableName()}", 'notice');
            return $rows;
        }
        else
        {
            $rowsUpdated = 0;
            $rows = $this->engine->select($this->getTableName(), $this->getSelectColumns());
            foreach($rows as $row)
            {
                $rowSubset = $this->getColumnsForEngineQuery();
                $rowsUpdated += $this->engine->update($this->getTableName(), $rowSubset, $this->getPrimaryKeyData($row));
            }
            $this->getTerminalPrinter()->printLn("Sanitized Flat {$this->getTableName()} ", 'notice');
            return $rowsUpdated;
        }
        return 0;
    }

    private function getColumnsForEngineQuery()
    {
        $columns = array();
        foreach ($this->getColumns() as $column)
        {
            $columns[$column->getName()] = $column->getDefault();
        }
        return $columns;
    }
}
