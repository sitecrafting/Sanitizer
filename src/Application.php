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
 * Time: 12:50
 */

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    include_once __DIR__.'/../vendor/autoload.php';
} else if (file_exists(__DIR__.'/../../../autoload.php')) {
    include_once __DIR__.'/../../../autoload.php';
} else {
    include_once 'phar://sanitizer.phar/vendor/autoload.php';
}

use Pegasus\Application\Sanitizer\Sanitizer;
use Pegasus\Application\Sanitizer\Version;
use Pegasus\Application\Sanitizer\Validation;

$application = new Symfony\Component\Console\Application();
$application->add(Sanitizer::getInstance());
$application->add(Version::getInstance());
$application->add(Validation::getInstance());
$application->run();