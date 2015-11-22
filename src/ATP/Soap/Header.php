<?php

namespace ATP\Soap;

class Header extends \stdClass
{
	public function __construct($namespace, $name)
	{
		$this->ns = $namespace;
		$this->name = $name;
	}
	
	public function __toString()
	{
		$xml = "<{$this->name} xmlns=\"{$this->ns}\"";
		foreach(get_object_vars($this) as $name => $value)
		{
			if(in_array($name, array("name", "ns"))) continue;
			$xml .= " {$name}=\"{$value}\"";
		}
		$xml .= "/>";
		
		return $xml;
	}
}
