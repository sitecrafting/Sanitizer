<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class MobileNumber extends AbstractMockData
{
    /**
     * Returns sanitised street names
     *
     * @return array
     */
    public function getValues()
    {
        static $numbers = null;
        if(null === $numbers)
        {
            $numbers = array();
            for($ii = 0; $ii < 10; $ii++)
            {
                $numbers[] = '0771234561'.$ii;
            }
        }
        return $numbers;
    }
}