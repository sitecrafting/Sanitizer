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
 * Time: 16:39
 */

namespace Pegasus\Application\Sanitizer\IO;

use Pegasus\Application\Sanitizer\Resource\Object;
use Pegasus\Application\Sanitizer\Resource\SanitizerException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class DatabaseHelper
{
    const DEFAULT_EXPORT_FILE_NAME = '{database_name}_export_{date}_{time}.sql';

    const DEFAULT_EXPORT_DATE_FORMAT = "d-m-Y";

    const DEFAULT_EXPORT_TIME_FORMAT = "G-i-s-e";

    public function exportDatabase($data, $sanitizer)
    {
        $importData = new Object($data);
        if(null == $importData->getDestination()) {
            throw new SanitizerException('Database export needs a file name to create!');
        }
        $engine         = $sanitizer->getEngine();
        $databaseName   = $sanitizer->getConfig()->getDatabase()->getDatabase();
        $dateFormat     = (null == $importData->getDateFormat()) ? self::DEFAULT_EXPORT_DATE_FORMAT : $importData->getDateFormat();
        $timeFormat     = (null == $importData->getTimeFormat()) ? self::DEFAULT_EXPORT_TIME_FORMAT : $importData->getTimeFormat();
        $fileName       = str_replace('{date}', date($dateFormat), $importData->getDestination());
        $fileName       = str_replace('{time}', date($timeFormat), $fileName);
        $fileName       = str_replace('{database_name}', $databaseName, $fileName);
        $ok             = $engine->dump($fileName);
        if(true == $importData->getDrop()) {
            $engine->drop();
            $engine->create();
        }
        return $ok;
    }

    public function importDatabase($data, $sanitizer)
    {
        $importData = new Object($data);
        if(null == $importData->getSource()) {
            return;
        }
        if(false == file_exists($importData->getSource())) {
            throw new FileNotFoundException("File not found, {$importData->getSource()}");
        }
        $engine = $sanitizer->getEngine();
        $engine->drop();
        $engine->create();
        $engine->useDb();
        $engine->source($importData->getSource());

    }
}