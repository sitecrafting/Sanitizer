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
 *
 */
namespace Pegasus\Application\Sanitizer\Tables;

use Pegasus\Application\Sanitizer\Engine\Engine;
use Pegasus\Application\Sanitizer\Engine\FatalEngineException;
use Pegasus\Application\Sanitizer\Resource\SanitizerException;
use Pegasus\Application\Sanitizer\Resource\TerminalPrinter;
use Pegasus\Application\Sanitizer\Tables;

class Collection
{
    const KEY_TABLE_TYPE = 'type';

    private static $engine = null;

    private static $printer = null;
    
    public static function setEngine(Engine $engine)
    {
        if(null == $engine)
        {
            throw new TableException("Someone has passed this table collection a null engine");
        }
        self::$engine = $engine;
    }

    public static function setTerminalPrinter(TerminalPrinter $printer)
    {
        self::$printer = $printer;
    }

    private static function getTerminalPrinter()
    {
        return self::$printer;
    }

    public static function getCollection()
    {
        static $collection  = null;
        $possibleTables     = 0;
        $totalAddedTables   = 0;
        if(null == $collection)
        {
            $collection     = array();
            $tables         = self::getTerminalPrinter()->getConfig()->getTables();
            foreach ($tables as $tableName => $tableConfig)
            {
                $possibleTables++;
                try
                {
                    $collection[] = self::getTableInstance($tableName, $tableConfig);
                    self::getTerminalPrinter()->printLn("Added $tableName to sanitise list ", 'notice');
                    $totalAddedTables++;
                }
                catch(FatalEngineException $e)
                {
                    self::getTerminalPrinter()->printLn('Fatal: '.$e->getMessage(), 'fatal_error');
                    exit(-200);
                }
                catch(TableCommentException $e)
                {
                    self::getTerminalPrinter()->printLn($e->getMessage(), 'notice');
                }
                catch(SanitizerException $e)
                {
                     self::getTerminalPrinter()->printLn($e->getMessage(), 'warning');
                }
            }
        }
        self::getTerminalPrinter()->printLn("All Possible Tables = {$possibleTables}", 'notice');
        self::getTerminalPrinter()->printLn("Queued Tables = {$totalAddedTables}", 'notice');
        self::getTerminalPrinter()->printLn("Skipped Tables = ".($possibleTables - $totalAddedTables), 'notice');
        return $collection;
    }

    private static function getTableInstance($tableName, array $tableConfig)
    {
        /* we default the type to flat */
        $table = null;

        /* Type has NOT been set in the config */
        if(true == isset($tableConfig[self::KEY_TABLE_TYPE]))
        {
            $columnType = $tableConfig[self::KEY_TABLE_TYPE];
            switch($columnType)
            {
                case Eav::getType() :
                {
                    $table = new Eav(self::$engine);
                    break;
                }

                case Update::getType() :
                {
                    $table = new Update(self::$engine);
                    break;
                }
                /*
                 * Space for different types
                 */
                default : /* type not found */
                {
                    throw new InvalidColumnTypeException("Column type '{$columnType}' not valid for table {$tableName}");
                }
            }
        }
        if(null == $table)
        {
            $table = new Flat(self::$engine);
        }
        $table->setTerminalPrinter(self::getTerminalPrinter());
        $table->setTableName($tableName);
        $valid = $table->setTableData($tableConfig);
        return (true == $valid) ? $table : $valid;
    }

    /**
     * This method iterates over the tables.
     */
    public static function sanitizeTables()
    {
        if(null == self::$engine)
        {
            throw new TableException("Someone has moved the Engine, I can't find it!");
        }
        $sanitizer = self::getTerminalPrinter();
        if(null == $sanitizer)
        {
            throw new TableException("There seems to be a glitch with the Sanitizer instance matrix!, I just can't find it!");
        }
        $sanitizer->setSatitisationRunning();
        $sanitized = array();
        $tables = self::getCollection();
        if('sanitize' == $sanitizer->getMode())
        {
            $sanitizer->startProgressBar(sizeof($tables));
            foreach($tables as $table)
            {
                $rows = $table->sanitize();
                if(true == $table->doCommand())
                {
                    $sanitized[] = "{$table->getCommand()} applied to {$table->getTableName()} and effected {$rows} rows";
                }
                else
                {
                    $sanitized[] = "Sanitized {$table->getTableName()} and updated {$rows} rows";
                }
                $sanitizer->advanceProgressBar();
            }
            $sanitizer->setSatitisationNotRunning();
            $sanitizer->advanceProgressFinish();
            $sanitizer->printLn("\n");
            foreach($sanitized as $san)
            {
                $sanitizer->printLn($san, 'notice');
            }
        }
        else
        {
            $sanitizer->printLn($sanitizer->getMode().' mode selected, exiting before sanitisation', 'general');
        }
        $sanitizer->setSatitisationNotRunning();
        $sanitizer->printLn("Sanitizer finished!");
    }

}
