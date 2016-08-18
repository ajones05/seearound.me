<?php
/**
 * Admin mobile api controller class.
 */
class Admin_MobileApiController extends Zend_Controller_Action
{
	/**
	 * Initialize object
	 *
	 * @return void
	 */
	public function init()
	{
		$this->user = Application_Model_User::getAuth(true);

		if ($this->user == null)
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

		if (empty($this->user['is_admin']))
		{
			$this->_redirect($this->view->baseUrl('/'));
		}

		$this->view->layout()->setLayout('bootstrap');
		$this->view->request = $this->_request;
		$this->view->user = $this->user;
	}

	/**
	 * Proxy for undefined methods.  Default behavior is to throw an
	 * exception on undefined methods, however this function can be
	 * overridden to implement magic (dynamic) actions, or provide run-time
	 * dispatching.
	 *
	 * @param  string $methodName
	 * @param  array $args
	 * @return void
	 * @throws Zend_Controller_Action_Exception
	 */
	public function __call($methodName, $args)
	{
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js'));
	}

	/**
	 * Index action
	 *
	 * @return void
	 */
	 public function indexAction()
	{
	}
}
