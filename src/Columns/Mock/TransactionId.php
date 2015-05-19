<?php
namespace Pegasus\Columns\Mock;

use Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
class TransactionId extends AbstractMockData
{
    /**
     * Returns sanitised street names
     *
     * @return array
     */
    public function getValues()
    {
        $id = null;
        for($ii = 0; $ii < 10; $ii++)
        {
            $id = array();
            $tempId = rand(1000000, 9999999).'-txn';
            if(false == in_array($tempId, $id))
            {
                $id[] = $tempId;
            }
        }
        return $id;
    }
}