<?php

namespace ATP\Model;

class Permission extends \ATP\ActiveRecord
{
	protected function createDefinition()
	{
		$this->hasData(
			'OwnerCreate', 'OwnerDelete', 'OwnerEdit', 'OwnerView',
			'GroupCreate', 'GroupDelete', 'GroupEdit', 'GroupView',
			'PublicCreate', 'PublicDelete', 'PublicEdit', 'PublicView'
		)
			->hasBoolean(
				'OwnerCreate', 'OwnerDelete', 'OwnerEdit', 'OwnerView',
				'GroupCreate', 'GroupDelete', 'GroupEdit', 'GroupView',
				'PublicCreate', 'PublicDelete', 'PublicEdit', 'PublicView'
			)
			->hasAdminColumns('Group', 'Resource')
			->belongsToGroup()
			->belongsToResource();
	}
	
	public function compile()
	{
		return array(
			'owner' => array(
				'create' => $this->ownerCreate,
				'delete' => $this->ownerDelete,
				'edit' => $this->ownerEdit,
				'view' => $this->ownerView,
			),
			'group' => array(
				'create' => $this->groupCreate,
				'delete' => $this->groupDelete,
				'edit' => $this->groupEdit,
				'view' => $this->groupView,
			),
			'public' => array(
				'create' => $this->publicCreate,
				'delete' => $this->publicDelete,
				'edit' => $this->publicEdit,
				'view' => $this->publicView,
			),
		);
	}
}
Permission::init();