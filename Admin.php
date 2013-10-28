<?php

namespace ATP;

class Admin
{
	public static function modelsByCategory()
	{
		$models = \Zend_Registry::get('config')->models->toArray();
		
		$modelsByCat = array();
		foreach($models as $name => $data)
		{
			$cat = $data['category'];
			if(!isset($modelsByCat[$cat])) $modelsByCat[$cat] = array();
			
			$modelsByCat[$cat][] = $name;
		}
		
		return $modelsByCat;
	}
}