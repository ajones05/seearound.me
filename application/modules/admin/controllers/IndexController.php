<?php
/**
 * Admin module index controller class.
 */
class Admin_IndexController extends Zend_Controller_Action
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

		$this->view->layout()->setLayout('page');
    }

	/**
	 * Index action
	 *
	 * @return void
	 */
    public function indexAction()
    {
		$newsModel = new Application_Model_News;
		$commentsModel = new Application_Model_Comments;
		$loginstatusModel = new Application_Model_Loginstatus;

		$onlineTime = (new DateTime('-10 minutes'))->format(DateTime::W3C);

		$this->view->currentlyLoggedIn = $loginstatusModel->fetchRow(
			$loginstatusModel->select()
				->from($loginstatusModel, 'COUNT(DISTINCT user_id) as count')
				->where('logout_time IS NULL')
				->where('visit_time>="' . $onlineTime . '"')
		);

		$pastDayTime = (new DateTime('-24 hours'))->format(DateTime::W3C);

		$this->view->pastDayLoggedIn = $loginstatusModel->fetchRow(
			$loginstatusModel->select()
				->from($loginstatusModel, 'COUNT(DISTINCT user_id) as count')
				->where('login_time>"' . $pastDayTime . '"')
		);

		$this->view->pastDayNews = $newsModel->fetchRow(
			$newsModel->select()
				->from($newsModel, 'COUNT(*) as count')
				->where('created_date>"' . $pastDayTime . '"')
		);

		$this->view->pastDayComments = $commentsModel->fetchRow(
			$commentsModel->select()
				->from($commentsModel, 'COUNT(*) as count')
				->where('created_at>"' . $pastDayTime . '"')
		);

		$pastWeekTime = (new DateTime('-1 week'))->format(DateTime::W3C);

		$this->view->pastWeekLoggedIn = $loginstatusModel->fetchRow(
			$loginstatusModel->select()
				->from($loginstatusModel, 'COUNT(DISTINCT user_id) as count')
				->where('login_time>"' . $pastWeekTime . '"')
		);

		$this->view->pastWeekNews = $newsModel->fetchRow(
			$newsModel->select()
				->from($newsModel, 'COUNT(*) as count')
				->where('created_date>"' . $pastWeekTime . '"')
		);

		$this->view->pastWeekComments = $commentsModel->fetchRow(
			$commentsModel->select()
				->from($commentsModel, 'COUNT(*) as count')
				->where('created_at>"' . $pastWeekTime . '"')
		);
    }
}
