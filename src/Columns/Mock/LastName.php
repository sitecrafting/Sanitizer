<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class LastName extends AbstractMockData
{
    /**
     * Returns sanitised street names
     *
     * @return array
     */
    public function getValues()
    {
        return array
        (
            "Smith",
            "Elson",
            "Butcher",
            "Paris",
            "Oliver",
            "Williams",
            "Carpenter",
            "Harris"
        );
    }
}