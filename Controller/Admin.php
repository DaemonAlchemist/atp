<?php

namespace ATP\Controller;

class Admin extends \Zend_Controller_Action
{
	protected static $_hasUsers = true;

    public function init()
    {
		$models = \Zend_Registry::get('config')->models->toArray();
	
		//Determine the current model
		$this->view->model = $this->getParam('model');
		if(strlen($this->view->model) > 0)
		{
			$this->view->modelId = \ATP\Inflector::singularize(\ATP\Inflector::camelize($this->view->model));
			$this->view->modelFull = $models[\ATP\Inflector::camelize($this->view->model)]['class'];
		}

		//Determine if an admin user is logged in
		$userClass = $models['Users']['class'];	
		if(!$userClass::isLoggedIn() && static::$_hasUsers)
		{
			$this->_forward('login');
		}
	
		$this->view->layout()->setLayout('layout/admin');
	
		$this->view->activateTheme("default");
	
        $this->view->models = $models;

		$this->view->flash = $this->_helper->getHelper('FlashMessenger');
	}
	
    public function indexAction()
    {
        // action body
    }

    public function listAction()
    {
        $modelFull = $this->view->modelFull;
		$obj = new $modelFull();
		$this->view->objects = $obj->loadMultiple();
    }

    public function editAction()
    {
        $modelFull = $this->view->modelFull;

		$object = new $modelFull($this->getParam('id'));
		
		$this->view->message = "";
		$flash = $this->view->flash;
		if(count($_POST) > 0)
		{
			$object = new $modelFull($_POST['id']);
		
			$data = $_POST;
			foreach($_FILES as $name => $fileData)
			{
				$data[$name] = $fileData;
			}

			$object->setFrom($data);

			try {
				$object->save();
				$flash->addMessage(\ATP\Inflector::singularize(\ATP\Inflector::titleize($this->view->model)) . " " . $object->identity() . " saved.");
				$_POST = array();
				$this->_helper->getHelper('Redirector')->gotoSimple('edit', null, null, array('model' => $this->view->model, 'id' => $object->identity()));
			}
			catch(\Exception $e) {
				$flash->addMessage("Error saving " . \ATP\Inflector::singularize(\ATP\Inflector::titleize($this->view->model)) . " " . $object->identity() . ": " . $e->getMessage());
			}
		}
		$this->view->object = $object;
		$this->view->flash = $flash;
    }

	public function deleteAction()
	{
        $modelFull = $this->view->modelFull;

		$object = new $modelFull($this->getParam('id'));
		
		$flash = $this->view->flash;
		try {
			$object->delete();
			$flash->addMessage(\ATP\Inflector::singularize(\ATP\Inflector::titleize($this->view->model)) . " " . $object->identity() . " deleted.");
		}
		catch(\Exception $e) {
				$flash->addMessage("Error deletign " . \ATP\Inflector::singularize(\ATP\Inflector::titleize($this->view->model)) . $object->identity() . ": " . $e->getMessage());
		}
		
		$_POST = array();
		$this->_helper->getHelper('Redirector')->gotoRoute(array('action' => 'list', 'model' => $this->view->model), 'admin');
	}
	
    public function loginAction()
    {
		$models = \Zend_Registry::get('config')->models->toArray();
		$userClass = $models['Users']['class'];	
		
		$this->view->errors = "";
		if($userClass::noUsers())
		{
			static::$_hasUsers = false;
			$this->_forward('edit', null, null, array('model' => 'users'));
		}
        else if(count($_POST) > 0)
		{
			$email = $this->getParam('email');
			$password = $this->getParam('password');
			if($userClass::login($email, $password))
			{
				$this->_forward('index');
			}
			else
			{
				$this->view->errors = "Invalid username or password";
			}
		}
		
		$this->view->layout()->setLayout('layout/admin_blank');
    }
	
	public function logoutAction()
	{
		$models = \Zend_Registry::get('config')->models->toArray();
		$userClass = $models['Users'];	
		$userClass::logout();
		$this->_forward('index');
	}
}
