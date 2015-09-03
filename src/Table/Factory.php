<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015  Philip Elson <phil@pegasus-commerce.com>
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
 * Date: 01/09/15
 * Time: 19:43
 *
 * PHP version 5.3+
 *
 * @category Pegasus_Tools
 * @package  Pegasus_Sanitizer
 * @author   Philip Elson <phil@pegasus-commerce.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     http://pegasus-commerce.com
 */
namespace Pegasus\Application\Sanitizer\Table;

use Pegasus\Application\Sanitizer\Engine\EngineInterface;
use Pegasus\Application\Sanitizer\IO\TerminalPrinter;
use Pegasus\Application\Sanitizer\Table\Exceptions\TableColumnTypeException;
use Pegasus\Application\Sanitizer\Table\Tables\AbstractTable;
use Pegasus\Application\Sanitizer\Table\Tables\Eav;
use Pegasus\Application\Sanitizer\Table\Tables\Flat;
use Pegasus\Application\Sanitizer\Table\Tables\Update;

class Factory
{
    public function getInstance($tableName, array $tableConfig, TerminalPrinter $printer, EngineInterface $engine) 
    {
        /* we default the type to flat */
        $table = null;
        /* Type has NOT been set in the config */
        if(true == isset($tableConfig[AbstractTable::KEY_TABLE_TYPE])) {
            $columnType = $tableConfig[AbstractTable::KEY_TABLE_TYPE];
            switch($columnType) {
            case Eav::getType() : {
                $table = new Eav($engine);
                    break;
                }

            case Update::getType() : {
                $table = new Update($engine);
                    break;
                }

                /*
                 * Space for different types
                 */
            default : { /* type not found */
                    throw new TableColumnTypeException("Column type '{$columnType}' not valid for table {$tableName}");
                }
            }
        }
        if (null == $table) {
            $table = new Flat($engine);
        }
        $table->setTerminalPrinter($printer);
        $table->setTableName($tableName);
        $valid = $table->setTableData($tableConfig);
        if(true == $valid) {
            return $table;
        }
        return $valid;
    }
}