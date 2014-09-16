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
		$files = array("module", "admin", "routes", "layout", "helpers");
		$config = array();
        foreach($files as $file)
		{
			$fullFile = "{$this->_moduleBaseDir}/../../config/{$file}.config.php";
			if(file_exists($fullFile)) $config = array_merge($config, include($fullFile));
		}
		
		//Load assets from module's public path
		if(!isset($config['asset_manager']))
		{
			$config['asset_manager'] = array(
				'resolver_configs' => array(
					'paths' => array(
						"{$this->_moduleBaseDir}/../../public",
					),
				),
			);
		}

		//Load view from module's view path
		if(!isset($config['view_manager']))
		{
			$config['view_manager'] = array(
				'template_path_stack' => array(
					"{$this->_moduleBaseDir}/../../view",
				)
			);
		}
		
		//echo "<pre>";print_r($config);die();
		return $config;
    }

	public function getInstallerOptions()
	{
		return array();
	}
	
	public function install($options = array())
	{
		//By default, just install database tables
		$this->installDatabaseEntries();
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
}
