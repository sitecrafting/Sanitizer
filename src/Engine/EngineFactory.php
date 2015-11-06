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
 * Time: 12:43
 */
namespace Pegasus\Application\Sanitizer\Engine;

use Pegasus\Application\Sanitizer\Engine\Exceptions\EngineNotFoundException;
use Pegasus\Application\Sanitizer\Resource\SanitizerException;

class EngineFactory
{
    private static $_engine = null;

    /**
     * Start your engines,  method is used to initialise the object
     *
     * @param  $config
     * @return EngineInterface
     * @throws EngineNotFoundException
     * @throws \Exception
     */
    public static function getSingleton($config)
    {
        if (null == self::$_engine) {
            self::$_engine = self::getInstance($config);
        }

        return self::$_engine;
    }

    /**
     * Returns a database engine instance
     *
     * @param $config
     * @return null|MySqlEngine
     * @throws EngineNotFoundException
     * @throws SanitizerException
     */
    public static function getInstance($config)
    {
        $engine = null;

        if (false == isset($config['database_type'])) {
            throw new SanitizerException("Engine Type/Name not found in Engine getInstance parameters");
        }

        $engineName = $config['database_type'];

        switch ($engineName) {
            case 'mysql' :
                $engine = new MySqlEngine($config);
                break;
            default :
                throw new EngineNotFoundException("Engine {$engineName} has done a runner!");
                break;
        }

        return $engine;
    }
}