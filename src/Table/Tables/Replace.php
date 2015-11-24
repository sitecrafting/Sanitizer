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
use Pegasus\Application\Sanitizer\Table\Exceptions\TableColumnTypeException;

class Replace extends AbstractTable
{
    /**
     * Returns the type identifier
     *
     * @return string
     */
    public static function getType()
    {
        return 'replace';
    }

    /**
     * Method to set the table data and have each class set up its own instance based on that data.
     *
     * @param  array $tableData
     * @return mixed
     * @throws TableException when column type could not be found.
     * @throws TableColumnTypeException if invalid type
     */
    function setTableData(array $tableData)
    {
        parent::setTableData($tableData);

        if (true == $this->doCommand()) {
            return true;
        }

        if (false == isset($tableData['rules'])) {
            $msg = "Update type needs a set of rules so it knows what to do for table {$this->getTableName()}!";
            throw new TableException($msg);
        }

        $rules = array();

        foreach ($tableData['rules'] as $rule) {
            $rule = new Object($rule);

            $this->_validateType($rule->getDataType());

            if (null == $rule->getDataType()) {
                $rule->setDataType('text');
            }

            $rule->setColumnTypeInstance($this->getInstanceFromType($rule->getDataType(), $rule->getData()));

            $rules[] = $rule;
            $this->getTerminalPrinter()->println($this->_getMessage($rule), 'notice');
        }

        $this->setRules($rules);
        $this->validateWhereClause();
        $this->checkAllRulesHaveColumnsWhichExist();

        return true;
    }

    /**
     * Throws exception if not valid.
     *
     * @param $type
     * @throws TableColumnTypeException
     */
    protected function _validateType($type)
    {
        $allowedDataTypes = array('text', 'varchar');

        if (false == in_array($type, $allowedDataTypes)) {

            $type = (null == $type) ? 'NULL' : $type;
            $allowedDataTypes = implode(',', $allowedDataTypes);
            $message = __CLASS__.": Data type must be ".$allowedDataTypes.' found '.$type;
            throw new TableColumnTypeException($message);
        }
    }

    /**
     * This function returns the formatted message
     *
     * @param $rule
     * @return string
     */
    private function _getMessage($rule) 
    {
        $message = "Another replace to column '";
        $message .= $rule->getColumn();
        $message .= "' in '";
        $message .= $this->getTableName();
        $message .= "' to replace '";
        $message .= $this->_replaceAsString($rule->getReplace());
        $message .= "'";

        if (null != $rule->getWith()) {
            $message .= " with '";
            $message .= implode(',', $rule->getWith());
            $message .= "'";
        }

        return $message;
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

            if (null == $column) {
                $msg = "Column instance not found in table {$this->getTableName()} for rule {$rule->getValue()}";
                throw new TableException($msg);
            }

            if (false == $column->exists()) {
                throw new TableException("Could not find column {$column->getName()} for {$this->getTableName()} ");
            }
        }
    }

    /**
     * This method replaces content with new content
     */
    public function sanitize()
    {
        $engine         = $this->getEngine();
        $printer        = $this->getTerminalPrinter();
        $rowsUpdated    = 0;
        $primaryKey     = $engine->getPrimaryKeyName($this->getTableName());

        foreach ($this->getRules() as $rule) {
            $this->_logIntro($rule, $printer);
            $contents = $engine->select($this->getTableName(), array($primaryKey, $rule->getColumn()));

            foreach ($contents as $content) {
                $newContent = null;

                if (true == array_key_exists($rule->getColumn(), $content)) {
                    $newContent = str_replace($rule->getReplace(), $rule->getWith(), $content[$rule->getColumn()]);
                    $rowsUpdated += $engine->update(
                        $this->getTableName(),
                        array($rule->getColumn() => $newContent),
                        array($primaryKey => $content[$primaryKey])
                    );
                }
            }

            $this->_logEnd($rule, $printer, $rowsUpdated);
        }

        return $rowsUpdated;
    }

    /**
     * If param is an array it's imploded.
     *
     * @param $replace
     * @return string
     */
    protected function _replaceAsString($replace)
    {
        return (true == is_array($replace)) ? implode(',', $replace) : $replace;
    }

    /**
     * Logs the rule intro message
     *
     * @param $rule
     * @param $printer
     */
    private function _logIntro($rule, $printer)
    {
        $replace = $this->_replaceAsString($rule->getReplace());
        $msg = "Replacing rows in {$this->getTableName()}' for column '";
        $msg .= "{$rule->getColumn()}' from '{$replace}' with '{$rule->getWith()}'";
        $printer->printLn($msg, 'notice');
    }

    /**
     * Logs the rule intro message
     *
     * @param $rule
     * @param $printer
     */
    private function _logEnd($rule, $printer, $rowsUpdated)
    {
        $msg = "Updated '$rowsUpdated' rows in {$this->getTableName()}' for ";
        $msg .= "column '{$rule->getColumn()}' to '{$rule->getWith()}'";
        $printer->printLn($msg, 'notice');
    }
}
