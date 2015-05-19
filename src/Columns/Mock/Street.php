<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class Street extends AbstractMockData
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
            "1\nAbbey Road",
            "10\nBlake Road",
            "6\nBleeker Street",
            "500\nMagento Road",
            "25\nWest End Road",
            "129\nLondon Road",
            "1\nBrightstar Road"
        );
    }
}