<?php
/**
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
namespace Pegasus\Application\Sanitizer\Configuration;

use Pegasus\Application\Sanitizer\Resource\Object;
use Pegasus\Application\Sanitizer\Configuration;

class Config extends Object
{
    const INPUT_PASSWORD_DEFAULT        = '';

    const INPUT_CONFIGURATION_FILE      ='sanitize.json';

    const INPUT_ENGINE                  = 'mysql';

    const INPUT_HOST                    = 'localhost';

    const INPUT_USER                    = 'root';

    const INPUT_DATABASE                = 'sanitizer';

    const INPUT_MODE                    = 'full';

    /**
     * Full sanitisation mode
     */
    const SANITIZATION_MODE_FULL        = 'full';

    /**
     * Quick sanitisation mode
     */
    const SANITIZATION_MODE_QUICK        = 'quick';

    private $database = null;

    public function __construct($configFile)
    {
        if(false == file_exists($configFile)) {
            throw new ConfigException("Sadly I couldn't validate the config file {$configFile} because it doesn't exist!");
        }
        $parsed = json_decode(file_get_contents($configFile), true);
        $this->setData($parsed);
    }

    /**
     * This method overrides the default values from command line options if the options are different to the default.
     *
     * @param $data
     */
    public function setDatabaseOverride($data)
    {
        if(false == isset($this->data['database'])) {
            $this->data['database'] = array();
        }

        $this->databaseConfigInitialise();

        foreach($data as $configOverride)
        {
            if(2 == sizeof($configOverride)) {
                if(null != $configOverride[0] && null != $configOverride[1]) {
                    $key = strtolower($configOverride[0]);
                    switch($key)
                    {
                    case 'password' :
                        {
                        //If the input has been changed by the user then we should use it
                        if($configOverride[1] != self::INPUT_PASSWORD_DEFAULT) {
                            $this->data['database'][$key] = $configOverride[1];
                        }
                            break;
                        }
                    case 'engine' :
                        {
                        //If the input has been changed by the user then we should use it
                        if($configOverride[1] != self::INPUT_ENGINE) {
                            $this->data['database'][$key] = $configOverride[1];
                        }
                            break;
                        }
                    case 'config' :
                        {
                        //If the input has been changed by the user then we should use it
                        if($configOverride[1] != self::INPUT_CONFIGURATION_FILE) {
                            $this->data['database'][$key] = $configOverride[1];
                        }
                            break;
                        }
                    case 'username' :
                        {
                        //If the input has been changed by the user then we should use it
                        if($configOverride[1] != self::INPUT_USER) {
                            $this->data['database'][$key] = $configOverride[1];
                        }
                            break;
                        }
                    case 'host' :
                        {
                        //If the input has been changed by the user then we should use it
                        if($configOverride[1] != self::INPUT_HOST) {
                            $this->data['database'][$key] = $configOverride[1];
                        }
                            break;
                        }
                    case 'database' :
                        {
                        //If the input has been changed by the user then we should use it
                        if($configOverride[1] != self::INPUT_DATABASE) {
                            $this->data['database'][$key] = $configOverride[1];
                        }
                            break;
                        }
                    case 'mode' :
                        {
                        //If the input has been changed by the user then we should use it
                        if($configOverride[1] != self::INPUT_DATABASE) {
                            $this->data['database']['sanitization_mode'] = $configOverride[1];
                        }
                            break;
                        }
                    }
                }
            }
        }

        /* set it back to null so the next call re-initialises the database config object */
        $this->database = null;
    }

    /**
     * This method overrides config nodes by providing an array which indexes directory into the config data
     * If the config data exists then it is overridden with the override.
     *
     * @param array $overrides Is the override data structure
     */
    public function setAdditionalOverrides(array $overrides) {
        if(null == $overrides || 0 == sizeof($overrides)) {
            return;
        }
        foreach($overrides as $configNode => $childNodes) {
            if (true == isset($this->data[$configNode])) {
                if (true == is_array($childNodes)) {
                    foreach ($childNodes as $childNodLevel2Key => $childNodesLevel2) {
                        if (true == is_array($childNodesLevel2)) {
                            foreach ($childNodesLevel2 as $childNodesLevel3key => $childNodesLevel3) {
                                if (true == isset($this->data[$configNode][$childNodLevel2Key][$childNodesLevel3key]) && null != $childNodesLevel3) {
                                    $this->data[$configNode][$childNodLevel2Key][$childNodesLevel3key] = $childNodesLevel3;
                                }
                            }
                        } else {
                            if (true == isset($this->data[$configNode][$childNodLevel2Key]) && null != $childNodesLevel2) {
                                $this->data[$configNode][$childNodLevel2Key] = $childNodesLevel2;
                            }
                        }
                    }
                } else {
                    if (true == isset($this->data[$configNode]) && null != $childNodes) {
                        $this->data[$configNode] = $childNodes;
                    }
                }
            }
        }
    }

    /**
     * This method initialises the database info to the defaults.
     *
     * I'm sure there is a nicer way to do this based on the Options fields but right now ....
     */
    private function databaseConfigInitialise()
    {
        if(false == isset($this->data['database']['engine'])) {
            $this->data['database']['engine'] = self::INPUT_ENGINE;
        }
        if(false == isset($this->data['database']['password'])) {
            $this->data['database']['password'] = self::INPUT_PASSWORD_DEFAULT;
        }
        if(false == isset($this->data['database']['config'])) {
            $this->data['database']['config'] = self::INPUT_CONFIGURATION_FILE;
        }
        if(false == isset($this->data['database']['database'])) {
            $this->data['database']['database'] = self::INPUT_DATABASE;
        }
        if(false == isset($this->data['database']['host'])) {
            $this->data['database']['host'] = self::INPUT_HOST;
        }
        if(false == isset($this->data['database']['username'])) {
            $this->data['database']['username'] = self::INPUT_USER;
        }
        if(false == isset($this->data['database']['sanitization_mode'])) {
            $this->data['database']['sanitization_mode'] = self::INPUT_MODE;
        }
    }

    /**
     * Returns true if this instance is in developer mode.
     * @return bool
     */
    public function getIsInDeveloperMode()
    {
        if(false == isset($this->data['developer_mode'])) {
            return false;
        }
        if('yes' == $this->data['developer_mode']) {
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
        if(null == $this->database) {
            $this->database = new Object($this->data['database']);
            $this->database->setDatabaseName($this->database->getDatabase());
            if(null == $this->database->getSanitizationMode()) {
                $this->database->setSanitizationMode('full');
            }
        }
        return $this->database;
    }

    /**
     * This method returns the sanitizer config as an object
     *
     * @return Object
     */
    public function getGeneralConfig()
    {
        return new Object(parent::getGeneralConfig());
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

    /**
     * Returns the log path, defaults to ./sanitizer.log
     * @return mixed
     */
    public function getLogPath()
    {
        if(null == parent::getLogPath()) {
            parent::setLogPath('sanitizer.log');
        }
        return parent::getLogPath();
    }
}