<?php

namespace ATP\Model;

class Resource extends \ATP\ActiveRecord
{
	public function createDefinition()
	{
		$this->hasData('Type', 'Name')
			->isDisplayedAsName()
			->hasAdminColumns('Type', 'Name')
			->hasPermissions()
			->isOrderedBy('name DESC');
	}
	
	public function key()
	{
		return $this->type . ":" . $this->name;
	}
}
Resource::init();