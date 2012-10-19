<?php
abstract class Tomato_Model_Dao
{
	/**
	 * Connection-Typ: Master
	 * @var string
	 */
	const CONN_MASTER = 'Master';
	/**
	 * Connection-Typ: Slave
	 * @var string
	 */
	const CONN_SLAVE  = 'Slave';
	
	/**
	 * @var Tomato_Db_Connection
	 */
	protected $_conn;
	
	/**
	 * Database table prefix
	 * 
	 * @var string
	 * @since 2.0.3
	 */
	protected $_prefix = '';
	
	/**
	 * The language content
	 * @var string
	 * @since 2.0.8
	 */
	protected $_lang;
	
	/**
	 * @since 2.0.3
	 * @return void
	 */
	public function __construct($conn = null)
	{
		$db = Tomato_Db::getDbConfig();
		$this->_prefix = $db['db']['prefix'];
		
		$this->_lang   = Tomato_Config::getConfig()->localization->languages->default;
		if ($conn != null) {
			$this->setDbConnection($conn);
		}
	}
	
	/**
	 * Returns a fully configured Tomato_Model_Dao instance
	 * 
	 * @author guenther.stadler
	 * 
	 * @param string $module
	 * @param string $daoName
	 * @param string $connType Tomato_Model_Dao::CONN_SLAVE or Tomato_Model_Dao::CONN_MASTER
	 * 
	 * @return Tomato_Model_Dao
	 */
	public static function factory($module, $daoName, $connType)
	{
		if ($connType != self::CONN_SLAVE && $connType != self::CONN_MASTER) {
			throw new Exception ('Unknown ConnType: ' . $connType);
		}
		
		Tomato_Db::factory(strtolower($connType));
		$conn = Zend_Registry::get('db');
		
		$dao  = Tomato_Model_Dao_Factory::getInstance()->setModule($module)->{'get' . $daoName . 'Dao'}();
		$dao->setDbConnection($conn);
		return $dao;
	}
	
	/**
	 * @param Tomato_Db_Connection $conn
	 * @return Tomato_Model_Dao
	 */
	public function setDbConnection($conn)
	{
		$this->_conn = $conn;
		return $this;
	}

	/**
	 * @return Tomato_Db_Connection
	 */
	public function getDbConnection()
	{
		return $this->_conn;
	}
	
	/**
	 * @since 2.0.8
	 * @param string $lang
	 * @return Tomato_Model_Dao
	 */
	public function setlang($lang)
	{
		$this->_lang = $lang;
		return $this;
	}
	
	/**
	 * Convert an object or array to entity instance
	 * @param mixed $entity
	 * @return Tomato_Model_Entity
	 */
	abstract function convert($entity);
	
	/* ========== For translation =========================================== */
	
	/**
	 * Get translation items
	 * 
	 * @since 2.0.8
	 * @param Tomato_Model_Entity $item
	 * @return Tomato_Model_RecordSet
	 */
	public function getTranslations($item)
	{
		return null;
	}
}
