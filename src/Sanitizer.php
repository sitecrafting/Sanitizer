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
 *
 * The basic flow is this:
 * <ul><li>App loads the config</li>
 * <li>App validates that the database can be connected to</li>
 * <li>App validates that the database exists</li>
 * <li>App validates all the tables are in the database which are specified
 * in the config
 *  <ul>
 *      <li>App also validates that the column is also in the table</li>
 *  </ul>
 * </li>
 * <li>App then iterates over each table and sanitizes the data</li></ul>
 *
 * Once the application has finished the database will be in a sanitized state.
 *
 *
 * Date: 18/05/15
 * Time: 12:50
 *
 * @category    Pegasus_Utilities
 * @package     Sanitizer
 * @license     MIT
 * @link        http://pegasus-commerce.com
 * @author      Philip Elson <phil@pegasus-commerce.com>
 *
 * http://symfony.com/doc/current/components/console/introduction.html
 */
namespace Pegasus;

use Pegasus\Configuration\Config;
use Pegasus\Resource\SanitizerException;
use Pegasus\Resource\TerminalPrinter;
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
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class Sanitizer extends Command implements TerminalPrinter
{
    /**
     * Default memory limit for this application is 2048M
     */
    const DEFAULT_MEMORY = '2048M';

    /**
     * Application confiuration instance
     *
     * @var null
     */
    public $config = null;

    /**
     * Console output instance
     *
     * @var OutputInterface
     */
    protected $output = null;

    /**
     * Console input instance
     *
     * @var InputInterface
     */
    protected $input = null;

    /**
     * Flag which identifies if the sanitisation is running. true if running.
     *
     * @var bool
     */
    protected $satitisationRunning = false;

    /**
     * Print cache used mainly when the sanitisation is running.
     *
     * @var array
     */
    protected $printCache = array();

    /**
     * Progress bar used to display the progress of sanitisation.
     *
     * @var ProgressBar
     */
    protected $progressBar = null;

    /**
     * Singleton instance of Sanitizer
     *
     * @var Sanitizer
     */
    protected static $sanitizer = null;

    /**
     * Instance of Logger
     *
     * @var Logger
     */
    protected $log = null;

    /**
     * Retuns a Singleton instance of the sanitizer
     *
     * @return null|Sanitizer
     */
    public static function getInstance()
    {
        if (null == self::$sanitizer) {
            self::$sanitizer = new Sanitizer();
        }
        return self::$sanitizer;
    }

    public static function getVersion()
    {
        return '0.0.2';
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
                Config::INPUT_ENGINE
            )
            ->addOption(
                'host',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Database Host',
                Config::INPUT_HOST
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                'Database Password',
                Config::INPUT_PASSWORD_DEFAULT
            )
            ->addOption(
                'username',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Database User',
                Config::INPUT_USER
            )
            ->addOption(
                'database',
                'db',
                InputOption::VALUE_OPTIONAL,
                'Database',
                Config::INPUT_DATABASE
            )
            ->addOption(
                'configuration',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database JSON Config File',
                Config::INPUT_CONFIGURATION_FILE
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_OPTIONAL,
                'Sanitisation Mode (full|quiet)',
                Config::INPUT_MODE
            )
            ->addOption(
                'memory',
                null,
                InputOption::VALUE_OPTIONAL,
                'Memory - PHP format',
                self::DEFAULT_MEMORY
            );
    }

    public function getConfig()
    {
        if (null == $this->config) {
            try
            {
                $this->config = new SanitizerConfig($this->input->getOption('configuration'));
                if (true == $this->config->getIsInDeveloperMode()) {
                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);
                }
                $this->config->setDatabaseOverride(
                    array(
                    array('Host', $this->input->getOption('host')),
                    array('Password', $this->input->getOption('password')),
                    array('Username', $this->input->getOption('username')),
                    array('Database', $this->input->getOption('database')),
                    array('Config', $this->input->getOption('configuration')),
                    array('Engine', $this->input->getArgument('engine')),
                    array('Mode', $this->input->getOption('mode')))
                );
            }
            catch (SanitizerException $exception)
            {
                $this->printLn($exception->getMessage(), 'fatal_error');
                exit(-1);
            }
        }
        return $this->config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input    = $input;
        $this->output   = $output;
        $this->setMemoryUsage();
        $this->loadOutputStyles();
        $this->getConfig();
        $this->getLog();
        $this->outputIntro();
        $this->renderOverviewTable();
        $this->loadDatabaseEngine();
        $this->sanitize();
    }

    /**
     * This method sets the max memory for this PHP application
     */
    private function setMemoryUsage()
    {
        if (false == ini_set("memory_limit", $this->input->getOption('memory'))) {
            ini_set("memory_limit", self::DEFAULT_MEMORY);
        }
    }

    /**
     * This method returns a singleton instance of the logger class.
     *
     * @return Logger
     */
    public function getLog()
    {
        if (null == $this->log) {
            $this->log = new Logger('Sanitizer');
            $this->log->pushHandler(new StreamHandler($this->getConfig()->getLogPath(), Logger::INFO));
        }
        return $this->log;
    }

    /**
     * This method initislaised the database engine with the confured options.
     * <ul><li>Defaults</li>
     * <li>
     *
     * @throws Engine\EngineNotFoundException
     * @throws SanitizerException
     * @throws Tables\TableException
     */
    private function loadDatabaseEngine()
    {
        $engine = Engine::start(
            array
            (
            'database_type' => $this->getConfig()->getDatabase()->getEngine(),
            'database_name' => $this->getConfig()->getDatabase()->getDatabase(),
            'server'        => $this->getConfig()->getDatabase()->getHost(),
            'username'      => $this->getConfig()->getDatabase()->getUsername(),
            'password'      => $this->getConfig()->getDatabase()->getPassword(),
            'charset'       => 'utf8'
            )
        );
        TableCollection::setTerminalPrinter($this);
        TableCollection::setEngine($engine);
    }

    protected function sanitize()
    {
        TableCollection::sanitizeTables();
    }

    public function loadOutputStyles()
    {
        $style = new OutputFormatterStyle('white', 'red', array('bold'));
        $this->output->getFormatter()->setStyle('warning', $style);
        $style = new OutputFormatterStyle('white', 'blue', array('bold'));
        $this->output->getFormatter()->setStyle('general', $style);
        $style = new OutputFormatterStyle('white', 'green', array('bold'));
        $this->output->getFormatter()->setStyle('notice', $style);
        $style = new OutputFormatterStyle('white', 'red', array('bold', 'underscore'));
        $this->output->getFormatter()->setStyle('fatal_error', $style);
    }

    /**
     * Outputs initial information on the app.
     */
    protected function outputIntro()
    {
        if (true == $this->getConfig()->getIsInDeveloperMode() || $this->getConfig()->getIsInDeveloperMode() == OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $this->printLn("App is in developer mode, therefore all output will be shown!", 'warning');
            $this->printLn("Verbosity ".$this->output->getVerbosity(), 'warning');
        }
        $this->printLn("Sanitisation mode '{$this->getConfig()->getDatabase()->getSanitizationMode()}'", 'normal');
    }

    /**
     * THis method renders the DB config data as a table allowing the user to confirm the data is accurate and they're
     * happy to continue.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    private function renderOverviewTable()
    {
        if (true == $this->canDisplayMessage(OutputInterface::VERBOSITY_NORMAL)) {
            $tableData = $this->getTableData();
            $this->_logTableData($tableData);
            $table = new Table($this->output);
            $table->setHeaders(array('Setting', 'Value'))->setRows($tableData);
            $table->render();
            $this->askPermissionToContinue();
        }
    }

    protected function askPermissionToContinue()
    {
        $helper = $this->getHelper('question');
        if (false == $helper->ask($this->input, $this->output, new ConfirmationQuestion('Are you happy to continue? [yes|no]', false))) {
            $this->printLn("Exiting due to user", "log");
            exit(-1);
        }
    }

    /**
     * This method takes a string and replaces the last half of the characters with *
     *
     * @param  $password
     * @return string
     */
    private function getSafeToDisplayPassword($password)
    {
        $length         = strlen($password);
        $obfuscation    = (int)$length / 2;
        $parts          = str_split($password, $obfuscation);
        $parts[1]       = str_repeat("*", strlen($parts[1]));
        return $parts[0].$parts[1];
    }

    /**
     * This method prints a line to the display.
     *
     * @param $message
     * @param null    $type
     */
    public function printLn($message, $type=null)
    {
        $this->printCache[] = array('message' => $message, 'type' => $type);
        if (null != $this->log) {
            $this->log->addInfo($message, array('type' => $type));
        }
        if (false == $this->getSatitisationState()) {
            $this->purgePrintCache();
        }
        if ('fatal_error' == $type) {
            $this->purgePrintCache();
        }
    }

    protected function purgePrintCache()
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
                    break;
                }
            case 'general' :
                {
                if (true == $this->canDisplayMessage(OutputInterface::VERBOSITY_VERY_VERBOSE)) {
                    $this->output->writeLn($this->formatMessage($type, $message));
                }
                    break;
                }
            case 'warning' :
                {
                if (true == $this->canDisplayMessage(OutputInterface::VERBOSITY_VERBOSE)) {
                    $this->output->writeLn($this->formatMessage($type, $message));
                }
                    break;
                }
            case 'notice' :
                {
                if (true == $this->canDisplayMessage(OutputInterface::VERBOSITY_VERY_VERBOSE)) {
                    $this->output->writeLn($this->formatMessage($type, $message));
                }
                    break;
                }
            case 'normal' :
                {
                if (true == $this->canDisplayMessage(OutputInterface::VERBOSITY_NORMAL)) {
                    $this->output->writeLn($message);
                }
                    break;
                }
            case 'fatal_error' :
                {
                $this->output->writeLn($this->formatMessage($type, $message), 'warning');
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
     * @param  $level
     * @return bool
     */
    private function canDisplayMessage($level)
    {
        //Default to verbose
        if (true == $this->getConfig()->getIsInDeveloperMode()) {
            return true;
        }
        return ($this->output->getVerbosity() >= $level);
    }

    /**
     * Method which adds type tags to the output if set.
     * @param $type
     * @param $message
     * @return string
     */
    protected function formatMessage($type, $message)
    {
        return $message = (null == $type) ? $message : "<{$type}>{$message}</{$type}>";;
    }

    /**
     * Returns the app mode. There are two options.
     * Sanitize, Verify
     *
     * @return mixed
     */
    public function getMode()
    {
        return 'sanitize';
    }


    public function setSatitisationRunning()
    {
        $this->satitisationRunning = true;
    }


    public function setSatitisationNotRunning()
    {
        $this->satitisationRunning = false;
        $this->purgePrintCache();
    }

    public function getSatitisationState()
    {
        return $this->satitisationRunning;
    }

    /**
     * Starts the progress bar and sets its width to $width
     *
     * @param $count is the count so far.
     * @param $width is the width of the bar.
     * @return $this
     */
    public function startProgressBar($count, $width=100)
    {
        $this->progressBar = new ProgressBar($this->output, $count);
        $this->progressBar->setBarWidth($width);
        return $this;
    }

    /**
     * Advances the progress bar
     *
     * @return $this
     */
    public function advanceProgressBar()
    {
        if (null != $this->progressBar) {
            $this->progressBar->advance();
        }
        return $this;
    }

    /**
     * Finishes the progress bar
     *
     * @return $this
     */
    public function advanceProgressFinish()
    {
        if (null != $this->progressBar) {
            $this->progressBar->finish();
        }
        return $this;
    }

    /**
     * Method which logs the table data.
     *
     * @param $tableData
     *
     * @return $this
     */
    private function _logTableData($tableData)
    {
        foreach ($tableData as $enteries) {
            $name   = $enteries[0];
            $value  = $enteries[1];
            $this->printLn("Sanitize settings: {$name}:{$value}", 'log');
        }
        return $this;
    }

    /**
     * Returns an array of table data to be displayed on the terminal
     *
     * @return array
     */
    protected function getTableData()
    {
        $password = $this->getConfig()->getDatabase()->getPassword();
        $tableData = array(
            array('Config Name', $this->getConfig()->getName()),
            array('Host', $this->getConfig()->getDatabase()->getHost()),
            array('Password', $this->getSafeToDisplayPassword($password)),
            array('User', $this->getConfig()->getDatabase()->getUsername()),
            array('Database', $this->getConfig()->getDatabase()->getDatabase()),
            array('Config', $this->getConfig()->getDatabase()->getConfig()),
            array('Engine', $this->getConfig()->getDatabase()->getEngine()),
            array('Mode', $this->getConfig()->getDatabase()->getSanitizationMode()));

        if (true == $this->canDisplayMessage(OutputInterface::VERBOSITY_VERBOSE)) {
            $tableData[] = array('Memory Limit', ini_get("memory_limit"));
        }
        return $tableData;
    }
}