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
 *
 * The basic flow is this:
 * <ul><li>App loads the config</li>
 * <li>App validates that the database can be connected to</li>
 * <li>App validates that the database exists</li>
 * <li>App validates all the tables are in the database which are specified in the config
 *      <ul><li>App also validates that the column is also in the table</li></ol>
 * </li>
 * <li>App then iterates over each table and sanitizes the data</li></ul>
 *
 * Once the application has finished the database will be in a sanitized state.
 *
 *
 * Date: 18/05/15
 * Time: 12:50
 *
 * http://symfony.com/doc/current/components/console/introduction.html#using-command-arguments
 */
namespace Pegasus;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Pegasus\Configuration\Config as SanitizerConfig;
use Pegasus\Application;
use Pegasus\Engine\Engine;
use Pegasus\Tables\Collection as TableCollection;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;


class Sanitizer extends Command
{
    public $config = null;

    private $output = null;

    private $input = null;

    private $satitisationRunning = false;

    private $printCache = array();

    public function getVersion()
    {
        return '0.0.1';
    }

    protected function configure()
    {
        $this
            ->setName('sanitize')
            ->setDescription('Database Sanitization')
            ->addArgument(
                'engine',
                InputArgument::OPTIONAL,
                'Database Engine',
                'mysql'
            )
            ->addOption(
                'host',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Database Host',
                'localhost'
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                'Database Password'
            )
            ->addOption(
                'username',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Database User',
                'root'
            )
            ->addOption(
                'database',
                'db',
                InputOption::VALUE_OPTIONAL,
                'Database',
                'sanitizer'
            )
            ->addOption(
                'configuration',
                'config',
                InputOption::VALUE_OPTIONAL,
                'Database JSON Config File',
                'database.json'
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_OPTIONAL,
                'Mode (sanitize, validate)',
                'sanitize'
            )
        ;
    }

    public function getConfig()
    {
        return $this->config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input    = $input;
        $this->output   = $output;
        $this->loadConfig(array(
            array('Host', $input->getOption('host')),
            array('Password', $input->getOption('password')),
            array('Username', $input->getOption('username')),
            array('Database', $input->getOption('database')),
            array('Config', $input->getOption('configuration')),
            array('Engine', $input->getArgument('engine'))));
        // ...
        $this->loadOutputStyles();
        $this->outputIntro();
        $this->renderOverviewTable();

        Engine::start($this);
        TableCollection::setSanitizer($this);
        $this->sanitizeTables();
    }

    public function loadOutputStyles()
    {
        $style = new OutputFormatterStyle('white', 'red', array('bold'));
        $this->output->getFormatter()->setStyle('warning', $style);
        $style = new OutputFormatterStyle('white', 'blue', array('bold'));
        $this->output->getFormatter()->setStyle('general', $style);
        $style = new OutputFormatterStyle('white', 'green', array('bold'));
        $this->output->getFormatter()->setStyle('notice', $style);
        $style = new OutputFormatterStyle('white', 'red', array('bold', 'blink'));
        $this->output->getFormatter()->setStyle('fatal_error', $style);
    }

    /**
     * Outputs initial information on the app.
     */
    private function outputIntro()
    {
        if(true == $this->getConfig()->getIsInDeveloperMode())
        {
            $this->printLn("App is in developer mode, therefore all output will be shown!", 'warning');
        }
    }

    /**
     * THis method renders the DB config data as a table allowing the user to confirm the data is accurate and they're
     * happy to continue.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function renderOverviewTable()
    {
        if (true == $this->canDisplayMessage(OutputInterface::VERBOSITY_VERBOSE))
        {
            $table = new Table($this->output);
            $table->setHeaders(array('Setting', 'Value'))->setRows(array(
                array('Host',           $this->getConfig()->getDatabase()->getHost()),
                array('Password',       $this->getConfig()->getDatabase()->getPassword()),
                array('User',           $this->getConfig()->getDatabase()->getUsername()),
                array('Database',       $this->getConfig()->getDatabase()->getDatabase()),
                array('Config',         $this->getConfig()->getDatabase()->getConfig()),
                array('Engine',         $this->getConfig()->getDatabase()->getEngine())));
            $table->render();
            if('sanitize' == $this->input->getOption('mode'))
            {
                $helper = $this->getHelper('question');
                if (false == $helper->ask($this->input, $this->output, new ConfirmationQuestion('Are you happy to continue? [yes|no]', false)))
                {
                    return;
                }
            }
        }
    }

    public function printLn($message, $type=null)
    {
        $this->printCache[] = array('message' => $message, 'type' => $type);
        if(false == $this->satitisationRunning)
        {
            $this->purgePrintCache();
        }
    }

    private function purgePrintCache()
    {
        foreach($this->printCache as $item)
        {
            $message    = $item['message'];
            $type       = $item['type'];
            switch($type)
            {
                case null :
                {
                    $this->output->writeLn($message);
                }
                case 'general' :
                {
                    if(true == $this->canDisplayMessage(OutputInterface::OUTPUT_NORMAL))
                    {
                        $this->output->writeLn($this->formatMessage($type, $message));
                    }
                    break;
                }
                case 'warning' :
                {
                    if(true == $this->canDisplayMessage(OutputInterface::OUTPUT_NORMAL))
                    {
                        $this->output->writeLn($this->formatMessage($type, $message));
                    }
                    break;
                }
                case 'notice' :
                {
                    if(true == $this->canDisplayMessage(OutputInterface::VERBOSITY_VERY_VERBOSE))
                    {
                        $this->output->writeLn($this->formatMessage($type, $message));
                    }
                    break;
                }
                case 'fatal_error' :
                {
                    if(true == $this->canDisplayMessage(OutputInterface::VERBOSITY_QUIET))
                    {
                        $this->output->writeLn($this->formatMessage($type, $message));
                    }
                    break;
                }
            }
        }
        $this->printCache = array();
    }

    /**
     * This function returns true if the verbosity level equal to the level provided.
     * If this is in developer mode than it will always return true.
     *
     * @param $level
     * @return bool
     */
    private function canDisplayMessage($level)
    {
        //Default to verbose
        if(true == $this->getConfig()->getIsInDeveloperMode())
        {
            return true;
        }
        return ($level == $this->output->getVerbosity());
    }

    /**
     * Method which adds type tags to the output if set.
     * @param $type
     * @param $message
     * @return string
     */
    private function formatMessage($type, $message)
    {
        return $message = (null == $type) ? $message : "<{$type}>{$message}</{$type}>";;
    }

    /**
     * Method which initialises the application config.
     *
     * @param $databaseMap
     */
    private function loadConfig($databaseMap)
    {
        $this->config = new SanitizerConfig($this->input->getOption('configuration'));
        $this->config->setDatabaseOverride($databaseMap);
        if(true == $this->config->getIsInDeveloperMode())
        {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
    }

    /**
     * This method iterates over the tables.
     */
    private function sanitizeTables()
    {
        $this->satitisationRunning = true;
        $sanitized = array();
        $tables = TableCollection::getCollection();
        if('sanitize' == $this->input->getOption('mode'))
        {
            $tableCount = sizeof($tables);
            $progress = new ProgressBar($this->output, $tableCount);
            $progress->setBarWidth(100);
            foreach($tables as $table)
            {
                $rows = $table->sanitize();
                if(true == $table->doCommand())
                {
                    $sanitized[] = "{$table->getCommand()} applied to {$table->getTableName()} and effected {$rows} rows";
                }
                else
                {
                    $sanitized[] = "Sanitized {$table->getTableName()} and updated {$rows} rows";
                }
                $progress->advance();
            }
            $progress->finish();
            $this->output->writeLn("\n");
            foreach($sanitized as $san)
            {
                $this->printLn($san, 'notice');
            }
        }
        else
        {
            $this->printLn($this->input->getOption('mode').' mode selected, exiting before sanitisation', 'general');
        }
        $this->output->writeLn("\n");
        $this->satitisationRunning = false;
        $this->purgePrintCache();
    }
}