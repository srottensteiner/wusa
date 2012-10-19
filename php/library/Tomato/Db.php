<?php
class Tomato_Db
{
	const CONNECTION_KEY 		 = 'Tomato_Db_Connection_Key';
	const DEFAULT_CONNECTION_KEY = 'Tomato_Db_Default_Connection_Key';
	const PREFIX_KEY	 		 = 'Tomato_Db_TablePrefix';
	const CONFIG_KEY	 		 = 'Tomato_Db_Config_';
	
	const RETURN_ONLY    = 1;
	const SET_AND_RETURN = 2;
	
	/**
	 * Default table prefix
	 * 
	 * @var const
	 */
	const DEFAULT_PREFIX = 't_';
	
	/**
	 * Array of default connections 
	 * 
	 * @var array
	 */
	private static $_defaults = array();
	
	private static $_dbConnectionKeys = array();
	
	/**
	 * Timeout nachdem eine Information im Ndb-Status ihre Gültigkeit verliert
	 *
	 */
	const CLUSTERINFOTIMEOUT = 60;
	/**
	 * Maximale Anzahl der Verbindungsversuche in den Cluster, danach wird abgebrochen
	 *
	 */
	const CLUSTERMAXTRY = 3;
	/**
	 * Maximale Anzahl der Verbindungsversuche pro node, nachdem bis zum nächsten verbindungsversuch die INFOTIMEOUT gewartet wird
	 *
	 */
	const CLUSTERMAXTRYNODE = 3;
	/**
	 * Maximale Zeit die ein Verbindungsaufbau dauern darf in Millisekunden
	 * @var int
	 */
	const MAXCONNECTIONTIMEOUT = 50;
	
	/**
	 * Gets the database connection
	 * 
	 * @param string $type Can be master or slave
	 * @param string $appName The application name
	 * If it is not passed, the application will use the value of the TOMATO_APP_NAME constant defined in index.php
	 * @return Zend_Db_Adapter_Abstract
	 */
	public static function factory($type = 'slave', $appName = null, $act = self::SET_AND_RETURN)
	{
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
	}
	
	protected static function _connectNode($config,$type,$appName)
	{
	    $connectionType = isset($config['db'][$type]['conectiontype'])?$config['db'][$type]['conectiontype']:'defaultRandom';
	    
	    if(isset($config['db'][$type]['conectiontype'])) unset($config['db'][$type]['conectiontype']);
	    
	    $func = '_connect'.ucfirst($connectionType);
	    
	    if(!method_exists(__CLASS__, $func))
	    {
	        throw new Exception('Unknown Connction Type: '.$connectionType);
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
	 * Wenn eine Node nicht reagiert, wird bei der nächsten Node versucht zu verbinden
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
	        //echo "verbindung nicht möglich <br>\n";
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
	        elseif($ndb_status[$node]["try"] != 0) //nur zurücksetzen wenn nicht eh schon alles passt
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
	 * @return Zend_Db_Adapter_Abstract
	 */
	private static function _connect($config, $type, $serverName)
	{
		if (!isset($config['db'][$type][$serverName])) {
			return null;
		}
		$params = $config['db'][$type][$serverName];
		
		// Set the adapter namespace, so Zend_Db can find the full class of adapter
		$adapter = isset($config['db']['adapter']) ? $config['db']['adapter'] : 'Mysql';
		$params['adapterNamespace'] = isset($config['db']['adapterNamespace']) ? $config['db']['adapterNamespace'] : 'Tomato_Db';
		
		// Add a prefix parameter
		$params['prefix'] = $config['db']['prefix'];
		
		if (Zend_Registry::isRegistered('activateDbProfiling') && Zend_Registry::get('activateDbProfiling') === true) {
			$params['profiler'] = true;
		}
		
		try {
			$db = Zend_Db::factory($adapter, $params);
			$db->setFetchMode(Zend_Db::FETCH_OBJ);
			$db->getConnection();
			return $db;
		} catch (Exception $ex) {
			// FIXME: Log the error to file
			return null;
		}
	}
	
	/**
	 * Gets database settings
	 * 
	 * @param string $appName The application name
	 * @return array
	 */
	public static function getDbConfig($appName = NULL)
	{
		$appName = $appName ? $appName : TOMATO_APP_NAME;
		$key	 = self::CONFIG_KEY . '_' . $appName;
		if (!Zend_Registry::isRegistered($key)) {
			/**
			 * Ãƒâ€žnderungen kurier.at:
			 * - Config-Filename setzt sich nur aus dem Applikationsnamen und ".php" zusammen
			 * - Config-Files liegen in conf/db
			 */
			$file		= $appName . '.php';
			$configFile = '/appl/www/conf/db/' . $file;
			/**
			 * Ende Ãƒâ€žnderungen
			 */
			if (!file_exists($configFile)) {
				throw new Exception('The DB configuration file ' . $file . ' does not exist');
			}
			
			$config = include $configFile;
			if (!is_array($config)) {
				throw new Exception('The configuration file ' . $configFile . ' does not return array');
			}
			Zend_Registry::set($key, $config);
		}
		
		return Zend_Registry::get($key);
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
