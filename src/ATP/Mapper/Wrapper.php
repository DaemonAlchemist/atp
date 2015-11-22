<?php

namespace ATP\Mapper;

class Wrapper
{
	private $_start = "";
	private $_end = "";
	
	public function __construct($start = "", $end = "")
	{
		$this->_start = $start;
		$this->_end = is_null($end) ? $start : $end;
	}
	
	public function __invoke($item)
	{
		return $this->_start . $item . $this->_end;
	}
}
