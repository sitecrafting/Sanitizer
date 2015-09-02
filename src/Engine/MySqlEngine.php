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
 * Time: 12:37
 */
namespace Pegasus\Application\Sanitizer\Engine;

use Pegasus\Application\Sanitizer\Engine\Exceptions\EngineException;
use Pegasus\Application\Sanitizer\Engine\Exceptions\FatalEngineException;
use Pegasus\Application\Sanitizer\Sanitizer;

class MySqlEngine extends medoo implements EngineInterface
{
    public function getEngineName()
    {
        return 'mysql';
    }

    public function logError($query=null)
    {
        $error = $this->error();
        if(false == is_array($error)) {
            $error = array($error);
        }
        Sanitizer::getInstance()->getLog()->addInfo('Fetch Error', $error);
        if(null != $query) {
            Sanitizer::getInstance()->getLog()->addInfo('Fetch Error Additional Info', array('info' => $query));
        }
    }

    /**
     * Returns true if the table name exists in the database
     *
     * @param  $tableName
     * @return bool if found, false otherwise
     * @throws FatalEngineException if an error occurred
     */
    public function tableExists($tableName)
    {
        /* @var $result PDOStatement */
        $query = "SHOW TABLES LIKE '{$tableName}'";
        $result = $this->query($query);
        if(false == $result) {
            $this->logError($query);
            throw new FatalEngineException("Table exists check failed, error logged");
        }
        $result = $result->fetchAll();
        return 1 == sizeof($result);
    }

    /**
     * Returns true if the column exists in the table
     *
     * @param  $tableName
     * @param  $columnName
     * @return bool if found, false otherwise
     * @throws FatalEngineException if an error occurred
     */
    public function columnExists($tableName, $columnName)
    {
        $query = "SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'";
        $result = $this->query($query);
        if(false == $result) {
            $this->logError($query);
            throw new FatalEngineException("Column check failed, error logged");
        }
        $result = $result->fetchAll();
        return 1 == sizeof($result);
    }

    /**
     * Returns the name of the primary key column
     *
     * @param  $tableName Is the name of the table to extract the primary key from
     * @return string primary key column name
     * @throws EngineException if the primary key was not found in the returned data.
     * @throws FatalEngineException  if an error occurred
     */
    public function getPrimaryKeyName($tableName)
    {
        $sql = "SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'";
        $result = $this->query($sql);
        if(false == $result) {
            $this->logError($sql);
            throw new FatalEngineException("Primary key extraction failed error logged");
        }
        $result = $result->fetch();
        if(false == isset($result['Column_name'])) {
            throw new EngineException("Primary key could not be found for table '{$tableName}'");
        }
        return $result['Column_name'];
    }


    public function drop() {
        $sql = "DROP DATABASE `{$this->database_name}`";
        $result = $this->query($sql);
        if(false == $result) {
            $this->logError($sql);
            throw new FatalEngineException("Unable to drop database");
        }
        return true;
    }

    public function create() {
        $sql = "CREATE DATABASE `{$this->database_name}`";
        $result = $this->query($sql);
        if(false == $result) {
            $this->logError($sql);
            throw new FatalEngineException("Unable to create database");
        }
        return true;
    }

    public function useDb() {
        $sql = "USE `{$this->database_name}`";
        $result = $this->query($sql);
        if(false == $result) {
            $this->logError($sql);
            throw new FatalEngineException("Unable to select database");
        }
        return true;
    }

    public function source($fileName) {
        $sql = file_get_contents($fileName);
        $result = $this->query($sql);
        if(false == $result) {
            $this->logError($sql);
            throw new FatalEngineException("Unable to import from source file, {$sql}");
        }
        return true;
    }

    public function dump($fileName) {
        $command = "mysqldump -u {$this->username} -p{$this->password} -h {$this->server} {$this->database_name} > '{$fileName}'";
        exec($command, $output=array(), $worked);
        return (0 == $worked) ? true : false;
    }
}