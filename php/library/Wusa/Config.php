<?php
namespace Wusa;
/**
 * Holds the Config
 * @author Lukas Plattner
 */
class Config
{
    /**
     * Instance for singleton
     * @var Config
     */
    protected static $instance = null;
    /**
     * Array der Configurationsdaten
     * @var \Zend\Config\Config
     */
    protected $configdata = array();
    /**
     * Returns Instance of Wusa_Config
     * @return Config
     */
    public static function getInstance()
    {
        if(self::$instance === null)
        {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    /**
     * @var \Zend\Log\Logger
     */
    protected $logger = null;

    /**
     * Constructor of Config
     */
    protected function __construct()
    {
        $this->configdata = new \Zend\Config\Config(array());
        $this->loadConfigFile('global.ini');

        foreach($this->configdata as $key => $val)
        {
            echo "$key -> ".var_export($val,true)."<br>\n";
        }
    }

    /**
     * Loads a Configfile into internal Data
     * @param $file
     * @return bool
     */
    public function loadConfigFile($file)
    {
        $filepath = CONFIG_PATH.$file;
        try{
            if(array_key_exists($file,$this->configdata)) return false;
            if(!file_exists($filepath)) return false;
            $reader = new \Zend\Config\Reader\Ini($filepath);
            $this->configdata->merge(new \Zend\Config\Config($reader->fromFile($filepath)));
            return true;
        }
        catch(Exception $e)
        {
            return false;
        }
    }

    /**
     * Returns Configparam
     * @param $param
     * @return mixed
     */
    public function __get($param)
    {
        foreach($this->configdata as $config)
        {
            if($config->$param)
            {
                return $config->$param;
            }
        }
        return false;
    }

    /**
     * Returns the Systemlogger
     * @return \Zend\Log\Logger
     */
    public function getLogger()
    {
        if($this->logger === null)
        {
            //Logger is Defined in the Config
            $loggerclass = '\\'.$this->configdata->system->logger->class;
            echo $loggerclass;
            $r = new \ReflectionClass($loggerclass);
            $writer = $r->newInstanceArgs((array)$this->configdata->system->logger->options->toArray());

            //$writer =  new \Zend\Log\Writer\Stream('log.log');
            $this->logger =  new \Zend\Log\Logger();
            $this->logger->addWriter($writer);
        }
        return $this->logger;
    }
    /**
     * Logs a Message
     * @param $message
     * @param $prio
     */
    public static function doLog($message,$prio)
    {
        self::getInstance()->getLogger()->log($message,$prio);
    }
}
