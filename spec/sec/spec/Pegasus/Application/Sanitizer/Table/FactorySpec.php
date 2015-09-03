<?php

namespace spec\Pegasus\Application\Sanitizer\Table;

use Pegasus\Application\Sanitizer\Engine\EngineFactory;
use Pegasus\Application\Sanitizer\Engine\EngineInterface;
use Pegasus\Application\Sanitizer\IO\TerminalPrinter;
use Pegasus\Application\Sanitizer\Table\Tables\AbstractTable;
use Pegasus\Application\Sanitizer\Table\Tables\Flat;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Pegasus\Application\Sanitizer\Table\Factory');
    }

    private function _getEngine() {
        static $engine = null;
        if(null == $engine) {
            $engine = EngineFactory::getInstance(
                array
                (
                    'database_type'     => 'mysql',
                    'database_name'     => 'sanitizer',
                    'server'            => 'localhost',
                    'username'          => 'root',
                    'password'          => 'password',
                    'charset'           => 'utf8'
                )
            );
        }
        return $engine;
    }

//    function it_should_return_a_valid_table_instance(TerminalPrinter $printer) {
//
//        $return = 'Pegasus\Application\Sanitizer\Table\Tables\AbstractTable';
//        $data = array('type' => 'flat', Flat::FIELD_COLUMN => 'value');
//        self::getInstance('core_config_data', $data, $printer, $this->_getEngine())->shouldReturnAnInstanceOf($return);
//    }
}
