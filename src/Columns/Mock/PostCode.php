<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class PostCode extends AbstractMockData
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
            "EX33 2BP",
            "BH12 5BB",
            "WC1E 6BT",
            "SO17 1BJ",
            "BA2 7AY",
            "BS8 1TH",
            "G12 8QQ",
            "HU6 7RX",
        );
    }
}