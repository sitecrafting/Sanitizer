<?php
/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Philip Elson <phil@pegasus-commerce.com>
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
 * Date: 18/05/15
 * Time: 20:51
 */
namespace Pegasus\Resource;

class Object
{
    protected $data = array();

    protected static $underScoreCache = array();

    protected $_syncFieldsMap = array();

    public function __construct()
    {
        $arguments = func_get_args();
        if(true == empty($arguments[0]))
        {
            $arguments[0] = array();
        }
        $this->data = $arguments[0];
    }

    public function addData(array $arr)
    {
        foreach($arr as $index=>$value)
        {
            $this->setData($index, $value);
        }
        return $this;
    }

    public function setData($key, $value=null)
    {
        if(true == is_array($key))
        {
            $this->data = $key;
            $this->addFullNames();
        }
        else
        {
            $this->data[$key] = $value;
            if (true == isset($this->_syncFieldsMap[$key]))
            {
                $this->data[$this->_syncFieldsMap[$key]] = $value;
            }
        }
        return $this;
    }

    public function getData($key='', $index=null)
    {
        if (''===$key)
        {
            return $this->data;
        }
        $default = null;
        if (true == isset($this->data[$key]))
        {
            if (true == is_null($index))
            {
                return $this->data[$key];
            }

            $value = $this->data[$key];
            if (true == is_array($value))
            {
                if (true == isset($value[$index]))
                {
                    return $value[$index];
                }
                return null;
            }
            elseif (true == is_string($value))
            {
                $arr = explode("\n", $value);
                if((isset($arr[$index]) && (!empty($arr[$index]) || strlen($arr[$index]) > 0)))
                {
                    return $arr[$index];
                }
                return null;
            }
            elseif ($value instanceof Varien_Object)
            {
                return $value->getData($index);
            }
            return $default;
        }
        return $default;
    }

    public function hasData($key='')
    {
        if (true == empty($key) || false == is_string($key))
        {
            return !empty($this->data);
        }
        return array_key_exists($key, $this->data);
    }

    public function __call($method, $args)
    {
        switch (substr($method, 0, 3))
        {
            case 'get' :
            {
                $key        = $this->getUnderScoredValue(substr($method, 3));
                return      $this->getData($key, isset($args[0]) ? $args[0] : null);
            }
            case 'set' :
            {
                $key        = $this->getUnderScoredValue(substr($method, 3));
                return $this->setData($key, isset($args[0]) ? $args[0] : null);
            }
        }
        throw new SanitizerException("Method not found ".get_class($this)."::".$method."(".print_r($args, true).")");
    }

    public function __set($var, $value)
    {
        $var = $this->getUnderScoredValue($var);
        $this->setData($var, $value);
    }

    protected function getUnderScoredValue($name)
    {
        if(true == isset(self::$underScoreCache[$name]))
        {
            return self::$underScoreCache[$name];
        }
        $result = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
        self::$underScoreCache[$name] = $result;
        return $result;
    }

    protected function addFullNames()
    {
        $existedShortKeys = array_intersect($this->_syncFieldsMap, array_keys($this->_data));
        if (!empty($existedShortKeys)) {
            foreach ($existedShortKeys as $key) {
                $fullFieldName = array_search($key, $this->_syncFieldsMap);
                $this->_data[$fullFieldName] = $this->_data[$key];
            }
        }
    }
}