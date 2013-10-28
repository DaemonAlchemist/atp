<?php

namespace ATP;

abstract class Resource implements ResourceInterface
{
	private $_parent = null;
	private $_id = null;
	
	private $_responseCode = "200 OK";
	private $_headers = array();

	public function __construct($parent)
	{
		$this->_parent = $parent;
	}
	
	public function getParent()
	{
		return $this->_parent;
	}
	
	public function getResponseCode()
	{
		return $this->_responseCode;
	}
	
	protected function setResponseCode($code)
	{
		$this->_responseCode = $code;
	}
	
	protected function addHeader($header)
	{
		$this->_headers[] = $header;
	}
	
	public function getHeaders()
	{
		return $this->_headers;
	}
	
	public function process($verb, $data)
	{
		switch($verb)
		{
			case 'GET':		return $this->get($data);	break;
			case 'PUT':		return $this->put($data);	break;
			case 'POST':	return $this->post($data);	break;
			case 'DELETE':	return $this->delete();		break;
		}
	}
	
	public static function getResource($path)
	{
		if(empty($path)) return null;
		$className = "Flow\\Resource";
		
		$resource = null;
		foreach($path as $name => $id)
		{
			$className .= "\\" . str_replace(" ", "", ucwords(str_replace("_", " ", strtolower($name))));
			if(!empty($id)) $className .= "\\Item";
			$className = str_replace("Item\\", "", $className);
			$newResource = new $className($resource);
			$newResource->setId($id);
			$resource = $newResource;
		}
		
		return $resource;
	}
	
	public function setId($id)
	{
		$this->_id = $id;
	}
	
	public function getId()
	{
		return $this->_id;
	}
	
	public function requestInfo()
	{
		return "{result: \"Failure\"}";
	}
}