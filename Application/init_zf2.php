<?php

include 'vendor/ZF2/library/Zend/Loader/AutoloaderFactory.php';
Zend\Loader\AutoloaderFactory::factory(array(
	'Zend\Loader\StandardAutoloader' => array(
		'autoregister_zf' => true,
		'namespaces' => array(
			'ATP' => 'vendor/ATP',
		)
	)
));

Zend\Mvc\Application::init(require 'config/application.config.php')->run();
