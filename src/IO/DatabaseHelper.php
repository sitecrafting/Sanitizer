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

use Pegasus\Application\Sanitizer\Engine\EngineFactory;
use Pegasus\Application\Sanitizer\Resource\Object;
use Pegasus\Application\Sanitizer\Resource\SanitizerException;
use Pegasus\Application\Sanitizer\Sanitizer;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class DatabaseHelper
{
    /**
     * Default export file name
     */
    const DEFAULT_EXPORT_FILE_NAME = '{database_name}_export_{date}_{time}.sql';

    /**
     * Default export date format
     */
    const DEFAULT_EXPORT_DATE_FORMAT = "d-m-Y";

    /**
     * Default export time format
     */
    const DEFAULT_EXPORT_TIME_FORMAT = "G-i-s-e";

    /**
     * Export a database to specified file
     *
     * @param $data
     * @param Sanitizer $sanitizer
     * @return mixed
     * @throws SanitizerException
     * @throws \Pegasus\Application\Sanitizer\TableException
     */
    public function exportDatabase($data, Sanitizer $sanitizer)
    {
        $importData = new Object($data);

        if (null == $importData->getDestination()) {
            throw new SanitizerException('Database export needs a file name to create!');
        }

        $engine         = $sanitizer->getEngine();
        $databaseName   = $sanitizer->getConfig()->getDatabase()->getDatabase();
        $dateFormat     = $importData->getDateFormat();
        $timeFormat     = $importData->getTimeFormat();
        $dateFormat     = (null == $dateFormat) ? self::DEFAULT_EXPORT_DATE_FORMAT : $dateFormat;
        $timeFormat     = (null == $timeFormat) ? self::DEFAULT_EXPORT_TIME_FORMAT : $timeFormat;
        $fileName       = str_replace(
            array('{date}', '{time}', '{database_name}'),
            array(date($dateFormat), date($timeFormat), $databaseName),
            $importData->getDestination()
        );
        $ok             = $engine->dump($fileName);

        //Only dump if the export was ok
        if (true == $ok && true == $importData->getDrop()) {
            $sanitizer->getTerminalPrinter()->printLn("Dropping database");
            $engine->drop();
            $engine->create();
        }

        return $ok;
    }

    /**
     * Import a database from file
     *
     * @param $data
     * @param $sanitizer
     */
    public function importDatabase($data, Sanitizer $sanitizer)
    {
        $importData = new Object($data);

        if (null == $importData->getSource()) {
            return;
        }

        if (false == file_exists($importData->getSource())) {
            throw new FileNotFoundException("File not found, {$importData->getSource()}");
        }

        $engine = $sanitizer->getEngine();
        $engine->drop();
        $engine->create();
        $engine->useDb();
        $engine->source($importData->getSource());
    }

    /**
     * This method is used to copy down a database
     *
     * @param $data
     * @param Sanitizer $sanitizer
     * @return bool|Object
     * @throws SanitizerException
     * @throws \EngineException
     * @throws \Pegasus\Application\Sanitizer\Engine\Exceptions\EngineNotFoundException
     */
    public function copyDown($data, Sanitizer $sanitizer)
    {
        $importData = new Object($data);

        if (null == $importData->getDatabaseType()) {
            $importData->setDatabaseType('mysql');
        }

        $importData->setSkipInit(true);
        $engine     = EngineFactory::getInstance($importData->getData(), true);
        $result   = $engine->copyDown($importData);

        if (false == $result) {
            throw new \EngineException(
                "Error copying down database, check the credentials are correct and the server is accessible"
            );
        }

        $filePath = $result->getFileName();

        if (false == file_exists($filePath)) {
            throw new \EngineException("Copy down dump file not found");
        }

        $this->importDatabase(array('source' => $filePath), $sanitizer);
        unlink($filePath);
        return $result;
    }

    /**
     * Run sql from file
     *
     * @param $fileName
     * @param Sanitizer $sanitizer
     * @throws \Pegasus\Application\Sanitizer\TableException
     */
    public function loadFromSource($fileName, Sanitizer $sanitizer)
    {
        if (false == file_exists($fileName)) {
            throw new FileNotFoundException("File not found, {$fileName}");
        }

        $engine = $sanitizer->getEngine();
        $engine->source($fileName);
    }
}