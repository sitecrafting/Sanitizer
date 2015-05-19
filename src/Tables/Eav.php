<?php

namespace Pegasus\Tables;
/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 18/05/15
 * Time: 12:42
 */

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
        // TODO: Implement setTableData() method.
        throw new \Exception('Not yet implemented');
    }
}
