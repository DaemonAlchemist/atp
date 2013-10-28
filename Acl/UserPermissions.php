<?php

namespace ATP\Acl;

class UserPermissions
{
	private $_permissions = array();
	private $_groups = array();
	private $_user = null;
	
	private static $_groupTypes = array('owner', 'group', 'public');
	private static $_permissionTypes = array('create', 'delete', 'edit', 'view');
	
	public function __construct($user)
	{
		$this->_user = $user;
		$this->compilePermissions();
	}
	
	public function compilePermissions()
	{
		$this->_groups = $this->_user->groupList;
		
		foreach($this->_groups as $group)
		{
			$permissions = $group->permissionList;
			foreach($permissions as $permission)
			{
				//Get the resource key
				$key = $permission->resource->key();
				
				//Create a permissions array if needed
				if(!isset($this->_permissions[$key]))
				{
					$resourcePermissions = array();
					foreach(static::$_groupTypes as $type)
					{
						$basePermissions = array();
						foreach(static::$_permissionTypes as $pType)
						{
							$basePermissions[$pType] = false;
						}
						$resourcePermissions[$type] = $basePermissions;
					}
					$this->_permissions[$key] = $resourcePermissions;
				}
				
				//Combine permissions
				foreach(static::$_groupTypes as $type)
				{
					foreach(static::$_permissionTypes as $pType)
					{
						$var = $type . ucfirst($pType);
						$this->_permissions[$key][$type][$pType] = 
							$this->_permissions[$key][$type][$pType] ||
							$permission->$var;
					}
				}
			}
		}
	}
	
	public function canCreate($resource)
	{
		return $this->_can('create', $resource);
	}
	
	public function canDelete($resource)
	{
		return $this->_can('delete', $resource);
	}
	
	public function canEdit($resource)
	{
		return $this->_can('edit', $resource);
	}
	
	public function canView($resource)
	{
		return $this->_can('view', $resource);
	}
	
	private function _can($action, $resource)
	{
		$isOwner = $resource->aclOwner->id == $this->_user->id;
		$isInGroup = $user->isInGroup($resource->aclGroup);
	
		return isset($this->_permissions[$resourceName]) && (
			($isOwner && $this->_permissions[$resourceName]['owner'][$action]) ||
			($isInGroup && $this->_permissions[$resourceName]['group'][$action]) ||
			$this->_permissions[$resourceName]['public'][$action]
		);
	}
}
