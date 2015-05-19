<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class Telephone extends AbstractMockData
{
    /**
     * Returns sanitised street names
     *
     * @return array
     */
    public function getValues()
    {
        static $numbers = null;
        if(null == $numbers)
        {
            $numbers = array();
            for($ii = 10; $ii < 20; $ii++)
            {
                $numbers[] = "01234 6789".$ii;
            }
        }
       return $numbers;
    }
}