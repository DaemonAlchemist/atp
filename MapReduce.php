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
}
