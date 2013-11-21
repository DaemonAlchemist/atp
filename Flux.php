<?php

namespace ATP;

class Flux
{
	private static $_processId = null;

	public static function processId()
	{
		if(is_null(static::$_processId))
		{
			static::$_processId = md5(microtime(true) . ":" . rand());
		}
		
		return static::$_processId;
	}
	
	public static function getTasks()
	{
		$config = \Zend_Registry::get('config')->flux;
		
		\ATP\Flux\Model\Task::reserve(static::processId(), $config->taskCount);

		$obj = new \ATP\Flux\Model\Task();
		return $obj->loadMultiple("status = 'ready' AND process_id = ?", array(static::processId()));
	}
	
	public static function getFinishedTasks($jobId)
	{
		$session = \Zend_Registry::get('session');
		$var = "finishedTasks_{$jobId}";
		if(!isset($session->$var)) $session->$var = array();
	
		$finishedTasks = $session->$var;
	
		$data = array($jobId);
	
		$sql = "status = 'complete' AND job_id = ?";

		if(count($finishedTasks) > 0)
		{
			$placeHolders = array();
			foreach($finishedTasks as $id)
			{
				$placeHolders[] = "?";
				$data[] = $id;
			}
			
			$sql .= " AND id NOT IN (" . implode(", ", $placeHolders) . ")";
		}
	
		$task = new \ATP\Flux\Model\Task();
		$tasks = $task->loadMultiple($sql,$data);
		
		foreach($tasks as $task)
		{
			$finishedTasks[] = $task->id;
		}
		
		$session->$var = $finishedTasks;
		
		return $tasks;
	}
	
	public static function run($timeout = null)
	{
		$config = \Zend_Registry::get('config')->flux;
		
		if(is_null($timeout)) $timeout = $config->timeout;
	
		set_time_limit($timeout);
		
		while(true)
		{
			$tasks = static::getTasks();
			if(count($tasks) == 0)
			{
				sleep($config->sleep);
				set_time_limit($timeout);
			}
			foreach($tasks as $task)
			{
				set_time_limit($timeout);
				$task->run();
			}
		}
	}
}
