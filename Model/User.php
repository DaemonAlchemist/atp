<?php

namespace ATP\Model;

class User extends \ATP\ActiveRecord
{
	protected static $_passwordSalt = "";
	
	protected $_passwordHash = "";

	protected function createDefinition()
	{
		$this->hasData('Name', 'Email', 'Password')
			->hasAdminColumns('Name', 'Email')
			->isDisplayedAsName()
			->isIdentifiedBy('Email')
			->hasManyGroupsViaUserGroups()
			->isOrderedBy('name ASC');
			
		static::setPasswordSalt(\Zend_Registry::get('config')->passwordSalt);
	}
	
	public static function isLoggedIn()
	{
		return !empty(\Zend_Registry::get('session')->user);
	}
	
	public static function setPasswordSalt($salt)
	{
		static::$_passwordSalt = $salt;
	}
	
	public static function login($email, $password)
	{
		if(empty($email) || empty($password)) return false;
		
		$user = new static($email);
		$permissions = $user->getPermissions();
		if($user->password == md5(static::$_passwordSalt . $password))
		{
			$session = \Zend_Registry::get('session');
			$session->user = $user;
			$session->permissions = $permissions;
			return true;
		}
		
		return false;
	}

	public function getPermissions()
	{
		return new \ATP\Acl\UserPermissions($this);
	}
	
	public function isInGroup($group)
	{
		foreach($this->grouplist as $curGroup)
		{
			if($curGroup->id == $group->id) return true;
		}
		
		return false;
	}
	
	public static function logout()
	{
		unset(\Zend_Registry::get('session')->user);
		unset(\Zend_Registry::get('session')->permissions);
	}
	
	public static function noUsers()
	{
		$obj = new self();
		return count($obj->loadMultiple()) == 0;
	}

	public function postLoadPassword($password)
	{
		$this->_passwordHash = $password;
		return null;
	}
	
	public function filterPassword($password)
	{
		$this->_passwordHash = empty($password)
			? $this->_passwordHash
			: md5(static::$_passwordSalt . $password);
			
		return null;
	}
	
	public function getPassword()
	{
		return $this->_passwordHash;
	}
}
User::init();