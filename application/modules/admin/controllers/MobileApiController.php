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
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $this->user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		if ($this->user->is_admin == 'false')
		{
			$this->_redirect($this->view->baseUrl('/'));
		}

		$this->view->layout()->setLayout('bootstrap');
		$this->view->request = $this->_request;
	}

	/**
	 * Index action
	 *
	 * @return void
	 */
    public function indexAction()
    {
    }

	/**
	 * Retrieve message conversation action.
	 *
	 * @return void
	 */
    public function messageConversationAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Retrieve message conversation action.
	 *
	 * @return void
	 */
    public function conversationMessageAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Retrieve message conversation action.
	 *
	 * @return void
	 */
    public function mypostsAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }
}
