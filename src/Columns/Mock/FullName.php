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
 * Date: 19/05/15
 * Time: 11:37
 */
namespace Pegasus\Application\Sanitizer\Columns\Mock;

use Pegasus\Application\Sanitizer\Columns\Mock;

class FullName extends AbstractMockData
{
    /**
     * Returns sanitised street names
     *
     * @return array
     */
    public function getValues()
    {
        static $fullName = null;

        if (null == $fullName) {
            $firstNames     = new FirstName();
            $secondNames    = new LastName();
            $fullName = array();

            for ($ii = 0; $ii < 15; $ii++) {
                $name = $firstNames->getRandomValue().' '.$secondNames->getRandomValue();

                if (false == in_array($name, $fullName)) {
                    $fullName[] = $name;
                }
            }
        }

        return $fullName;
    }
}