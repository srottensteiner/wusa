<?php
class Tomato_Db_Mysqli extends Zend_Db_Adapter_Pdo_Mysql {
	
	public function select()
	{
		return new Tomato_Db_Select_Mysql($this);
	}
	
}