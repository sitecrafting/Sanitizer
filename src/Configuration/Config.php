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
    /**
     * Default password
     */
    const INPUT_PASSWORD_DEFAULT        = '';

    /**
     * Default config file name
     */
    const INPUT_CONFIGURATION_FILE      ='sanitize.json';

    /**
     * Default log name
     */
    const DEFAULT_LOG_NAME              = 'sanitizer.log';

    /**
     * Default database engine
     */
    const INPUT_ENGINE                  = 'mysql';

    /**
     * Default database host
     */
    const INPUT_HOST                    = 'localhost';

    /**
     * Default database user
     */
    const INPUT_USER                    = 'root';

    /**
     * Default database name
     */
    const INPUT_DATABASE                = 'sanitizer';

    /**
     * Full sanitisation mode
     */
    const SANITIZATION_MODE_FULL        = 'full';

    /**
     * Quick sanitisation mode
     */
    const SANITIZATION_MODE_QUICK        = 'quick';

    /**
     * Contains the database config
     *
     * @var null
     */
    private $_databaseName = null;

    /**
     * Initialise the config with the config file
     *
     * @param $configFile
     * @throws ConfigException
     */
    public function __construct($configFile)
    {
        if (false == file_exists($configFile)) {
            $msg = "Sadly I couldn't validate the config file {$configFile} because it doesn't exist!";
            throw new ConfigException($msg);
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
        if (false == isset($this->_data['database'])) {
            $this->_data['database'] = array();
        }

        $this->databaseConfigInitialise();

        foreach ($data as $configOverride) {

            if (2 == sizeof($configOverride)) {

                if (null != $configOverride[0] && null != $configOverride[1]) {
                    $key = strtolower($configOverride[0]);

                    switch($key) {
                        case 'password' :
                            //If the input has been changed by the user then we should use it
                            if ($configOverride[1] != self::INPUT_PASSWORD_DEFAULT) {
                                $this->_data['database'][$key] = $configOverride[1];
                            }
                            break;
                        case 'engine' :
                            //If the input has been changed by the user then we should use it
                            if ($configOverride[1] != self::INPUT_ENGINE) {
                                $this->_data['database'][$key] = $configOverride[1];
                            }
                            break;
                        case 'config' :
                            //If the input has been changed by the user then we should use it
                            if ($configOverride[1] != self::INPUT_CONFIGURATION_FILE) {
                                $this->_data['database'][$key] = $configOverride[1];
                            }
                            break;
                        case 'username' :
                            //If the input has been changed by the user then we should use it
                            if ($configOverride[1] != self::INPUT_USER) {
                                $this->_data['database'][$key] = $configOverride[1];
                            }
                            break;
                        case 'host' :
                            //If the input has been changed by the user then we should use it
                            if ($configOverride[1] != self::INPUT_HOST) {
                                $this->_data['database'][$key] = $configOverride[1];
                            }
                            break;
                        case 'database' :
                            //If the input has been changed by the user then we should use it
                            if ($configOverride[1] != self::INPUT_DATABASE) {
                                $this->_data['database'][$key] = $configOverride[1];
                            }
                            break;
                        case 'mode' :
                            //If the input has been changed by the user then we should use it
                            if ($configOverride[1] != self::INPUT_DATABASE) {
                                $this->_data['database']['sanitization_mode'] = $configOverride[1];
                            }
                            break;
                    }
                }
            }
        }

        /* set it back to null so the next call re-initialises the database config object */
        $this->_databaseName = null;
    }

    /**
     * This method overrides config nodes by providing an array which indexes directory into the config data
     * If the config data exists then it is overridden with the override.
     *
     * @param array $overrides Is the override data structure
     */
    public function setAdditionalOverrides(array $overrides) 
    {
        if (null == $overrides || 0 == sizeof($overrides)) {
            return;
        }

        foreach ($overrides as $configNode => $childNodes) {
            if (true == isset($this->_data[$configNode])) {
                if (true == is_array($childNodes)) {
                    foreach ($childNodes as $childNodLevelTwoKey => $childNodesLevelTwo) {
                        if (true == is_array($childNodesLevelTwo)) {
                            foreach ($childNodesLevelTwo as $childNodesLevelThreekey => $childNodesLevelThree) {
                                if (true == isset(
                                    $this->_data[$configNode]
                                    [$childNodLevelTwoKey][$childNodesLevelThreekey]
                                )
                                    && null != $childNodesLevelThree) {
                                    $this->_data[$configNode][$childNodLevelTwoKey][$childNodesLevelThreekey]
                                        = $childNodesLevelThree;
                                }
                            }
                        } else {
                            if (true == isset($this->_data[$configNode][$childNodLevelTwoKey])
                                && null != $childNodesLevelTwo) {
                                $this->_data[$configNode][$childNodLevelTwoKey] = $childNodesLevelTwo;
                            }
                        }
                    }
                } else {
                    if (true == isset($this->_data[$configNode]) && null != $childNodes) {
                        $this->_data[$configNode] = $childNodes;
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
        if (false == isset($this->_data['database']['engine'])) {
            $this->_data['database']['engine'] = self::INPUT_ENGINE;
        }

        if (false == isset($this->_data['database']['password'])) {
            $this->_data['database']['password'] = self::INPUT_PASSWORD_DEFAULT;
        }

        if (false == isset($this->_data['database']['config'])) {
            $this->_data['database']['config'] = self::INPUT_CONFIGURATION_FILE;
        }

        if (false == isset($this->_data['database']['database'])) {
            $this->_data['database']['database'] = self::INPUT_DATABASE;
        }

        if (false == isset($this->_data['database']['host'])) {
            $this->_data['database']['host'] = self::INPUT_HOST;
        }

        if (false == isset($this->_data['database']['username'])) {
            $this->_data['database']['username'] = self::INPUT_USER;
        }

        if (false == isset($this->_data['database']['sanitization_mode'])) {
            $this->_data['database']['sanitization_mode'] = self::SANITIZATION_MODE_FULL;
        }
    }

    /**
     * Returns true if this instance is in developer mode.
     * @return bool
     */
    public function getIsInDeveloperMode()
    {
        if (false == isset($this->_data['developer_mode'])) {
            return false;
        }

        if ('yes' == $this->_data['developer_mode']) {
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
        if (null == $this->_databaseName) {
            $this->_databaseName = new Object($this->_data['database']);
            $this->_databaseName->setDatabaseName($this->_databaseName->getDatabase());

            if (null == $this->_databaseName->getSanitizationMode()) {
                $this->_databaseName->setSanitizationMode('full');
            }
        }

        return $this->_databaseName;
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
        return $this->_data['tables'];
    }

    /**
     * Returns the log path, defaults to ./sanitizer.log
     * @return mixed
     */
    public function getLogPath()
    {
        if (null == parent::getLogPath()) {
            parent::setLogPath(self::DEFAULT_LOG_NAME);
        }

        return parent::getLogPath();
    }
}