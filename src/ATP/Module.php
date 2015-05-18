<?php

namespace ATP;

class Module
{
	protected $_moduleName = "";
	protected $_moduleBaseDir = "vendor";
	private $_sm = null;

    public function onBootstrap(\Zend\Mvc\MvcEvent $e)
    {
    }

	public function setServiceManager($sm)
	{
		$this->_sm =  $sm;
	}
	
	public function getServiceManager()
	{
		return $this->_sm;
	}
	
    public function getConfig()
    {
		$files = array("module", "admin", "routes", "layout", "helpers", "caching");
		$config = array();
        foreach($files as $file)
		{
			$fullFile = "{$this->_moduleBaseDir}/../../config/{$file}.config.php";
			if(file_exists($fullFile)) $config = array_merge($config, include($fullFile));
		}
		
		//Load assets from module's public path
		if(!isset($config['asset_manager'])) $config['asset_manager'] = array();
		if(!isset($config['asset_manager']['resolver_configs']))
		{
			$config['asset_manager']['resolver_configs'] = array(
				'prioritized_paths' => array(
					array(
						"path"		=> "{$this->_moduleBaseDir}/../../public",
						"priority"	=> isset($config['asset_manager']['priority']) ? $config['asset_manager']['priority'] : 100
					),
				),
			);
		}

		//Load view from module's view path
		if(!isset($config['view_manager'])) $config['view_manager'] = array();
		if(!isset($config['view_manager']['template_path_stack']))
		{
			$config['view_manager']['template_path_stack'] = array(
				"{$this->_moduleBaseDir}/../../view",
			);
		}
		
		return $config;
    }

	public function getInstallerOptions()
	{
		return array();
	}
	
	public function install($options = array())
	{
		//By default, just install database tables and copy files
		$this->installDatabaseEntries();
		$this->installFiles();
	}

	public function installDatabaseEntries()
	{
		$db = \ATP\ActiveRecord::getAdapter();
		foreach($this->getInstallDbQueries() as $sql)
		{
			$db->query($sql, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
		}
	}
	
	
	protected function getInstallDbQueries()
	{
		return array();
	}

	public function installFiles()
	{
		foreach($this->getInstallFiles() as $src => $dest)
		{
			$src = $this->_moduleBaseDir . "/../../" . $src;
			$dest = getcwd() . "/" . $dest;
			$dir = dirname($dest);
			if(!is_dir($dir)) mkdir ($dir, 0777, true);
			copy($src, $dest);
		}
	}
	
	protected function getInstallFiles()
	{
		return array();
	}
}
