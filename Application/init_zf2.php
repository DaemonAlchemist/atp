<?php

include 'vendor/ZF2/library/Zend/Loader/AutoloaderFactory.php';
Zend\Loader\AutoloaderFactory::factory(array(
	'Zend\Loader\StandardAutoloader' => array(
		'autoregister_zf' => true,
		'namespaces' => array(
			'ATP' => 'vendor/ATP',
			'Assetic' => 'vendor/Assetic/src/Assetic',
			'Imagine' => 'vendor/Imagine/lib/Imagine',
		)
	)
));

Zend\Mvc\Application::init(require 'config/application.config.php')->run();
