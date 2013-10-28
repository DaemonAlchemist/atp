<?php

namespace ATP\Flux;

class Controller extends \Zend_Controller_Action
{
	public function jobStatusAction()
	{
		$options = json_decode(urldecode($this->getParam('options')));

		$jobId = $options->jobId;
		
		$results = array();
		foreach(\ATP\Flux::getFinishedTasks($jobId) as $task)
		{
			$data = $task->data;
			$data->taskClass = $task->class;
			$results[] = $task->data;
		}
		echo json_encode($results);
		die();
	}
}
