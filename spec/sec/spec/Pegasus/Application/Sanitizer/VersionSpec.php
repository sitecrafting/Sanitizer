<?php

namespace spec\Pegasus\Application\Sanitizer;

use Pegasus\Application\Sanitizer\Sanitizer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VersionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Pegasus\Application\Sanitizer\Version');
    }

    function it_should_return_an_instance_of_Version() {
        self::getInstance()->shouldReturnAnInstanceOf('Pegasus\Application\Sanitizer\Version');
    }

    function it_should_return_the_current_version() {
        self::getInstance()->getVersion()->shouldEqual(Sanitizer::VERSION);
    }
}
