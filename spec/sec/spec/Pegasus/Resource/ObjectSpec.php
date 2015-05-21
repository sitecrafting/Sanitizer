<?php

namespace spec\Pegasus\Resource;

use Pegasus\Resource\Object;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ObjectSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Pegasus\Resource\Object');
    }

    function it_should_return_the_set_value_when_i_call_get()
    {
        $this->setExample('example_data');
        $this->getExample()->shouldEqual('example_data');
        $this->setExample('woosa');
        $this->getExample()->shouldEqual('woosa');
        $this->setExample(null);
        $this->getExample()->shouldEqual(null);
    }

    function it_should_return_the_set_value_when_i_call_get_on_constructor_set_data()
    {
        $this->beConstructedWith(array('example' => 'woosa', 'null' => null, 'object' => new Object()));
        $this->getExample()->shouldEqual('woosa');
        $this->getNull()->shouldEqual(null);
        $this->getObject()->shouldReturnAnInstanceOf('Pegasus\Resource\Object');
    }
}
