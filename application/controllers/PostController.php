<?php
use Respect\Validation\Validator as v;

/**
 * Post controller class.
 * Handles post actions.
 */
class PostController extends Zend_Controller_Action
{
	/**
	 * Posts list action.
	 *
	 * @return void
	 */
	public function listAction()
	{
		if ($this->_request->isXmlHttpRequest())
		{
			$this->_helper->layout()->disableLayout();
		}
		else
		{
			$this->view->layout()->setLayout('posts');
		}

		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				$this->_helper->flashMessenger('Please log in to access this page.');
				$this->_redirect($this->view->baseUrl('/'));
			}

			$new = $this->_request->getPost('new', []);

			if (!v::optional(v::arrayVal())->validate($new))
			{
				throw new RuntimeException('Incorrect new value');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::intVal()->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$point = $this->_request->getParam('point');

			if (!v::optional(v::stringType()->latlng())->validate($point))
			{
				throw new RuntimeException('Incorrect point value: ' .
					var_export($point, true));
			}

			$center = $this->_request->getParam('center');

			if (!v::optional(v::arrayType()->latlng())->validate($center))
			{
				throw new RuntimeException('Incorrect center value: ' .
					var_export($center, true));
			}

			$radius = $this->_request->getParam('radius', 0.8);

			if (!v::floatVal()->validate($radius) || $radius < 0.5 || $radius > 1.5)
			{
				throw new RuntimeException('Incorrect radius value: ' .
					var_export($radius, true));
			}

			$keywords = $this->_request->getParam('keywords');

			if (!v::optional(v::stringType())->validate($keywords))
			{
				throw new RuntimeException('Incorrect keywords value: ' .
					var_export($keywords, true));
			}

			$filter = $this->_request->getParam('filter');

			if (!v::optional(v::intVal())->validate($filter))
			{
				throw new RuntimeException('Incorrect filter value: ' .
					var_export($filter, true));
			}

			$search = array();

			if (trim($keywords) !== '')
			{
				$search['keywords'] = $keywords;
			}

			if (trim($filter) !== '')
			{
				$search['filter'] = $filter;
			}

			$searchForm = new Application_Form_PostSearch;

			if (!$searchForm->isValid($search))
			{
				throw new RuntimeException('Validate error');
			}

			$userLocation = $user->location();

			$mapCenter = $point ? explode(',', $point) :
				($center ? $center : $userLocation);

			$posts = (new Application_Model_News)->search(array(
				'keywords' => $keywords,
				'filter' => $filter,
				'latitude' => $mapCenter[0],
				'longitude' => $mapCenter[1],
				'radius' => $point ? 0.018939 : $radius,
				'limit' => 15,
				'start' => $start,
				'exclude_id' => $new
			), $user);

			$postData = array();

			if ($this->_request->isXmlHttpRequest())
			{
				$response = array('status' => 1);

				if (count($posts))
				{
					foreach ($posts as $post)
					{
						$postData[$post->id] = array(
							$post->latitude,
							$post->longitude,
							// TODO: remove spaces, new line...
							My_ViewHelper::render('post/_list_item', array(
								'post' => $post,
								'owner' => $post->findDependentRowset('Application_Model_User')->current(),
								'user' => $user,
								'limit' => 350
							))
						);
					}

					$response['data'] = $postData;
				}
				elseif ($start == 0)
				{
					$response['empty'] = My_ViewHelper::render('post/_list_empty');
				}

				$this->_helper->json($response);
			}

			foreach ($posts as $post)
			{
				$postData[$post->id] = array(
					$post->latitude,
					$post->longitude
				);
			}

			$this->view->user = $user;
			$this->view->posts = $posts;
			$this->view->searchForm = $searchForm;
			$this->view->headScript()->appendScript(
				'var mapCenter=' . json_encode($mapCenter) . ',' .
				'user=' . json_encode(array(
					'name' => $user->Name,
					'address' => $user->address(),
					'image' => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
					'location' => $userLocation
				)) . ',' .
				'search=' . json_encode($search, JSON_FORCE_OBJECT) . ',' .
				'postData=' . json_encode($postData) . ',' .
				'renderRadius=' . json_encode($radius) . ';'
			);
		}
		catch (Exception $e)
		{
			if ($this->_request->isXmlHttpRequest())
			{
				My_Log::exception($e);
				$this->_helper->json(array(
					'status' => 0,
					'message' => $e instanceof RuntimeException ? $e->getMessage() :
						'Internal Server Error'
				));
			}

			throw $e;
		}
	}

	/**
	 * User tooltip action.
	 *
	 * @return void
	 */
	public function userTooltipAction()
	{
		$this->_helper->layout()->disableLayout();

		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$this->view->user = $user;
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$this->_helper->viewRenderer->setNoRender(true);
			echo $e instanceof RuntimeException ? $e->getMessage() :
				'Internal Server Error';
		}
	}

	/**
	 * Posts tooltip action.
	 *
	 * @return void
	 */
	public function tooltipAction()
	{
		$this->_helper->layout()->disableLayout();

		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$lat = $this->_request->getPost('lat');

			if (!v::stringType()->lat()->validate($lat))
			{
				throw new RuntimeException('Incorrect latitude value: ' .
					var_export($lat, true));
			}

			$lng = $this->_request->getPost('lng');

			if (!v::stringType()->lng()->validate($lng))
			{
				throw new RuntimeException('Incorrect longitude value: ' .
					var_export($lng, true));
			}

			$keywords = $this->_request->getPost('keywords');

			if (!v::optional(v::stringType())->validate($keywords))
			{
				throw new RuntimeException('Incorrect keuwords value: ' .
					var_export($keywords, true));
			}

			$filter = $this->_request->getPost('filter');

			if (!v::optional(v::intVal())->validate($filter))
			{
				throw new RuntimeException('Incorrect filter value: ' .
					var_export($filter, true));
			}

			$search = array();

			if (trim($keywords) !== '')
			{
				$search['keywords'] = $keywords;
			}

			if (trim($filter) !== '')
			{
				$search['filter'] = $filter;
			}

			$searchForm = new Application_Form_PostSearch;

			if (!$searchForm->isValid($search))
			{
				throw new RuntimeException('Validate error');
			}

			$model = new Application_Model_News;
			$id = $this->_request->getPost('id');

			if ($id)
			{
				if (!$model->checkId($id, $post, 0))
				{
					throw new RuntimeException('Incorrect post ID: ' .
						var_export($id, true), -1);
				}
			}
			else
			{
				$result = $model->search(array(
					'latitude' => $lat,
					'longitude' => $lng,
					'radius' => 0.018939,
					'keywords' => $keywords,
					'filter' => $filter,
					'limit' => 1
				), $user);

				if (!$result->count())
				{
					$this->_helper->viewRenderer->setNoRender(true);
					echo $this->view->partial('post/user-tooltip.html',
						array('user' => $user));
					return true;
				}

				$post = $result->current();
			}

			$query = $model->searchQuery(array(
				'latitude' => $lat,
				'longitude' => $lng,
				'radius' => 0.018939,
				'keywords' => $keywords,
				'filter' => $filter
			), $user);

			$query->join(array('r' => new Zend_Db_Expr('(SELECT @rownum := 0)')), '', '');
			$query->from('', array('(@rownum:=@rownum+1) AS _position'));
			$stmt = $model->getAdapter()->query('SELECT n._position FROM ('.
				$query->assemble() . ') n WHERE n.id=' . $post->id, array());

			$currentPosition = $stmt->fetch();

			if (!$currentPosition)
			{
				throw new RuntimeException('Incorrect post position', -1);
			}

			$where = array();

			if ($currentPosition['_position'] > 1)
			{
				$where[] = 'n._position=' . ($currentPosition['_position'] - 1);
			}

			$where[] = 'n._position=' . ($currentPosition['_position'] + 1);

			$stmt = $model->getAdapter()->query('SELECT n.id FROM ('.
				$query->assemble() . ') n WHERE (' . implode(' OR ', $where) . ')', array());

			$besidePosts = $stmt->fetchAll();

			if (count($besidePosts))
			{
				if (count($besidePosts) == 2)
				{
					$this->view->prev = $model->findById($besidePosts[0]['id'], 0);
					$this->view->next = $model->findById($besidePosts[1]['id'], 0);
				}
				else
				{
					if ($currentPosition['_position'] > 1)
					{
						$this->view->prev = $model->findById($besidePosts[0]['id'], 0);
					}
					else
					{
						$this->view->next = $model->findById($besidePosts[0]['id'], 0);
					}
				}
			}

			$this->view->post = $post;
			$this->view->owner = $post->findDependentRowset('Application_Model_User')->current();
			$this->view->position = $lat . ',' . $lng;
			$this->view->readmore = $this->_request->getPost('readmore', 0);
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$this->_helper->viewRenderer->setNoRender(true);
			echo $e instanceof RuntimeException ? $e->getMessage() :
				'Internal Server Error';
		}
	}

	/**
	 * Add post action.
	 *
	 * @return void
	 */
	public function newAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$form = new Application_Form_News;

			if (!$form->isValid($this->_request->getParams()))
			{
				throw new RuntimeException('Validate error', -1);
			}

			$data = $form->getValues();
			$data['user_id'] = $user->id;
			$post = (new Application_Model_News)->save($data);

			$response = array(
				'status' => 1,
				'data' => array(
					$post->id => array(
						$post->latitude,
						$post->longitude,
						$this->view->partial('post/_list_item.html', array(
							'post' => $post,
							'owner' => $user,
							'user' => $user
						))
					)
				)
			);

			if ($this->_request->getPost('reset', 0))
			{
				$result = (new Application_Model_News)->search(array(
					'latitude' => $post->latitude,
					'longitude' => $post->longitude,
					'radius' => 0.8,
					'limit' => 14,
					'exclude_id' => array($post->id)
				), $user);

				foreach ($result as $post)
				{
					$owner = $post->findDependentRowset('Application_Model_User')->current();
					$response['data'][$post->id] = array(
						$post->latitude,
						$post->longitude,
						$this->view->partial('post/_list_item.html', array(
							'post' => $post,
							'owner' => $post->findDependentRowset('Application_Model_User')->current(),
							'user' => $user,
							'limit' => 350
						))
					);
				}
			}
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Edit post action.
	 *
	 * @return void
	 */
    public function editAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$post_id = $this->_request->getPost('id');

			if (!(new Application_Model_News)->checkId($post_id, $post, 0))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($post_id, true), -1);
			}

			if ($user->id != $post->user_id)
			{
				throw new RuntimeException('You have not access for this action', -1);
			}

			$response = array(
				'status' => 1,
				'html' => $this->view->partial('post/edit.html',
					array('user' => $user, 'post' => $post))
			);
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Post comments list action.
	 *
	 * @return void
	 */
    public function commentsAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();
			$user = $auth ? (new Application_Model_User)->findById($auth['user_id']) : null;

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, 0))
			{
				throw new RuntimeException('Incorrect post ID');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::intVal()->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$model = new Application_Model_Comments;
			$comments = $model->findAllByNewsId($id, $model->news_limit, $start);

			$response = array('status' => 1);

			if (count($comments))
			{
				foreach ($comments as $comment)
				{
					// TODO: remove spaces, new line...
					$response['data'][] = My_ViewHelper::render('post/_comment', array(
						'user' => $user,
						'comment' => $comment,
						'post' => $post,
						'limit' => 250
					));
				}

				$count = max($post->comment - ($start + $model->news_limit), 0);

				if ($count > 0)
				{
					$response['label'] = $model->viewMoreLabel($count);
				}
			}
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

        $this->_helper->json($response);
	}

	/**
	 * Save comment action.
	 *
	 * @return void
	 */
    public function commentAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$data = $this->_request->getParams();

			if (empty($data['post_id']) || !Application_Model_News::checkId($data['post_id'], $post, 0))
			{
				throw new RuntimeException('Incorrect post ID');
			}

			$form = new Application_Form_Comment;

			if (!$form->isValid($data))
			{
				throw new RuntimeException('Validate error', -1);
			}

			$comment = (new Application_Model_Comments)->save($form, $post, $user);

			$response = array(
				'status' => 1,
				// TODO: remove spaces, new line...
				'html' => My_ViewHelper::render('post/_comment', array(
					'user' => $user,
					'comment' => $comment,
					'post' => $post
				))
			);
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}
}
