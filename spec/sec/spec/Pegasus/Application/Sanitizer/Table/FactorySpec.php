<?php

namespace spec\Pegasus\Application\Sanitizer\Table;

use Pegasus\Application\Sanitizer\Engine\EngineFactory;
use Pegasus\Application\Sanitizer\IO\TerminalPrinter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Pegasus\Application\Sanitizer\Table\Factory');
    }

    private function _getEngine() {
        return EngineFactory::getSingleton(
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

    private function getUpdateData() {
        return Array
        (
            'data_type' => 'text',
            'type' => 'update',
            'rules' => array(
                array(
                    'data_type' => 'text',
                    'comment'   => 'Base Un-secure URL',
                    'column'    => 'value',
                    'where'     => Array(
                        'path'      => 'web/unsecure/base_url'
                    ),
                    'to'        => 'http://test-url.dev:8080/',
                )
            ),
        );

    }

    private function getFlatData() {
        return Array
        (
            Array (
                'column' => 'value',
                'data_type' => 'text',
            ),
        );
    }

    function it_should_return_a_valid_table_instance(TerminalPrinter $printer) {

        $return = 'Pegasus\Application\Sanitizer\Table\Tables\AbstractTable';
       // $data = array('type' => 'flat', Flat::FIELD_COLUMN => 'value');
        self::getInstance('core_config_data', $this->getFlatData(), $printer, $this->_getEngine())->shouldReturnAnInstanceOf($return);
    }
}
