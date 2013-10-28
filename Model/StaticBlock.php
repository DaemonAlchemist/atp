<?php

namespace ATP\Model;

class StaticBlock extends \ATP\ActiveRecord
{
	protected function createDefinition()
	{
		$this->hasData('Identifier', 'Content')
			->hasAdminColumns('Identifier')
			->hasHtmlContent('Content')
			->isDisplayedAsIdentifier()
			->isIdentifiedBy('Identifier')
			->isOrderedBy('identifier ASC');
	}
	
	public static function getContentBlock($identifier)
	{
		$block = new static($identifier);
		return $block->content;
	}
}
StaticBlock::init();