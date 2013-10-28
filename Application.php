<?php

namespace ATP;

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

class Application extends \Zend_Application
{
	private static function _initApp()
	{
		$configDirs = array(
			APPLICATION_PATH . '/../library/ATP/configs',
			APPLICATION_PATH . '/configs'
		);
		
		$options = array('config' => array());
		foreach($configDirs as $dirPath)
		{
			$configDir = dir($dirPath);
			
			while(false !== ($file = $configDir->read()))
			{
				if($file == "." || $file == ".." || $file == ".svn") continue;
				$options['config'][$file] = realpath("{$dirPath}/{$file}");
			}
		}

		return new static(APPLICATION_ENV, $options);
	}

	public static function go()
	{
		self::_initApp()->bootstrap()->run();
	}
	
	public static function cron($argv)
	{
		self::_initApp()->bootstrap()->_runCron($argv);
	}
	
	private function _runCron($params)
	{
		try
		{
			$scriptName = array_shift($params);
			
			$jobInfo = array_shift($params);
			$jobParts = explode("::", $jobInfo);
			$jobClass = $jobParts[0];
			$jobFunc = $jobParts[1];
			
			$job = new $jobClass();
			call_user_func_array(array($job, $jobFunc),$params);
		}
		catch(Exception $e)
		{
			//Let someone know when things break
			$config = \POM\Config::getInstance();
		
			$adminEmail = $config->email->admin;
			$techEmail = $config->email->technical;
			$subject = "Mobile Site Analysis Processing Error";
			$message = "There was an error processing the mobile site analysis reports:\n\n" . $e->getMessage();
			
			$headers = "From: {$adminEmail}\r\n" .
				"Reply-To: {$adminEmail}\r\n" .
				"X-Mailer: PHP/" . phpversion();
				
			mail($adminEmail, $subject, $message, $headers);
			mail($techEmail, $subject, $message, $headers);
		}
	}
}
