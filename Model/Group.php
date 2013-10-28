<?php

namespace ATP\Model;

class Group extends \ATP\ActiveRecord
{
	protected function createDefinition()
	{
		$this->hasData('Name')
			->hasAdminColumns('Name')
			->isDisplayedAsName()
			->isIdentifiedBy('Name')
			->isOrderedBy('name ASC')
			->hasManyUsersViaUserGroups()
			->hasPermissions();
	}
}
Group::init();