<?php

namespace ATP;

class Config extends \Zend_Config
{
	public function getParameter($name)
	{
		$className = $this->modelClass('Parameters');
		return $className::getParameter($name);
	}
	
	public function modelClass($model)
	{
		$models = $this->models->toArray();
		return $models[$model]['class'];
	}
}
