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
 * Time: 20:43
 */
namespace Pegasus\Configuration;

use Pegasus\Resource\Object;
use Pegasus\Configuration;

class Config extends Object
{
    private $database = null;

    public function __construct($configFile)
    {
        if(false == file_exists($configFile))
        {
            throw new ConfigException("Sadly I couldn't validate the config file {$configFile} because it doesn't exist!");
        }
        $parsed = json_decode(file_get_contents($configFile), true);
        $this->setData($parsed);

    }

    public function setDatabaseOverride($data)
    {
        if(false == isset($this->data['database']))
        {
            $this->data['database'] = array();
        }

        foreach($data as $configOverride)
        {
            if(2 == sizeof($configOverride))
            {
                if(null != $configOverride[0] && null != $configOverride[1])
                {
                    $this->data['database'][strtolower($configOverride[0])] = $configOverride[1];
                }
            }
        }

        /* set it back to null so the next call re-initialises the database config object */
        $this->database = null;
    }

    /**
     * Returns true if this instance is in developer mode.
     * @return bool
     */
    public function getIsInDeveloperMode()
    {
        if(false == isset($this->data['developer_mode']))
        {
            return false;
        }
        if('yes' == $this->data['developer_mode'])
        {
            return true;
        }
        return false;
    }

    /**
     * Returns the database config as an object.
     *
     * @return Object
     */
    public function getDatabase()
    {
        if(null == $this->database)
        {
            $this->database = new Object($this->data['database']);
            $this->database->setDatabaseName($this->database->getDatabase());
        }
        return $this->database;
    }

    /**
     * Returns table data
     *
     * @return Object
     */
    public function getTables()
    {
        return $this->data['tables'];
    }
}