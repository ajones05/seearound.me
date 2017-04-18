<?php
use Respect\Validation\Validator as v;

/**
 * Dev controller class.
 * Handles dev actions.
 */
class DevController extends Zend_Controller_Action
{
	/**
	 * Index action.
	 */
	public function indexAction()
	{
		$this->view->layout()->setLayout('default');
	}

	/**
	 * Login action.
	 */
	public function loginAction()
	{
		$this->view->layout()->setLayout('default');
	}
}
