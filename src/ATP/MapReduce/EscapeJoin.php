<?php

namespace ATP\MapReduce;

class EscapeJoin
{
	public function process($data, $sep = ",", $quoteChar = "'", $otherSubstitutions = array())
	{
		return \ATP\MapReduce::process(
			$data,
			new \ATP\Mapper\Escaper($quoteChar, $otherSubstitutions),
			new \ATP\Reducer\Concatenate($sep),
			""
		);
	}	
}
