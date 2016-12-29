<?php
use Respect\Validation\Validator as v;

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
		$this->user = Application_Model_User::getAuth();

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
	 * Index action
	 *
	 * @return void
	 */
    public function indexAction()
    {
		$newsModel = new Application_Model_News;
		$commentsModel = new Application_Model_Comments;
		$loginstatusModel = new Application_Model_Loginstatus;

		$onlineTime = (new DateTime('-10 minutes'))->format(My_Time::SQL);

		$this->view->currentlyLoggedIn = $loginstatusModel->fetchRow(
			$loginstatusModel->select()
				->from($loginstatusModel, 'COUNT(DISTINCT user_id) as count')
				->where('logout_time IS NULL')
				->where('visit_time>="' . $onlineTime . '"')
		);

		$pastDayTime = (new DateTime('-24 hours'))->format(My_Time::SQL);

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

		$pastWeekTime = (new DateTime('-1 week'))->format(My_Time::SQL);

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

	/**
	 * POst likes list action
	 */
	public function postLikesAction()
	{
		$isAjax = $this->getRequest()->isXmlHttpRequest();

		try
		{
			$source = $this->_request->get('source');

			if (!v::optional(v::stringType())->validate($source))
			{
				throw new RuntimeException('Incorrect source value type: ' .
					var_export($source, true));
			}

			$start = $this->_request->get('start');

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value type: ' .
					var_export($source, true));
			}

			$likeModel = new Application_Model_Voting;
			$query = $likeModel->select()->setIntegrityCheck(false)
				->from('votings', 'votings.*')
				->joinLeft(['p' => 'news'], 'p.id=votings.news_id', '')
				->where('p.isdeleted=0')
				->joinLeft(['u' => 'user_data'], 'u.id=votings.user_id',
					['user_name' => 'Name'])
				->group('votings.id');

			switch ($source)
			{
				case 'new':
					$id = $this->_request->get('id');

					if (!v::intVal()->validate($id))
					{
						throw new RuntimeException('Incorrect id value type: ' .
							var_export($id, true));
					}

					$query
						->where('votings.id>?', $id)
						->order('votings.id DESC')
						->limit(100, 0);
					break;
				default:
					$query
						->order('votings.id DESC')
						->limit(100, $start);
			}

			$result = $likeModel->fetchAll($query);

			if ($isAjax)
			{
				$response = ['status' => 1];

				if ($result->count())
				{
					foreach ($result as $like)
					{
						$response['data'][] = My_ViewHelper::render('index/_post-like',
							['like' => $like], 'modules/admin/views/scripts');
					}
				}

				$this->_helper->json($response);
			}
			else
			{
				$this->view->likes = $result;
			}
		}
		catch (Exception $e)
		{
			if (!$isAjax)
			{
				throw $e;
			}

			$this->_helper->json([
				'status' => 0,
				'message' => $e->getMessage()
			]);
		}
	}
}
