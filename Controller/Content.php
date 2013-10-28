<?php

namespace ATP\Controller;

class Content extends \Zend_Controller_Action
{
	private $_useThemes = true;

    public function init()
    {
        $this->_useThemes = \Zend_Registry::get('config')->themes->useThemes;
		$this->view->showTemplates = false;
    }

    public function indexAction()
    {
		//Get the requested path
		$path = substr($this->getRequest()->getPathInfo(), 1);
		
		//Extract the theme
		if($this->_useThemes)
		{
			$parts = explode("/", $path);
			$theme = array_shift($parts);
			$path = implode("/", $parts);
			$this->view->activateTheme($theme);
		}
		
		//Determine the actual file location
		$fullPath = realpath($this->view->getScriptPath($path));
		
		//Get the content type
		$type = "text/plain";
		$render = true;
		$ext = pathinfo($fullPath, \PATHINFO_EXTENSION);
		switch($ext)
		{
			case "css": $type = "text/css"; break;
			case "js": $type = "text/js"; break;
			case "txt": $type = "text/plain"; break;
			default:
				$type = mime_content_type($fullPath);
				$render = false;
				break;
		}		
		
		//Display the file
		header("Content-Type: {$type}");
		echo $render
			? $this->view->render($path)
			: file_get_contents($fullPath);
		die();
    }
	
	public function __call($func, $args)
	{
		$this->indexAction();
	}
}
