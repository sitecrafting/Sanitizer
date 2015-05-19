<?php
namespace Pegasus\Columns\Types;
use Pegasus\Resource\Object;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
abstract class AbstractDataType extends Object
{
    /**
     * Return the default data for this type of column.
     *
     * @return mixed
     */
    abstract function getDefault();

    /**
     * This method returns an array of options
     *
     * @return array
     */
    public function option()
    {
        return array();
    }
}