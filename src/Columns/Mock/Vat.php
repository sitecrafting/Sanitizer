<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class Vat extends AbstractMockData
{
    /**
     * Returns sanitised street names
     *
     * @return array
     */
    public function getValues()
    {
        static $vat = null;
        if(null == $vat)
        {
            $vat = array();
            $start = 'GB2054';
            $finish = '464';
            for($ii = 10; $ii < 50; $ii++)
            {
                $vat[] = $start.$ii.$finish;
            }
        }
        return $vat;
    }
}