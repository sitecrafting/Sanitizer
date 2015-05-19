<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class Company extends AbstractMockData
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
            "Millie's Fabulous Muffins",
            "Sam's Web Design",
            "Bash Hacker LTD",
            "Apple's and Pears",
            "Debbie's Amazing Designs LTD",
            "DullStar",
            "Dark Solar Systems LTD"
        );
    }
}