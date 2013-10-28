<?php

namespace ATP\Model;

class Parameter extends \ATP\ActiveRecord
{
	protected function createDefinition()
	{
		$this->hasData('Identifier', 'Value')
			->hasAdminColumns('Identifier', 'Value')
			->isDisplayedAsIdentifier()
			->isIdentifiedBy('Identifier')
			->isOrderedBy('identifier ASC');
	}
	
	public static function getParameter($identifier)
	{
		$block = new static($identifier);
		return $block->value;
	}
}
Parameter::init();