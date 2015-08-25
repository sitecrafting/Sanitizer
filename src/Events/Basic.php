<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015  Philip Elson <phil@pegasus-commerce.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Date: 13/08/15
 * Time: 14:23
 *
 * PHP version 5.3+
 *
 * @category Pegasus_Tools
 * @package  Pegasus_sanitizer
 * @author   Philip Elson <phil@pegasus-commerce.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     http://pegasus-commerce.com
 */
namespace Pegasus\Application\Sanitizer\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * This basic event class contains get and add methods to an internal array.
 *
 * Class Basic
 */
class Basic extends Event
{
    protected $values = array();

    /**
     * This method allows for initialising of the data.
     *
     * @param array|null $data
     */
    public function __construct(array $data=null) {
        if (null != $data) {
            $this->values = $data;
        }
    }

    /**
     * This method returns all the values (array)
     *
     * @return array|null
     */
    public function getValues() {
        return $this->values;
    }

    /**
     * This method sets a value identified by the key
     *
     * @param $key      Is the key used to set and extract the value
     * @param $value    Is the value to be stored
     * @return bool     If the key already exists
     * @throws \Exception If the key already exists
     */
    public function addValue($key, $value)
    {
        if (true == array_key_exists($key)) {
            throw new \Exception("key '{$key}' already exists");
        }
        $this->values[$key] = $value;
        return true;
    }

    /**
     * This method returns the value identified by the key
     *
     * @param $key      Used to identify the value
     * @return mixed    The value
     * @throws \Exception If the value could not be found
     */
    public function getValue($key)
    {
        if (true == array_key_exists($key)) {
            return $this->values[$key];
        }
        throw new \Exception("Values with the key '{$key}' not found");
    }
}