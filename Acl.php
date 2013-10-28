<?php

namespace ATP;

class Acl
{
	public static function currentUser()
	{
		return \Zend_Registry::get('session')->user;
	}
}
