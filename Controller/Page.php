<?php

namespace ATP\Controller;

class Page extends \Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
		$models = \Zend_Registry::get('config')->models->toArray();
		$className = $models['Pages'];
        $this->view->page = new $className;
		$this->view->page->load($this->getParam('url'));
    }
}
