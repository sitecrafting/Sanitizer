<?php
/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 18/05/15
 * Time: 12:50
 */


require 'vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

use Pegasus\Sanitizer;
use Symfony\Component\Console\Application as ConsoleApp;

$sanitizer = new Sanitizer();
$application = new ConsoleApp();
$application->add($sanitizer);
//$application->setDefaultCommand($sanitizer->getName());
$application->run();