<?php

namespace ATP\Config;

class File
{
	private $_fileName = "";
	private $_fileContents = "";

	public function __construct($fileName)
	{
		$this->_fileName = $fileName;
		$this->_fileContents = file_get_contents($fileName);
	}
	
	public function apply($options)
	{
		foreach($options as $var => $val)
		{
			$this->$var = $val;
		}
	}
	
	public function __set($var, $val)
	{
		$this->_fileContents = str_replace("#{$var}#", $val, $this->_fileContents);
	}
	
	public function save()
	{
		file_put_contents($this->_fileName, $this->_fileContents);
	}
}
