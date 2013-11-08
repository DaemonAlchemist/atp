<?php

namespace ATP;

class Module
{
	protected $_moduleName = "";
	protected $_moduleBaseDir = "vendor";

    public function onBootstrap(\Zend\Mvc\MvcEvent $e)
    {
    }

    public function getConfig()
    {
        return include "{$this->_moduleBaseDir}/config/module.config.php";
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    $this->_moduleName => "{$this->_moduleBaseDir}/src/{$this->_moduleName}",
                ),
            ),
        );
    }
}
