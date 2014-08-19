<?php

\Zend\Loader\AutoloaderFactory::factory(array(
	'Zend\Loader\StandardAutoloader' => array(
		'autoregister_zf' => true,
		'namespaces' => array(
			'ATP' => 'vendor/daemonalchemist/ATP',
		)
	)
));

Zend\Mvc\Application::init(require 'config/application.config.php')->run();
