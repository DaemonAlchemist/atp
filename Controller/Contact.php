<?php

namespace ATP\Controller;

class Contact extends \Zend_Controller_Action
{
	public function indexAction()
	{
		$this->view->message = "";
	
		if(count($_POST) > 0)
		{
			$from = $_POST['email'];
			$fName = $_POST['first_name'];
			$lName = $_POST['last_name'];
			$msg = $_POST['message'];
			
			$to      = \Zend_Registry::get('config')->getParameter("Contact Email");
			$subject = "User filled out contact form ({$fName} {$lName})";
			$headers =
				"From: {$from}" . "\r\n" .
				"Reply-To: {$from}"	. "\r\n" .
				'X-Mailer: PHP/' . phpversion();

			mail($to, $subject, $msg, $headers);
			
			$this->view->message = "{{Contact Submit Message}}";
		}
	}
}