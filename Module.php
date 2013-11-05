<?php

namespace ATP;

class Module
{
	protected $_moduleName = "";
	protected $_moduleBaseDir = "vendor";

    public function onBootstrap(\Zend\Mvc\MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new \Zend\Mvc\ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include "{$this->_moduleBaseDir}/{$this->_moduleName}/config/module.config.php";
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    $this->_moduleName => "{$this->_moduleBaseDir}/{$this->_moduleName}/src/{$this->_moduleName}",
                ),
            ),
        );
    }
}
