<?php
namespace Pegasus\Columns\Mock;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 19/05/15
 * Time: 11:37
 */
abstract class AbstractMockData
{
    abstract function getValues();

    /**
     * For quick sanitisation, this returns true if all the fields can be updated to the same value.
     *
     * @return bool
     */
    public function supportsMassUpdate()
    {
        return true;
    }

    /**
     * All values must be unique
     *
     * @return bool
     */
    public function valuesMustBeUnique()
    {
        return false;
    }

    /**
     * Returns a shuffled array.
     *
     * @return mixed
     */
    public function getRandom()
    {
        $values = $this->getValues();
        shuffle($values);
        return $values;
    }

    /**
     * Returns a single random value from this class
     *
     * @return mixed
     */
    public function getRandomValue()
    {
        $data = $this->getRandom();
        return $data[rand(0, sizeof($data)-1)];
    }

}