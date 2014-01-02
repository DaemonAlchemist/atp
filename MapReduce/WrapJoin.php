<?php

namespace ATP\MapReduce;

class WrapJoin
{
	public function process($data, $sep = ",", $start = "", $end = "")
	{
		if(empty($end)) $end = $start;
		return \ATP\MapReduce::process(
			$data,
			new \ATP\Mapper\Wrapper($start, $end),
			new \ATP\Reducer\Concatenate($sep),
			""
		);
	}	
}
