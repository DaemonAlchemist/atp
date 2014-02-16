<?php

namespace ATP\ActiveRecord;

class File
{
	public $name = "";
	public $type = "";
	public $size = "";
	
	public function setFrom($data)
	{
		if(is_null($data)) return;
	
		if(!is_array($data)) $data = get_object_vars(json_decode($data));
		
		$this->name = $data['name'];
		$this->type = $data['type'];
		$this->size = $data['size'];
	}
	
	public function toJson()
	{
		return json_encode(array(
			'name' => $this->name,
			'type' => $this->type,
			'size' => $this->size
		));
	}
	
	public function exists()
	{
		return !empty($this->name);
	}
}