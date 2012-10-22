<?php
namespace Wusa;
use Zend\Db\Adapter;

class Db
{
	const RETURN_ONLY    = 1;
	const SET_AND_RETURN = 2;
    /**
     * Ordner in denen nach der Config gesucht werden soll
     * @var array
     */
    private static $_confDirs = array();
	/**
	 * Gets the database connection
	 * 
	 * @param string $type Can be master or slave
	 * @param string $config If not passed the default from Config will be used
	 * @return \Zend\Db\Adapter\Adapter
	 */
	public static function factory($type = 'slave', $config = null, $act = self::SET_AND_RETURN)
	{
        if(!$config)
        {
            $config = Config::getInstance()->system->db->connection->default;
        }

        $config = self::getDbConfig($config);
        return Db\Connect::connect($config,$type,'');

        /**
		$appName = $appName ? $appName : TOMATO_APP_NAME;
		$key	 = self::CONNECTION_KEY . '_' . $type . '_' . $appName;
		if (!Zend_Registry::isRegistered($key)) {
			$config  = self::getDbConfig($appName);
			
			$db = self::_connectNode($config, $type, $appName);
			
			Zend_Registry::set($key, $db);
			self::$_dbConnectionKeys[] = $key;
		}
		
		$db = Zend_Registry::get($key);
		
		if ($act == self::SET_AND_RETURN) {
			Zend_Registry::set('db', $db);
		}
		
		return $db;
         */
	}

    /**
     * Returns the Configfile from Configname
     * @param $config
     * @return bool|string
     */
    protected static function getConfigfile($config)
    {
        foreach(self::$_confDirs as $confdir)
        {
            $path = $confdir.DIRECTORY_SEPARATOR.$config.'.php';
            if(file_exists($path))
            {
                return $path;
            }
        }
        return false;
    }

    /**
     * Adds a path to the list of configdirs
     * @param $string
     * @return bool
     */
    public static function addConfdir($string)
    {
        if(is_array($string))
        {
            $return = true;
            foreach($string as $str)
            {
                $return = $return && self::addConfdir($str);
            }
            return $return;

        }
        if(is_dir($string))
        {
            self::$_confDirs[] = $string;
            return true;
        }
        return false;
    }

    /**
     * Resets the List of Configdirs
     */
    public static function resetConfdirs()
    {
        self::$_confDirs = array();
    }
	
	protected static function _connectNode($config,$type,$appName)
	{
	    $connectionType = isset($config['db'][$type]['conectiontype'])?$config['db'][$type]['conectiontype']:'defaultRandom';
	    
	    if(isset($config['db'][$type]['conectiontype'])) unset($config['db'][$type]['conectiontype']);
	    
	    $func = '_connect'.ucfirst($connectionType);
	    
	    if(!method_exists(__CLASS__, $func))
	    {
	        throw new Exception('Unknown Connection Type: '.$connectionType);
	    }
	    
	    return call_user_func_array(array(__CLASS__,$func), array($config,$type,$appName));
	}
	
	protected static function _connectCluster($config,$type,$appName)
	{
	    $servers = $config['db'][$type];
	    if(isset($servers['default'])) unset($servers['default']);
	    
	    $node = array_rand($servers); //Generieren einer Zufallszahl anhan derer ein Verbindungsversuch gemacht wird
	    
	    $cachekey = 'cluster_status_' . str_replace('.', '_', $appName); //generieren eines Uniqe Keys je Cluster
	    
	    $success = false;
	    $ndb_status = apc_fetch($cachekey, $success);
	    
	    if( $success === false) // Key konnte nicht aus dem Cache geholt werden
	    {
	        //Initiales Setzen des ndb_status arrays
	        $ndb_status = array();
	        foreach ($servers as $key => $val)
	        {
	            $ndb_status[$key] = array("try"=>0,"set"=>time());
	        }
	        apc_store($cachekey, $ndb_status);
	    }
	    
	   $db= self::_connectClusterNode($config,$type,$ndb_status,0,$node,$cachekey);
	    
	    if($db === null)
	    {
	        throw new Exception("Connection Error auf mysql_cluster | ".mysql_error());
	    }
	    return $db;
	}
	
	/**
	 * Stellt die Verbindung zu einer Node her
	 * Bricht nach der Maximalen anzahl der Versuche ab
	 * Wenn eine Node nicht reagiert, wird bei der n�chsten Node versucht zu verbinden
	 *
	 * @return Zend_Db_Adapter_Abstract
	 */
	private static function _connectClusterNode(array $config,$type,array $ndb_status, $try,$node,$cachekey)
	{
	    if($try>=self::CLUSTERMAXTRY) //Maximale Anzahl der Verbindungsversuche erreicht
	    {
	        return false;
	    }
	    //echo "Node: $node<br>\n";
	    
	    $servers = $config['db'][$type];
	    if(isset($servers['default'])) unset($servers['default']);
	    
	    if(	$ndb_status[$node]["try"] >= self::CLUSTERMAXTRYNODE  &&
	            $ndb_status[$node]["set"]>(time() - self::CLUSTERINFOTIMEOUT)
	    )
	    {
	        do{
	            $nextNode = array_rand($servers);
	        }while($nextNode == $node);
	        // Mit dieser Node kann nicht verbunden werden weil sie vermutlich nicht reagiert
	        return self::_connectClusterNode($config,$type, $ndb_status, ++$try, $nextNode,$cachekey);
	    }
	    $starttime = microtime();
	    
	    try{
    	    $db = self::_connect($config, $type, $node);
    	    
    	    if($db !== null)
    	    {
    	        if(!$db->getConnection())
    	        {
    	            $db = null;
    	        }
    	    
    	    }
	    }
	    catch(Exception $e)
	    {
	        $db = null;
	    }
	    
	    if($db === null)
	    {
	        //echo "verbindung nicht m�glich <br>\n";
	        $ndb_status[$node]["try"]++;
	        $ndb_status[$node]["set"] = time();
	        apc_store($cachekey, $ndb_status);
	        do{
	            $nextNode = array_rand($servers);
	        }while($nextNode == $node);
	        // Mit dieser Node kann nicht verbunden werden weil sie vermutlich nicht reagiert
	        return self::_connectClusterNode($config,$type, $ndb_status, ++$try, $nextNode,$cachekey);
	    }
	    else
	    {
	        if(microtime() > ($starttime+self::MAXCONNECTIONTIMEOUT))
    	    {
    	        $ndb_status[$node]["try"]++;
    	        $ndb_status[$node]["set"] = time();
	            apc_store($cachekey, $ndb_status);
    	    }
	        elseif($ndb_status[$node]["try"] != 0) //nur zur�cksetzen wenn nicht eh schon alles passt
	        {
	            $ndb_status[$node]["try"] = 0;
	            apc_store($cachekey, $ndb_status);
	        }
	    }
	    return $db;
	}
	
	protected static function _connectDefaultRandom($config,$type,$appName)
	{
	    $servers = $config['db'][$type];
	    // First, try to connect the default server
	    $db = self::_connect($config, $type, $servers['default']);
	    if ($db == null) {
	        // Try to connect to random server
	        $randomServer = $servers;
	        unset($randomServer['default']);
	        $randomServer = array_rand($randomServer);
	         
	        $db = self::_connect($config, $type, $randomServer);
	        if ($db == null) {
	            throw new Exception('Cannot connect to both default and random servers');
	        }
	    }
	    return $db;
	}
	
	public static function getRegisteredDbConnectionKeys()
	{
		return self::$_dbConnectionKeys;
	}
	
	/**
	 * Sets default database connection.
	 * The default connection is defined in the setting file
	 * 
	 * @param string $type Can be "master" or "slave"
	 * @return Zend_Db_Adapter_Abstract
	 */
	public static function setDefault($type = 'slave', $appName = null)
	{
		$appName = $appName ? $appName : TOMATO_APP_NAME;
		$key	 = self::DEFAULT_CONNECTION_KEY . '_' . $type . '_' . $appName;
		
		self::$_defaults[$appName] = $key;
		
		if (!Zend_Registry::isRegistered($key)) {
			$config  = self::getDbConfig($appName);
			$servers = $config['db'][$type];
			
			// First, try to connect the default server
			$db = self::_connect($config, $type, $servers['default']);
			if ($db == null) {
				// Try to connect to random server
				$randomServer = $servers;
				unset($randomServer['default']);
				$randomServer = array_rand($randomServer);
				
				$db = self::_connect($config, $type, $randomServer);
				if ($db == null) {
					throw new Exception('Cannot connect to both default and random servers');	
				}
			}
			
			Zend_Registry::set($key, $db);
			self::$_dbConnectionKeys[] = $key;
		}
		$db = Zend_Registry::get($key);
		Zend_Registry::set('db', $db);
		
		return $db;
	}
	
	/**
	 * @param string $type Can be "master" or "slave"
	 * @param string $appName
	 * @return Zend_Db_Adapter_Abstract
	 */
	public static function resetToDefault($appName = null)
	{
		$appName = $appName ? $appName : TOMATO_APP_NAME;
		if (!isset(self::$_defaults[$appName])) {
			throw new Exception('Not found the default connection registered by the ' . $appName);
		}
		
		$key = self::$_defaults[$appName];
		$db  = Zend_Registry::get($key);
		Zend_Registry::set('db', $db);
		return $db;
	}
	
	/**
	 * Connects to the database server
	 * 
	 * @param array $config Database settings
	 * @param string $type Can be "master" or "slave"
	 * @param string $serverName Name of the server which will be used to define the connection params
	 * @return \Zend\Db\Adapter\Adapter
	 */
	protected static function _connect($config, $type, $serverName)
	{
		if (!isset($config->$type->$serverName)) {
			return null;
		}
		$params = $config->$type->$serverName;
		
		// Set the adapter namespace, so Zend_Db can find the full class of adapter
		$params['driver'] = $config->get('driver',Config::getInstance()->db->driver);
        $params['adapterNamespace'] = $config->get('adapterNamespace','Zend\\Db\\Adapter');

        // Add a prefix parameter
		$params['prefix'] = $config->get('prefix',Config::getInstance()->db->prefix);

        /*
		if (Zend_Registry::isRegistered('activateDbProfiling') && Zend_Registry::get('activateDbProfiling') === true) {
			$params['profiler'] = true;
		}*/
		
		try {
			$db = new \Zend\Db\Adapter\Adapter($params);
			//$db->setFetchMode(\Zend\Db::FETCH_OBJ);
			//$db->getConnection();
			return $db;
		} catch (Exception $ex) {
            Config::doLog(__METHOD__.': Exception '.$ex->getMessage(),\Zend\Log\Logger::ERR);
			return null;
		}
	}
	
	/**
	 * Gets database settings
	 * 
	 * @param string $appName The application name
	 * @return array
	 */
	public static function getDbConfig($config = NULL)
	{
		//$key	 = self::CONFIG_KEY . '_' . $config;
		//if (!\Zend\Registry::isRegistered($key)) {

            $configfile = self::getConfigfile($config);

            if(!$configfile){
                Config::doLog(__METHOD__.': Configfile for '.$config.' could not be found', \Zend\Log\Logger::ERR);
                throw new Exception('Configfile for '.$config.' could not be found');
            }
            echo 'use '.$configfile;

			$config = include $configfile;
			if (!is_array($config)) {
				throw new Exception('The configuration file ' . $configfile . ' does not return array');
			}
            return new \Zend\Config\Config($config);
			//\Zend\Registry::set($key, $config);
		//}
		
		//return \Zend\Registry::get($key);
	}
	
	/**
	 * Writes database settings to file
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function writeDbConfig($config)
	{
		$configFile = TOMATO_APP_DIR . DS . 'config' . DS . 'db.' . TOMATO_APP_NAME . '.' . strtolower(TOMATO_ENV) . '.php';
		
		$writer = new Tomato_Config_Writer_Php();
		$config = is_array($config) ? new Zend_Config($config) : $config;
		$writer->write($configFile, $config);
	}
}
