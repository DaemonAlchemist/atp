<?php

namespace ATP;

class View extends \Zend_View
{
	private static $_blocks = array();
	private static $_params = array();

	public function init()
	{
		$this->showTemplates = \Zend_Registry::get('config')->showTemplates;
	}
	
	//TODO - Optimize so we don't have to load all static blocks on every request
	public function render($name)
	{
		//Load blocks if not loaded yet
		if(count(static::$_blocks) == 0 && \Zend_Registry::get('config')->useStaticBlocks)
		{
			$className = \Zend_Registry::get('config')->modelClass('StaticBlocks');
			$obj = new $className();
			$blocks = $obj->loadMultiple();
			foreach($blocks as $block)
			{
				static::$_blocks['{{' . $block->identifier . '}}'] = $block->content;
			}
		}
	
		//Load params if not loaded yet
		if(count(static::$_params) == 0)
		{
			$className = \Zend_Registry::get('config')->modelClass('Parameters');
			$obj = new $className();
			$params = $obj->loadMultiple();
			foreach($params as $param)
			{
				static::$_params['##' . $param->identifier . '##'] = $param->value;
			}
		}
	
		$content = parent::render($name);
		
		//Insert static content
		while($this->_replaceContent($content));
		
		//Show templates in source
		if($this->showTemplates)
		{
			$content = "<!-- Begin {$name} -->\n\n{$content}\n\n<!-- End {$name} -->\n\n";
		}
		
		return $content;
	}
	
	//TODO - Optimize
	private function _replaceContent(&$content)
	{
		$altered = false;
		
		//Replace static blocks
		foreach(static::$_blocks as $search => $replace)
		{
			$content = str_replace($search, $replace, $content, $count);
			if($count > 0) $altered = true;
		}
		
		//Replace parameters
		foreach(static::$_params as $search => $replace)
		{
			$content = str_replace($search, $replace, $content, $count);
			if($count > 0) $altered = true;
		}
		
		return $altered;
	}
	
	public function activateTheme($theme)
	{
		\ATP\Theme::activate($theme, $this);
		return $this;
	}
	
	public function baseThemedUrl($url)
	{
		return $this->baseUrl(\ATP\Theme::currentTheme() . $url);
	}
	
	public function js($url, $append = true)
	{
		return $append
			? $this->appendJs($url)
			: $this->prependJs($url);
	}
	
	public function appendJs($url)
	{
		$this->headScript()->appendFile($this->baseThemedUrl($url));
		return $this;
	}
	
	public function prependJs($url)
	{
		$this->headScript()->prependFile($this->baseThemedUrl($url));
		return $this;
	}
	
	public function css($url, $append = true)
	{
		return $append
			? $this->appendCss($url)
			: $this->prependCss($url);
	}
	
	public function appendCss($url)
	{
		$this->headLink()->appendStylesheet($this->baseThemedUrl($url));
		return $this;
	}

	public function prependCss($url)
	{
		$this->headLink()->appendStylesheet($this->baseThemedUrl($url));
		return $this;
	}
}