<?php

namespace ATP;

class MapReduce
{
	public static function process($data, $mapper, $reducer, $default = null)
	{
		return array_reduce(
			array_map(
				$mapper,
				$data,
				array_keys($data)
			),
			$reducer,
			$default
		);
	}
	
	public static function __callStatic($name, $arguments)
	{
		$name = ucfirst($name);
		$class = "\\ATP\\MapReduce\\{$name}";
		$obj = new $class();
		return call_user_func_array(array($obj, "process"), $arguments);
	}
}
