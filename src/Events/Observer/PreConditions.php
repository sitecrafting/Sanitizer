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
 * Date: 02/09/15
 * Time: 10:02
 *
 * PHP version 5.3+
 *
 * @category Pegasus_Tools
 * @package  Pegasus_Sanitizer
 * @author   Philip Elson <phil@pegasus-commerce.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     http://pegasus-commerce.com
 */
namespace Pegasus\Application\Sanitizer\Events\Observer;

use Pegasus\Application\Sanitizer\Events\SimpleEvent;
use Pegasus\Application\Sanitizer\IO\DatabaseHelper;
use Pegasus\Application\Sanitizer\Resource\Object;
use Pegasus\Application\Sanitizer\Sanitizer;

class PreConditions extends AbstractObserver
{
    private $_sanitizer = null;

    private function _setSanitizer(Sanitizer $sanitizer)
    {
        $this->_sanitizer = $sanitizer;
    }

    private function _getSanitizer()
    {
        return $this->_sanitizer;
    }

    public function trigger(SimpleEvent $event, $eventName=null)
    {
        $eventData = $event->getValues();

        if (null == $eventData) {
            return;
        }

        $sanitizer = $eventData->getSanitizer();

        if (null == $sanitizer) {
            return;
        }

        $this->_setSanitizer($sanitizer);
        $config = $sanitizer->getConfig();

        if (null == $config) {
            return;
        }

        switch ($eventName) {
            case 'sanitizer.sanitize.before' :
                $this->_processEvent($config->getPreConditions());
                break;
        }
    }

    private function _processEvent($configData) 
    {
        $helper = new DatabaseHelper();

        if (null == $configData || false == is_array($configData)) {
            return;
        }

        foreach ($configData as $key => $data) {
            switch ($key) {
                case "import_database" :
                    $this->_getSanitizer()->getTerminalPrinter()->printLn("Importing database...");
                    $helper->importDatabase($data, $this->_getSanitizer());
                    $this->_getSanitizer()->getTerminalPrinter()->printLn("Database imported \n");
                    break;
                case "copy_down_database" :
                    $this->_getSanitizer()->getTerminalPrinter()->printLn("Copying down database...");
                    $result = $helper->copyDown($data, $this->_getSanitizer());
                    $this->_getSanitizer()->getTerminalPrinter()->printLn("Commands", 'debug');
                    $this->_getSanitizer()->getTerminalPrinter()->printLn(
                        implode("\n", $result->getCommands()),
                        'debug'
                    );
                    $this->_getSanitizer()->getTerminalPrinter()->printLn("Commands", 'debug');
                    $this->_getSanitizer()->getTerminalPrinter()->printLn("Database copied down \n");
                    break;
            }
        }
    }

    public function getEventsToListenForArray() 
    {
        return array('sanitizer.sanitize.before');
    }
}