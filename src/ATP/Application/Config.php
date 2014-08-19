<?php

namespace ATP\Application;

class Config
{
	public static function defaultOptions($appOptions, $env)
	{
		$modulePaths = array(
			'./module',
			'./vendor',
			'./vendor/*/*',
		);
		
		$d1 = dir("./vendor");
		while (false !== ($entry1 = $d1->read()))
		{
			if(is_dir(realpath("vendor/{$entry1}")) && !in_array($entry1, array(".", "..")))
			{
				$modulePaths[] = "./vendor/{$entry1}";	//TODO:  Remove when all modules are composerified
				$d2 = dir("./vendor/{$entry1}");
				while (false !== ($entry2 = $d2->read()))
				{
					if(is_dir(realpath("vendor/{$entry1}/{$entry2}")) && !in_array($entry2, array(".", "..")))
					{
						$modulePaths[] = "./vendor/{$entry1}/{$entry2}";
					}
				}
				$d2->close();
				unset($d2);
			}
		}
		$d1->close();
	
		return array_merge($appOptions, array(
			'module_listener_options' => array(
				'module_paths' => $modulePaths,
				'config_glob_paths' => array(
					"config/autoload/{,*.}{global,{$env},local}.php",
				),

				'config_cache_enabled' => ($env == 'production'),
				'config_cache_key' => 'app_config',
				'module_map_cache_enabled' => ($env == 'production'),
				'module_map_cache_key' => 'module_map',
				'cache_dir' => 'data/config/',
				'check_dependencies' => ($env != 'production')
			),
		));
	}
}