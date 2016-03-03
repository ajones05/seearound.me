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

		if (!$this->user->is_admin)
		{
			$this->_redirect($this->view->baseUrl('/'));
		}

		$this->view->layout()->setLayout('bootstrap');
		$this->view->request = $this->_request;
		$this->view->user = $this->user;
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
	 * Login action.
	 *
	 * @return void
	 */
    public function indexapiAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Follow action.
	 *
	 * @return void
	 */
    public function followAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Unfollow action.
	 *
	 * @return void
	 */
    public function unfollowAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Send message action.
	 *
	 * @return void
	 */
    public function sendmessageAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
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
	 * Conversation messages list action.
	 *
	 * @return void
	 */
    public function conversationMessageAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Set conversation read status action.
	 *
	 * @return void
	 */
    public function viewedAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Unread conversation messages list action.
	 *
	 * @return void
	 */
    public function unreadmessagesAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Messages list action.
	 *
	 * @return void
	 */
    public function messagesAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Add new post action.
	 *
	 * @return void
	 */
    public function addimobinewsAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Returns post comments list action.
	 *
	 * @return void
	 */
    public function getTotalCommentsAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Posts list action.
	 *
	 * @return void
	 */
    public function mypostsAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Posts like action.
	 *
	 * @return void
	 */
    public function postLikeAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Posts list action.
	 *
	 * @return void
	 */
    public function requestNearestAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Edit user profile data action.
	 *
	 * @return void
	 */
    public function editProfileAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }

	/**
	 * Retrieve user notifications action.
	 *
	 * @return void
	 */
    public function notificationAction()
    {
		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/mobile-api.js', $this->view));
    }
}
