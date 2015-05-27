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

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pegasus\Resource\SanitizerException;
use Pegasus\Sanitizer;
use Pegasus\Engine\medoo;

abstract class Engine extends medoo implements EngineInterface
{
    private static $engine = null;

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
        if(null == self::$engine)
        {
            if (false == isset($config['database_type']))
            {
                throw new SanitizerException("Engine Type/Name not found in Engine start parameters");
            }
            $engineName = $config['database_type'];
            switch ($engineName)
            {
                case 'mysql' :
                {
                    self::$engine = new MySqlEngine($config);
                    break;
                }
                default :
                {
                    throw new EngineNotFoundException("Engine {$engineName} has done a runner!");
                    break;
                }
            }
        }
        return self::$engine;
    }

    public function logError($query=null)
    {
        $error = parent::error();
        if(false == is_array($error))
        {
            $error = array($error);
        }
        $this->log = new Logger('SanitizerError');
        $this->log->pushHandler(new StreamHandler(Sanitizer::getInstance()->getConfig()->getLogPath(), Logger::CRITICAL));
        $this->log->addInfo('Fetch Error', $error);
        if(null != $query)
        {
            $this->log->addInfo('Fetch Error Additional Info', array('info' => $query));
        }
    }
}