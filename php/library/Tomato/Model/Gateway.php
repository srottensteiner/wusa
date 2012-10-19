<?php
abstract class Tomato_Model_Gateway 
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $_conn;
	
	/**
	 * Database table prefix
	 * 
	 * @var string
	 * @since 2.0.3
	 */
	protected $_prefix;
	
	/**
	 * @since 2.0.3
	 * @return void
	 */
	public function __construct($conn = null)
	{
		$db = Tomato_Db::getDbConfig();
		$this->_prefix = $db['db']['prefix'];
		
		if ($conn != null) {
			$this->setDbConnection($conn);
		}
	}
	
	/**
	 * @param Zend_Db_Adapter_Abstract $conn
	 */
	public function setDbConnection($conn) 
	{
		$this->_conn = $conn;
	}

	/**
	 * @return Zend_Db_Adapter_Abstract
	 */
	public function getDbConnection()
	{
		return $this->_conn;
	}
	
	/**
	 * Convert an object or array to entity instance
	 * @param mixed $entity
	 * @return Tomato_Model_Entity
	 */
	abstract function convert($entity);
}
