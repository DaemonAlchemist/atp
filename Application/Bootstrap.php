<?php

namespace ATP\Application;

class Bootstrap extends \Zend_Application_Bootstrap_Bootstrap
{
	protected function _initConfig()
	{
		//Save the config in a globally available location
		$config = new \ATP\Config($this->getOptions(), true);
		\Zend_Registry::set('config', $config);
	}
	
	protected function _initCache()
	{
		$frontendOptions = array(
			'lifetime' => null,
			'automatic_serialization' => true
		);
		
		$backendOptions = array(
			'cache_dir' => realpath(APPLICATION_PATH . "/../cache")
		);
		
		$cache = \Zend_Cache::factory("Core", "File", $frontendOptions, $backendOptions);
		\Zend_Registry::set('cache', $cache);
	}

	protected function _initView()
	{
		$view = new \ATP\View();
		$view->addScriptPath(\Zend_Registry::get('config')->resources->view->scriptPath);

        $viewRenderer = new \Zend_Controller_Action_Helper_ViewRenderer();
        $viewRenderer->setView($view);
        \Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);

        return $view;
	}
	
	protected function _initDb()
	{
		//Create the db adapter
		$db = new \Zend_Db_Adapter_Pdo_Mysql(\Zend_Registry::get('config')->resources->db->params);
		
		//Save it into the registry
		\Zend_Registry::set('db',$db);
	}
	
	protected function _initSession()
	{
		$key = \Zend_Registry::get('config')->sessionKey;
		\Zend_Registry::set('session', new \Zend_Session_Namespace($key));
	}
}
