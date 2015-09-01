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

use Pegasus\Application\Sanitizer\Resource\Object;
use Pegasus\Application\Sanitizer\Resource\SanitizerException;
use Pegasus\Application\Sanitizer\Columns\Types;

class Update extends AbstractTable
{
    const FIELD_DATA_TYPE = 'data_type';

    const FIELD_COLUMN = 'column';

    public static function getType()
    {
        return 'update';
    }

    /**
     * Method to set the table data and have each class set up its own instance based on that data.
     *
     * @param  array $tableData
     * @return mixed
     * @throws TableException when column type could not be found.
     */
    function setTableData(array $tableData)
    {
        parent::setTableData($tableData);
        if (true == $this->doCommand()) {
            return true;
        }
        if (false == isset($tableData['rules'])) {
            throw new TableException("Update type needs a set of rules so it knows what to do for table {$this->getTableName()}!");
        }
        $rules = array();
        foreach ($tableData['rules'] as $rule) {
            $rule = new Object($rule);
            if(null == $rule->getDataType()) {
                $rule->setDataType('text');
            }
            $rule->setColumnTypeInstance($this->getInstanceFromType($rule->getDataType(), $rule->getData()));
            $rules[] = $rule;
            $this->getTerminalPrinter()->println("Another update to column '{$rule->getColumn()}' in {$this->getTableName()} marked for update to '{$rule->getTo()}' where '{$rule->getWhere()}'", 'notice');
        }
        $this->setRules($rules);
        $this->validateWhereClause();
        $this->checkAllRulesHaveColumnsWhichExist();
        return true;
    }

    /**
     * This method throws a SanitizerException is the each rule does not have a
     * WHERE clause.
     *
     * @throws SanitizerException
     */
    private function validateWhereClause()
    {
        foreach ($this->getRules() as $rule) {
            if (false == is_array($rule->getWhere()) && null != $rule->getWhere()) {
                $rule->setWhere(array($rule->getWhere()));
            }
        }
    }

    /**
     * This method throws TableException if the a rules column does
     * not exist.
     *
     * @throws TableException
     */
    private function checkAllRulesHaveColumnsWhichExist()
    {
        foreach ($this->getRules() as $rule) {
            $column = $rule->getColumnTypeInstance();
            if(null == $column) {
                throw new TableException("Column instance not found in table {$this->getTableName()} for rule {$rule->getValue()}");
            }
            if(false == $column->exists()) {
                throw new TableException("Column not find column {$column->getName()} for {$this->getTableName()} ");
            }
        }
    }

    /**
     * This implementation does an update rather than a sanitise. This allows the user to
     * update tables quickly and repeatable when rolling down from one environment to another.
     */
    public function sanitize()
    {
        $engine     = $this->getEngine();
        $printer    = $this->getTerminalPrinter();
        $rows       = 0;
        foreach($this->getRules() as $rule)
        {
            $printer->printLn("Updating rows in {$this->getTableName()}' for column '{$rule->getColumn()}' to '{$rule->getTo()}'", 'notice');
            $dataToChange = array($rule->getColumnTypeInstance()->getName() => $rule->getTo());
            $rowsUpdated  = $engine->update($this->getTableName(), $dataToChange, $rule->getWhere());
            $printer->printLn("Updated '$rowsUpdated' rows in {$this->getTableName()}' for column '{$rule->getColumn()}' to '{$rule->getTo()}'", 'notice');
            $rows += $rowsUpdated;
        }
        return $rows;
    }
}
