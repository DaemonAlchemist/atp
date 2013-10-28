<?php

namespace ATP\Flux\Model;

class Task extends \ATP\ActiveRecord
{
	protected function createDefinition()
	{
		$this->hasData('Title', 'Guid', 'Status', 'ProcessId', 'JobId', 'Class', 'Function', 'Data', 'Description', 'StartedAt', 'FirstTriedAt', 'FinishedAt', 'ExpiresAt')
			->hasAdminColumns('Guid', 'Title', 'Status')
			->hasHtmlContent('Description')
			->isDisplayedAsTitle()
			->isIdentifiedBy('Guid')
			->hasManyTasksAsChildTasksViaRelatedTasks('parent', 'child')
			->hasManyTasksAsParentTasksViaRelatedTasks('child', 'parent')
			->isOrderedBy('status ASC, updated_at DESC');
	}
	
	public function _setup()
	{
		$this->data = new \stdClass();
	}
	
	public static function create($guid, $title, $class, $func, $data, $jobId = null, $reserve = false)
	{
		$task = new static($guid);
		
		if(!$task->id)
		{
			$task->guid = $guid;
			$task->title = $title;
			$task->status = 'ready';
			$task->class = $class;
			$task->function = $func;
			$task->jobId = $jobId;
			$task->aclOwner = new \ATP\Model\User();
			$task->aclGroup = new \ATP\Model\Group();
			
			foreach($data as $key => $value)
			{
				$task->data->$key = $value;
			}
			
			if($reserve)
			{
				$task->processId = \ATP\Flux::processId();
				$task->startedAt = date("Y-m-d H:i:s");
				$task->firstTriedAt = date("Y-m-d H:i:s");
			}
			
			$task->save();
			$task->load($guid);
		}
		
		return $task;
	}
	
	public function join()
	{
		while(!$this->isComplete())
		{
			$this->load($this->guid);
			if(!$this->isComplete()) sleep(2);
		}
	}
	
	public function run($aSync = true)
	{
		if($aSync)
		{
			if($this->isReady())
			{
				$this->_run();
			}
			else
			{
				$this->block();
			}
		
			$this->release();
		}
		else
		{
			while(!$this->isComplete())
			{
				$this->load($this->guid);
				if($this->isReady())
				{
					$this->_run();
				}
				else
				{
					$this->block();
				}
			
				if(!$this->isComplete()) sleep(\Zend_Registry::get("config")->flux->sleep);
			}
		}
	}
	
	private function _run()
	{
		$className = $this->class;
		$func = $this->function;
		$obj = new $className();
		$obj->$func($this);
	}
	
	public function createSubTask($title, $class, $function, $data, $description)
	{
		$task = new self();
		$task->class = $class;
		$task->function = $function;
		$task->data = $data;
		$task->description = $description;
		$task->status = 'ready';
		$task->createGuid();
	}
	
	public static function reserve($processId, $count)
	{
		$sql = "
			UPDATE tasks
				SET process_id = ?,
				started_at=NOW(),
				first_tried_at = IF(first_tried_at is null, NOW(), first_tried_at)
			WHERE status='ready' AND (process_id=\"\" OR process_id is null)
			ORDER BY status DESC, id ASC
			LIMIT {$count}
		";
		$data = array($processId);
		$stmt = \Zend_Registry::get('db')->prepare($sql);
		$stmt->execute($data);
	}
	
	public static function clearExpired()
	{
		$sql = "
			DELETE FROM tasks
			WHERE status='complete' AND expires_at is not null AND expires_at < NOW()
		";
		$stmt = \Zend_Registry::get('db')->prepare($sql);
		$stmt->execute();
	}
	
	public static function unblockStuck()
	{
		$sql = "UPDATE tasks SET status='ready' WHERE status='blocked'";
		$stmt = \Zend_Registry::get('db')->prepare($sql);
		$stmt->execute();
		
		$sql = "UPDATE tasks SET process_id='' WHERE status != 'complete'";
		$stmt = \Zend_Registry::get('db')->prepare($sql);
		$stmt->execute();
	}
	
	public function release()
	{
		$this->process_id = "";
		$this->save();
	}
	
	public function ready()
	{
		$this->status = 'ready';
		$this->release();
	}
	
	public function isReady()
	{
		$tasks = $this->childTaskList;
		foreach($tasks as $task)
		{
			if(!$task->isComplete()) return false;
		}
		return $this->status == 'ready';
	}
	
	public function block()
	{
		$this->status = 'blocked';
		$this->release();
	}
	
	public function isBlocked()
	{
		return $this->status == 'blocked';
	}
	
	public function complete($expiresInSeconds = null)
	{
		$this->status = 'complete';
		$this->finishedAt = date("Y-m-d H:i:s");
		$this->process_id = "";
		if(!is_null($expiresInSeconds))
		{
			$this->expires_at = date("Y-m-d H:i:s", strtotime("+ {$expiresInSeconds} seconds"));
		}
		$this->save();
		
		foreach($this->parentTaskList as $parent)
		{
			$parent->ready();
		}
	}
	
	public function isComplete()
	{
		return $this->status == 'complete';
	}
	
	public static function createGuid($identifier)
	{
		return md5($identifier);
	}
	
	public function dependsOn($task)
	{
		$this->childTaskList[] = $task;
	}
	
	public function postLoadData($data)
	{
		return is_object($data)
			? $data
			: json_decode($data);
	}
	
	public function preSaveData($data)
	{
		return json_encode($data);
	}
}
Task::init();
