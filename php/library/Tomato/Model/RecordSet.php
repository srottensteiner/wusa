<?php
class Tomato_Model_RecordSet implements Countable, Iterator, ArrayAccess 
{
	/**
	 * @var int
	 */
	protected $_count = 0;
	
	private $_iteratorIndex = 0;
	
	/**
	 * @var mixed
	 */
	protected $_results;
	
	public function __construct($results, $convertCallback, $entityClass = null) 
	{
		if (is_array($results)) {
			$this->_results = array();
			foreach ($results as $key => $data) {
				$this->_results[$key] = call_user_func($convertCallback, $data);
			}
		} else {
			$this->_results = $results;
		}
	}
	
	/*
	 * Implement Countable interface
	 */
	
	public function count() 
	{
		if (null == $this->_count) {
			$this->_count = count($this->_results);
		}
		return $this->_count;
	}
	
	/*
	 * Implement Iterator interface
	 */
	
	public function key() 
	{
		return key($this->_results);	
	}
	
	public function next() 
	{
		$this->_iteratorIndex++;
		return next($this->_results);
	}
	
	public function rewind() 
	{
		$this->_iteratorIndex = 0;
		return reset($this->_results);
	}
	
	public function valid() 
	{
		return $this->_iteratorIndex < $this->count();
	}
	
	public function current() 
	{
		$key = key($this->_results);
		$result = $this->_results[$key];
		return $result;
	}
	
	/*
	 * Implement ArrayAccess interface
	 */
	
	public function offsetExists($key) 
	{
		return array_key_exists($key, $this->_results);
	}
	
	public function offsetGet($key, $limit = null) 
	{
		if ($limit !== null) {
			$objects = array();
			
			for ($i = $key; $i <= $limit; $i++) {
				if ($this->offsetExists($i)) {
					$objects[] = $this->_results[$i];
				}
			}
			
			/**
			 * @todo Wäre das hier nicht einfacher?
			 */
			//$objects = array_slice($this->_results, $key, $limit);
			
			return $objects;
		} else {
			$result = $this->offsetExists($key) ? $this->_results[$key] : null;
        	return $result;
		}
    }
    
	public function offsetSet($key, $element) 
	{
        $this->_results[$key] = $element;
        $this->_count = count($this->_results);
    }
    
	public function offsetUnset($key) 
	{
        unset($this->_results[$key]);
        $this->_count = count($this->_results);
    }
}