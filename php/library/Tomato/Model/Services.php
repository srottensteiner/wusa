<?php

abstract class Tomato_Model_Services {
	
	public static function getFoundRows($db = null)
	{
		$db = $db !== null ? $db : Zend_Registry::get('db');
		
		$stmt = $db->select()
                   ->from(null, new Zend_Db_Expr('FOUND_ROWS()'));
                   
		return $db->fetchOne($stmt);
	}
	
	public static function getById($id, $db = null) {
		
		$db = $db !== null ? $db : Zend_Registry::get('db');
		
		$row = $db->select()
				  ->from(static::$_table)
				  ->where(static::$_id_field.' = ?', $id)
				  ->query()
				  ->fetch();
		
		return ($row === false) ? false : static::convert($row);
	}
	
	public static function add($object, $db = null)
	{
		$db = $db !== null ? $db : Zend_Registry::get('db');
		
		$res = $db->insert(
			static::$_table,
			$object->getProperties(true)
		);
		
		if (!$res) {
			return false;
		}
		
		$object->{static::$_id_field} = $db->lastInsertId();
		
		return $object->{static::$_id_field};
		
		/*
		$sql = "INSERT INTO {$this->_prefix}{$this->_table} SET ";
		
		$count = 0;
		foreach($object->getProperties() as $db_field => $value) {
			
			if($db_field != $this->_id_field) {
				if($count > 0) $sql .= ", ";
				$sql .= "{$db_field} = {$db->quote($value)}";
				$count++;
			}
		}		
		
		$result = $db->query($sql);
		
		return ($result === false) ? false : $db->lastInsertId();
		*/
	}
	
	public static function update($object, $db = null)
	{
		$db = $db !== null ? $db : Zend_Registry::get('db');
		
		$id_field = static::$_id_field;
		$properties = $object->getProperties(true);
		$id = $properties[$id_field];
		unset($properties[$id_field]);
		
		return $db->update(
			static::$_table,
			$properties,
			$db->quoteInto($id_field.' = ?', $id)
		);
	}
	
	/**
	 * @deprecated Replace() wird von Zend_Db nicht unterstützt, weil das nicht zum SQL-Standard gehört
	 */
	public static function replace($object, $db = null)
	{
		$db = $db !== null ? $db : Zend_Registry::get('db');
		
		$sql = "REPLACE ".static::$_table." SET ";
		
		$count = 0;
		foreach($object->getProperties() as $db_field => $value) {
			
			if($count > 0) $sql .= " ,";
			$sql .= "{$db_field} = '".mysql_real_escape_string($value, $db->getConnection())."'";
			$count++;
		}
		
		return $db->query($sql);
		
		//echo $sql;
		
		//return mysql_query($sql, $this->getDbConnection());
	}
	/**
	 * Delete something.
	 * $spec might be:
	 * - An integer. In this case, this is the primary key.
	 * - An object. In this case, the primary key is taken from this model object
	 * - An array with the conditions wich rows should be deleted. Please
	 * note that there is not post processing of the conditions. Means - the caller
	 * have to prepare / quote them fully! 
	 * @param mixed $spec
	 * @return Number of deleted rows
	 */
	public static function delete($spec, $db = null)
	{
		if (!$spec) return 0;
		
		$db = $db !== null ? $db : Zend_Registry::get('db');
		$id_field = static::$_id_field;		
		if (is_object($spec)){
			# Model object given
			$condition = $db->quoteInto($id_field.' = ?', $spec->$id_field); 
		} else if (is_numeric($spec)){
			# Primary key given
			$condition = $db->quoteInto($id_field.' = ?', intVal($spec));
		} else if (is_array($spec)){
			# User defined conditions
			$condition = $spec;
		} else {			
			return 0;
		}		
		return $db->delete(
			static::$_table,
			$condition
		);
	}	
}