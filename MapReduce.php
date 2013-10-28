<?php

namespace ATP;

class MapReduce
{
	private $_mapper = null;
	private $_reducer = null;
	private $_results = null;
	
	public static function get()
	{
		return new self();
	}
	
	public function map($mapper)
	{
		$this->_mapper = $mapper;
		return $this;
	}

	public function reduce($reducer)
	{
		$this->_reducer = $reducer;
		return $this;
	}
	
	public function process($data)
	{
		//Map
		$mappedData = array();
		foreach($data as $index => $item)
		{
			$map = $this->_mapper;
			$mappedData[] = $map($item, $index);
		}
		
		//Reduce
		$reducedData = null;
		foreach($mappedData as $item)
		{
			$reduce = $this->_reducer;
			$reducedData = $reduce($reducedData, $item);
		}
		
		return $reducedData;
	}
}
