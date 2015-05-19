<?php
namespace Pegasus\Tables;

use Pegasus\Sanitizer;
use Pegasus\Tables;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 18/05/15
 * Time: 12:42
 */

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
                $table = self::getTableInstance($tableName, $tableConfig);
                if(false != $table)
                {
                    $collection[] = $table;
                }
            }
        }
        return $collection;
    }

    private static function getTableInstance($tableName, array $tableConfig)
    {
        /* we default the type to flat */
        $table = new Flat();

        //print_r($tableConfig);

        //Type has NOT been set in the config
        if(true == isset($tableConfig[self::KEY_TABLE_TYPE]))
        {
            self::getSanitizer()->printLn($tableConfig[self::KEY_TABLE_TYPE]);
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
