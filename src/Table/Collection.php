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
 * Time: 12:42
 */
namespace Pegasus\Application\Sanitizer\Table;

use Pegasus\Application\Sanitizer\Engine\EngineInterface;
use Pegasus\Application\Sanitizer\Engine\FatalEngineException;
use Pegasus\Application\Sanitizer\Resource\SanitizerException;
use Pegasus\Application\Sanitizer\IO\TerminalPrinter;
use Pegasus\Application\Sanitizer\Table;
use Pegasus\Application\Sanitizer\Table\Exceptions\TableException;
use Pegasus\Application\Sanitizer\Table\Exceptions\TableCommentException;

class Collection
{
    private $_engine                = null;

    private $_possibleTableCount    = 0;

    private $_addedTableCount       = 0;

    private $_collection            = null;

    public function __construct(EngineInterface $engine) 
    {
        $this->setEngine($engine);
    }

    public function setEngine(EngineInterface $engine) 
    {
        if (null == $engine) {
            throw new TableException("Someone has passed this table collection a null engine");
        }
        $this->_engine = $engine;
    }

    public function getEngine() 
    {
        if (null == $this->_engine) {
            throw new TableException("Engine can't be found!..");
        }
        return $this->_engine;
    }

    public function getSize() 
    {
        if (null == $this->_collection) {
            return 0;
        }
        return sizeof($this->_collection);
    }

    public function getCollection(TerminalPrinter $printer) 
    {
        if (null == $this->_collection) {
            $this->_collection      = array();
            $tables                 = $printer->getConfig()->getTables();
            foreach ($tables as $tableName => $tableConfig) {
                $this->_possibleTableCount++;
                try {
                    $this->_collection[] = Factory::getInstance($tableName, $tableConfig, $printer, $this->getEngine());
                    $printer->printLn("Added $tableName to sanitise list ", 'notice');
                    $this->_addedTableCount++;
                } catch(FatalEngineException $e) {
                    $printer->printLn('Fatal: '.$e->getMessage(), 'fatal_error');
                    exit(-200);
                } catch(TableCommentException $e) {
                    $printer->printLn($e->getMessage(), 'notice');
                } catch(SanitizerException $e) {
                    $printer->printLn($e->getMessage(), 'warning');
                }
            }
            $printer->printLn("All Possible Tables = \"".$this->getPossibleTableCount()."\"", 'notice');
            $printer->printLn("Queued Tables = \"".$this->getAddedTableCount()."\"", 'notice');
            $printer->printLn("Skipped Tables = ".($this->getPossibleTableCount() - $this->getAddedTableCount()), 'notice');
        }
        return $this->_collection;
    }

    public function getPossibleTableCount() 
    {
        return $this->_possibleTableCount;
    }

    public function getAddedTableCount() 
    {
        return $this->_addedTableCount;
    }

    public function getSomeTablesAreBeingSkipped() 
    {
        return $this->getAddedTableCount() != $this->getPossibleTableCount();
    }
}
