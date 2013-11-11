<?php

namespace ATP\Reducer;

class Concatenate
{
	private $_sep = "";
	
	public function __construct($sep = "")
	{
		$this->_sep = $sep;
	}
	
	public function __invoke($reduced, $item)
	{
		return empty($reduced) ? $item : $reduced . $this->_sep . $item;
	}
}