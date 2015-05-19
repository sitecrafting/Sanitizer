<?php
/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 18/05/15
 * Time: 20:43
 */
namespace Pegasus\Configuration;

use Pegasus\Resource\Object;
use Pegasus\Configuration;

class Config extends Object
{
    private $database = null;

    private $tables = null;

    public function __construct($configFile)
    {
        if(false == file_exists($configFile))
        {
            throw new ConfigException("Sadly I couldn't validate the config file {$configFile} because it doesn't exist!");
        }
        $parsed = json_decode(file_get_contents($configFile), true);
        $this->setData($parsed);

    }

    public function setDatabaseOverride($data)
    {
        if(false == isset($this->_data['database']))
        {
            $this->_data['database'] = array();
        }

        foreach($data as $configOverride)
        {
            if(2 == sizeof($configOverride))
            {
                $this->_data['database'][strtolower($configOverride[0])] = $configOverride[1];
            }
        }

        /* set it back to null so the next call re-initialises the database config object */
        $this->database = null;
    }

    /**
     * Returns the database config as an object.
     *
     * @return Object
     */
    public function getDatabase()
    {
        if(null == $this->database)
        {
            $this->database = new Object($this->_data['database']);
        }
        return $this->database;
    }

    /**
     * Returns table data
     *
     * @return Object
     */
    public function getTables()
    {
        return $this->_data['tables'];
    }
}