<?php

namespace ATP\Model;

class Page extends \ATP\ActiveRecord
{
	protected function createDefinition()
	{
		$this->hasData('Title', 'Url', 'Content')
			->hasAdminColumns('Title', 'Url')
			->hasHtmlContent('Content')
			->isDisplayedAsTitle()
			->isIdentifiedBy('Url')
			->isOrderedBy('title ASC');
	}
	
	public static function getPageContent($url)
	{
		$page = new static($url);
		return $page->content;
	}
}
Page::init();