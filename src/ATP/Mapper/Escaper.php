<?php

namespace ATP\Mapper;

class Escaper
{
	private $_quoteChar = "'";
	private $_otherSubstitutions = array();
	
	public function __construct($quoteChar = "'", $otherSubstitutions = array())
	{
		$this->_quoteChar = $quoteChar;
		$this->_otherSubstitutions = $otherSubstitutions;
	}
	
	public function __invoke($item)
	{
		$item = str_replace($this->_quoteChar, "\\" . $this->_quoteChar, $item);
		$item = str_replace(array_keys($this->_otherSubstitutions), $this->_otherSubstitutions, $item);
		return $this->_quoteChar . $item . $this->_quoteChar;
	}
}
