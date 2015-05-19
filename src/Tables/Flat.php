<?php
namespace Pegasus\Tables;

use Pegasus\Tables\AbstractTable;
use Pegasus\Engine\Engine;
use Pegasus\Columns\Types;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 18/05/15
 * Time: 12:42
 */

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
     */
    function setTableData(array $tableData)
    {
        try
        {
            if (false == parent::setTableData($tableData))
            {
                return false;
            }
        }
        catch(TableCommandFoundException $commandFoundException)
        {
            return false;
        }

        if(true == $this->skip($tableData))
        {
            return false;
        }

        foreach($tableData as $columnData)
        {
            $column = null;
            if(false == isset($columnData[self::FIELD_COLUMN]))
            {
                throw new TableException("No column name could be found by on table '{$this->getTableName()}' for data ".implode(',', $columnData));
            }
            if(true == isset($columnData[self::FIELD_DATA_TYPE]))
            {
                $configDataType = $columnData[self::FIELD_DATA_TYPE];
                switch($configDataType)
                {
                    case 'timestamp' :
                    {
                        $column = new Types\Timestamp($columnData);
                        break;
                    }
                    case 'text' :
                    {
                        $column = new Types\Text($columnData);
                        break;
                    }
                    case 'varchar' :
                    {
                        $column = new Types\Varchar($columnData);
                        break;
                    }
                    case 'integer' :
                    {
                        $column = new Types\Integer($columnData);
                        break;
                    }
                    default :
                    {
                        throw new TableException("No column types could be found by '{$configDataType}' on table '{$this->getTableName()}");
                    }
                }
            }
            if(null != $column)
            {
                $this->addColumn($column);
            }
            $column = null;

        }
        return true;
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
            Collection::getSanitizer()->printLn("No columns to manipulate could be found for table '{$this->getTableName()}', skipping", 'general');
            return true;
        }
        return false;
    }
}
