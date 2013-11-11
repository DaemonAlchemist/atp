<?php

namespace ATP\ActiveRecord;

class ModelList extends \ArrayObject
{
	private $_type = "";
	
	public function __construct($type)
	{
		$this->_type = $type;
	}

	public function modelType()
	{
		return $this->_type;
	}
	
	public function temp()
	{
		$models = \Zend_Registry::get('config')->models->toArray();
		$className = $models[\ATP\Inflector::camelize($this->_type)]['class'];
		return new $className();
	}
	
	public function adminColumns()
	{
		if(count($this) == 0) return array();
		$obj = current($this);
		return $obj->adminColumns();
	}
	
	public function toArray()
	{
		$newValues = array();
		foreach($this as $key => $value)
		{
			$newValues[$key] = $value;
		}
		
		return $newValues;
	}
	
	public function toJson()
	{
		$json = "{";
		
		$json .= \ATP\MapReduce::process(
			$this->toArray(),
			function($obj){return "\"" . $obj->id . "\": " . $obj->toJson();},
			new \ATP\Reducer\Concatenate(",")
		);
		
		$json .= "}";
		
		return $json;
	}
	
	public function reverse($preserveKeys = false)
	{
		$newValues = array_reverse($this->toArray(), $preserveKeys);
		
		$list = new self($this->_type);
		foreach($newValues as $key => $value)
		{
			$list[$key] = $value;
		}
		
		return $list;
	}
}