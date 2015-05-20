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
 * Time: 12:43
 */
namespace Pegasus\Engine;

require_once 'vendor/catfan/medoo/medoo.php';

use Pegasus\Configuration\Config;

class Engine extends \medoo implements EngineInterface
{
    const ENGINE_NAME = 'engine';

    private static $engine = null;

    private static $config = null;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $dbInitialisationData = array
        (
            'database_type' => self::ENGINE_NAME,
            'database_name' => $this->config->getDatabase()->getDatabase(),
            'server' => $this->config->getDatabase()->getHost(),
            'username' => $this->config->getDatabase()->getUsername(),
            'password' => $this->config->getDatabase()->getPassword(),
            'charset' => 'utf8'
        );
        parent::__construct($dbInitialisationData);
    }

    /**
     * Start your engines,  method is used to initialise the object
     *
     * @param $config
     * @return mixed
     * @throws EngineNotFoundException
     * @throws \Exception
     */
    public static function start($config)
    {
        if(null == $config)
        {
            throw new \Exception("Creating an Engine instance needs config, sadly no config was provided");
        }
        self::$config = $config;
        $engineName = $config->getDatabase()->getEngine();
        switch($engineName)
        {
            case MySqlEngine::ENGINE_NAME :
            {
                self::$engine = new MySqlEngine($config);
            }
            default :
            {
                throw new EngineNotFoundException("Engine {$engineName} has done a runner!");
                break;
            }
        }
        return self;
    }

    /**
     * Returns a Singleton instance of a database Engine. On the first call the Config
     * can not be null.
     *
     * @return null
     * @throws EngineNotFoundException if no engine is found.
     */
    public static function getInstance()
    {
        return self::$engine;
    }
}