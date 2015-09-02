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
use Pegasus\Application\Sanitizer\Resource\Object;
use Pegasus\Application\Sanitizer\Sanitizer;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class PostConditions extends AbstractObserver
{
    private $_sanitizer = null;

    private function _setSanitizer(Sanitizer $sanitizer) {
        $this->_sanitizer = $sanitizer;
    }

    private function _getSanitizer() {
        return $this->_sanitizer;
    }

    public function trigger(SimpleEvent $event) {
        $eventData = $event->getValues();
        if(null == $eventData) {
            return;
        }
        $sanitizer = $eventData->getSanitizer();
        if(null == $sanitizer) {
            return;
        }
        $this->_setSanitizer($sanitizer);
        $config = $sanitizer->getConfig();
        if(null == $config) {
            return;
        }
        $this->_processEvent($config->getPostConditions());
    }

    private function _processEvent($configData) {
        if(null == $configData || false == is_array($configData)) {
            return;
        }
        foreach($configData as $key => $data) {
            switch ($key) {
                case "export_database" : {
                    $this->_importDatabase($key, $data);
                    break;
                }
            }
        }
    }

    private function _importDatabase($key, $data) {
        $importData = new Object($data);
        if(null == $importData->getDestination()) {
            return;
        }
        $engine = $this->_getSanitizer()->getEngine();
        $fileName = str_replace('{date}', date($importData->getDateFormat()), $importData->getDestination());
        $fileName = str_replace('{time}', date($importData->getTimeFormat()), $fileName);
        $this->_getSanitizer()->getTerminalPrinter()->printLn("Exporting database to {$fileName}...");
        $ok = $engine->dump($fileName);
        if(true == $importData->getDrop()) {
            $engine->drop();
            $engine->create();
        }
        $this->_getSanitizer()->getTerminalPrinter()->printLn("Exporting finished", (true == $ok) ? null : 'error');
    }

    public function getEventArray() {
        return array('sanitizer.sanitize.after');
    }
}