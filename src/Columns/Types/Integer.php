<?php
namespace Pegasus\Columns\Types;

use Pegasus\Columns\Types;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class Integer extends AbstractDataType
{
    /**
     * Return the default data for this type of column.
     *
     * @return mixed
     */
    function getDefault()
    {
        return "1000";
    }
}