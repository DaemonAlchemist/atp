<?php

namespace ATP;

class Theme
{
	private static $_curTheme = null;

	public static function activate($theme, $view)
	{
		static $themes = null;
		
		if(is_null($themes))
		{
			$themes = \Zend_Registry::get('config')->themes;
		}
			
		if(!isset($themes->$theme)) return;
		
		if(isset($themes->$theme->parent))
		{
			static::activate($themes->$theme->parent, $view);
		}
		
		$view->addScriptPath(static::themeDirectory($theme));
		
		static::$_curTheme = $theme;
	}
	
	public static function currentTheme()
	{
		return static::$_curTheme;
	}
	
	private static function themeDirectory($theme)
	{
		return APPLICATION_PATH . "/themes/{$theme}/views/scripts";
	}	
}
