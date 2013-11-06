<?php

namespace ATP\Application;

class Config
{
	public static function defaultOptions($appOptions, $env)
	{
		return array_merge($appOptions, array(
			'module_listener_options' => array(
				'module_paths' => array(
					'./module',
					'./vendor',
					'./vendor/atp-modules',
				),

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