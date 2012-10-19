<?php

/**
 * Übernommen von:
 * http://zend-framework-community.634137.n4.nabble.com/Adding-SELECT-options-to-Zend-Db-Select-td673667.html
 * 
 * @author guenther.stadler
 */
class Tomato_Db_Select_Mysql extends Zend_Db_Select
{
	const SQL_CALC_FOUND_ROWS = 'sqlCalcFoundRows';
	
	const SQL_NO_CACHE = 'sqlNoCache';

	public function __construct(Zend_Db_Adapter_Abstract $adapter)
	{
		/**
		 * Use array_merge() instead of simply setting a key
		 * because the order of keys is significant to the
		 * rendering of the query.
		 */
		self::$_partsInit = array_merge(
			array(self::SQL_NO_CACHE => false),
			array(self::SQL_CALC_FOUND_ROWS => false),
			self::$_partsInit
		);
		parent::__construct($adapter);
	}

	public function sqlCalcFoundRows($flag = true)
	{
		$this->_parts[self::SQL_CALC_FOUND_ROWS] = (bool) $flag;
		return $this;
	}

	protected function _renderSqlCalcFoundRows($sql)
	{
		if ($this->_parts[self::SQL_CALC_FOUND_ROWS]) {
			$sql .= ' SQL_CALC_FOUND_ROWS';
		}
		return $sql;
	}
	
	public function sqlNoCache($flag = true)
	{
		$this->_parts[self::SQL_NO_CACHE] = (bool) $flag;
		return $this;
	}

	protected function _renderSqlNoCache($sql)
	{
		if ($this->_parts[self::SQL_NO_CACHE]) {
			$sql .= ' SQL_NO_CACHE';
		}
		return $sql;
	}	
	
}