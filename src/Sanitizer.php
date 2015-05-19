<?php
/**
 *
 * This application is designed to sanitize a database. Never ever ever ever use it on a live DB!
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
 * Created by PhpStorm.
 * User: Philip Elson
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


class Sanitizer extends Command
{
    public $config = null;

    private $output = null;

    private $input = null;

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
                'sanitize'
            )
            ->addOption(
                'configuration',
                'config',
                InputOption::VALUE_OPTIONAL,
                'Database JSON Config File',
                'database.json'
            )
        ;
    }

    public function getConfig()
    {
        return $this->config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
        $style = new OutputFormatterStyle('white', 'red', array('bold', 'blink'));
        $output->getFormatter()->setStyle('warning', $style);
        $style = new OutputFormatterStyle('white', 'blue', array('bold'));
        $output->getFormatter()->setStyle('general', $style);
        $this->input    = $input;
        $this->output   = $output;
        $this->renderOverviewTable();
        $this->loadConfig(array(
            array('Host', $input->getOption('host')),
            array('Password', $input->getOption('password')),
            array('Username', $input->getOption('username')),
            array('Database', $input->getOption('database')),
            array('Config', $input->getOption('configuration')),
            array('Engine', $input->getArgument('engine'))));
        //Engine::start($this->config);
        TableCollection::setSanitizer($this);
        $this->sanitizeTables();
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
        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity())
        {
            $table = new Table($this->output);
            $table->setHeaders(array('Setting', 'Value'))->setRows(array(
                array('Host',           $this->input->getOption('host')),
                array('Password',       (null == $this->input->getOption('password')) ? '--empty--' : str_repeat("*", strlen($this->input->getOption('password')))),
                array('User',           $this->input->getOption('username')),
                array('Database',       $this->input->getOption('database')),
                array('Config',         $this->input->getOption('configuration')),
                array('Engine',         $this->input->getArgument('engine'))));
            $table->render();
            $helper = $this->getHelper('question');
            if (false == $helper->ask($this->input, $this->output, new ConfirmationQuestion('Are you happy to continue? [yes|no]', false))) {
                return;
            }
        }
    }

    public function printLn($message, $type=null)
    {
       // if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity())
        {
            $message = (null == $type) ? $message : "<{$type}>{$message}</{$type}>";
            $this->output->writeLn($message);
        }
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
    }

    private function sanitizeTables()
    {
        foreach(TableCollection::getCollection() as $table)
        {
            die(print_r($table));
        }
    }

}