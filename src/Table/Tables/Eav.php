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

use Pegasus\Application\Sanitizer\Columns\Mock\MockData;
use Pegasus\Application\Sanitizer\Resource\Object;
use Pegasus\Application\Sanitizer\Table;

class Eav extends AbstractTable
{
    public static function getType()
    {
        return 'eav';
    }

    /**
     * Method to set the table data and have each class set up its own instance based on that data.
     *
     * @param  array $tableData
     * @return mixed
     */
    function setTableData(array $tableData)
    {
        parent::setTableData($tableData);

        if (true == $this->doCommand()) {
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
        if (true == isset($tableData['data_type'])) {
            return $tableData['data_type'];
        }

        return 'text';
    }

    public function getColumnFromTableData($tableData)
    {
        if (false == isset($tableData['column'])) {
            throw new TableException('Column name not defined for EAV table '.$this->getTableName());
        }

        return $tableData['column'];
    }

    private function configureEavColumn($column, $tableData)
    {
        if (false == isset($tableData['control_column'])) {
            $msg = "Control column undefined for column '{$column->getName()}' on table '{$this->getTableName()}'";
            throw new TableException($msg);
        }

        $controlColumn = new Object($tableData['control_column']);
        $column->setControlColumn($controlColumn);
    }

    public function sanitize()
    {
        $rows = 0;
        $rowsEffected = $this->hasExecutedCommand();

        if (false !== $rowsEffected) {
            return $rowsEffected;
        }

        foreach ($this->getColumns() as $column) {
            $controlColumn = $column->getControlColumn();
            $msg = "Sanitizing Eav '{$this->getTableName()}' subset of ";
            $msg .= "column '{$column->getColumn()}' with '{$controlColumn->getName()}'";
            $this->getTerminalPrinter()->printLn($msg, 'notice');

            foreach ($controlColumn->getValues() as $subsetIndex => $source) {
                $source = new MockData($source);

                if (null != $source->getMockModel()) {
                    $modelName      = $source->getMockModel();

                    if (false == class_exists($modelName)) {
                        $msg = "Unable to find Mock Model with the name '{$modelName}' in ";
                        $msg .= "table '{$this->getTableName()}' with row id '{$subsetIndex}' ";
                        throw new TableException($msg);
                    }

                    $model          = new $modelName();
                    $rows += $this->sanitizeSubset(
                        $this->getTableName(),
                        $controlColumn->getName(),
                        $subsetIndex,
                        $column->getColumn(),
                        $model
                    );
                } else {
                    $msg = "No Mock Model configuration found for EAV column '{$controlColumn->getName()}' ";
                    $msg .= "with row id '{$subsetIndex}' in table '{$this->getTableName()}'";
                    $this->getTerminalPrinter()->printLn($msg, 'notice');
                }

                if (null != $source->getComment()) {
                    $msg = "Comment[{$this->getTableName()}][{$controlColumn->getName()}]: {$source->getComment()}";
                    $this->getTerminalPrinter()->printLn($msg, 'general');
                }
            }
            $msg = "Sanitized Eav '{$this->getTableName()}' subset of column '{$column->getColumn()}' with ";
            $msg .= "'{$controlColumn->getName()}' equal to '{$subsetIndex}' ";
            $this->getTerminalPrinter()->printLn($msg, 'notice');

        }
        return $rows;
    }

    private function sanitizeSubset($tableName, $controlColumnName, $subsetIndex, $columnName, $mockModel)
    {
        if (true == $this->getIsQuickSanitisation()) {
            return $this->getEngine()->update(
                $tableName,
                array($columnName => $mockModel->getRandomValue()),
                array($controlColumnName => $subsetIndex)
            );
        } else {
            $rowsUpdated = 0;
            $rows = $this->getEngine()->select(
                $tableName,
                $this->getSelectColumns(),
                array($controlColumnName => $subsetIndex)
            );

            foreach ($rows as $row) {
                $newData = array();
                $newData[$columnName] = $mockModel->getRandomValue();
                $rowsUpdated += $this->getEngine()->update($tableName, $newData, $this->getPrimaryKeyData($row));
            }

            return $rowsUpdated;
        }
    }
}
