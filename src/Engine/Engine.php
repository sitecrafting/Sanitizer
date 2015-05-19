<?php
namespace Pegasus\Engine;

require_once 'vendor/catfan/medoo/medoo.php';

use Pegasus\Configuration\Config;

/**
 * Created by PhpStorm.
 * User: philipelson
 * Date: 18/05/15
 * Time: 12:43
 */
class Engine extends \medoo implements EngineInterface
{
    const ENGINE_NAME = 'engine';

    private static $engine = null;

    private static $config = null;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $dbInitialisationData = array
        (
            'database_type' => self::ENGINE_NAME,
            'database_name' => $this->config->getDatabase()->getDatabase(),
            'server' => $this->config->getDatabase()->getHost(),
            'username' => $this->config->getDatabase()->getUsername(),
            'password' => $this->config->getDatabase()->getPassword(),
            'charset' => 'utf8'
        );
        parent::__construct($dbInitialisationData);
    }

    /**
     * Start your engines,  method is used to initialise the object
     *
     * @param $config
     * @return mixed
     * @throws EngineNotFoundException
     * @throws \Exception
     */
    public static function start($config)
    {
        if(null == $config)
        {
            throw new \Exception("Creating an Engine instance needs config, sadly no config was provided");
        }
        self::$config = $config;
        $engineName = $config->getDatabase()->getEngine();
        switch($engineName)
        {
            case MySqlEngine::ENGINE_NAME :
            {
                self::$engine = new MySqlEngine($config);
            }
            default :
            {
                throw new EngineNotFoundException("Engine {$engineName} has done a runner!");
                break;
            }
        }
        return self;
    }

    /**
     * Returns a Singleton instance of a database Engine. On the first call the Config
     * can not be null.
     *
     * @return null
     * @throws EngineNotFoundException if no engine is found.
     */
    public static function getInstance()
    {
        return self::$engine;
    }
}