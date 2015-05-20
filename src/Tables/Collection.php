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
namespace Pegasus\Tables;

use Behat\Gherkin\Exception\Exception;
use Pegasus\Resource\SanitizerException;
use Pegasus\Sanitizer;
use Pegasus\Tables;

class Collection
{
    const KEY_TABLE_TYPE = 'type';

    /**
     * @var $sanitizer Sanitizer
     */
    private static $sanitizer = null;

    public static function setSanitizer(Sanitizer $sanitizer)
    {
        self::$sanitizer = $sanitizer;
    }

    public static function getSanitizer()
    {
        return self::$sanitizer;
    }

    public static function getCollection()
    {
        static $collection = null;
        if(null == $collection)
        {
            $collection     = array();
            $tables         = self::$sanitizer->getConfig()->getTables();
            foreach ($tables as $tableName => $tableConfig)
            {
                try
                {
                    $collection[] = self::getTableInstance($tableName, $tableConfig);
                }
                catch(TableCommentException $e)
                {
                    self::getSanitizer()->printLn($e->getMessage(), 'notice');
                }
                catch(SanitizerException $e)
                {
                     self::getSanitizer()->printLn($e->getMessage(), 'warning');
                }
            }
        }
        return $collection;
    }

    private static function getTableInstance($tableName, array $tableConfig)
    {
        /* we default the type to flat */
        $table = new Flat();

        /* Type has NOT been set in the config */
        if(true == isset($tableConfig[self::KEY_TABLE_TYPE]))
        {
            $columnType = $tableConfig[self::KEY_TABLE_TYPE];
            switch($columnType)
            {
                case Eav::getType() :
                {
                    $table = new Eav() ;
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
        $table->setTableName($tableName);
        $valid = $table->setTableData($tableConfig);
        return (true == $valid) ? $table : $valid;
    }

}
