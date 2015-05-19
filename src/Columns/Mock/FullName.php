<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class FullName extends AbstractMockData
{
    /**
     * Returns sanitised street names
     *
     * @return array
     */
    public function getValues()
    {
        static $fullName = null;
        if(null == $fullName)
        {
            $firstNames     = new FirstName();
            $secondNames    = new LastName();
            $fullName = array();
            for($ii = 0; $ii < 15; $ii++)
            {
                $name = $firstNames->getRandomValue().' '.$secondNames->getRandomValue();
                if(false == in_array($name, $fullName))
                {
                    $fullName[] = $name;
                }
            }
        }
        return $fullName;
    }
}