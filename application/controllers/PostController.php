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
		$this->view->layout()->setLayout('posts');

		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			$this->_helper->flashMessenger('Please log in to access this page.');
			$this->_redirect($this->view->baseUrl('/'));
		}

		$point = $this->_request->getParam('point');

		if (!v::string()->latlng()->addOr(v::nullValue())->validate($point))
		{
			throw new RuntimeException('Incorrect point value: ' .
				var_export($point, true));
		}

		$center = $this->_request->getParam('center');

		if (!v::string()->latlng()->addOr(v::nullValue())->validate($center))
		{
			throw new RuntimeException('Incorrect center value: ' .
				var_export($center, true));
		}

		$keywords = $this->_request->getParam('keywords');

		if (!v::string()->addOr(v::nullValue())->validate($keywords))
		{
			throw new RuntimeException('Incorrect keywords value: ' .
				var_export($keywords, true));
		}

		$userLocation = $user->location();

		$mapCenter = $point ? explode(',', $point) :
			($center ? explode(',', $center) : $userLocation);

		$posts = (new Application_Model_News)->search(array(
			'keywords' => $keywords,
			'latitude' => $mapCenter[0],
			'longitude' => $mapCenter[1],
			'radius' => $point ? 0.018939 : 0.8,
			'limit' => 15
		), $user);

		$this->view->user = $user;
		$this->view->posts = $posts;

		$postData = array(
			0 => array(
				$userLocation[0],
				$userLocation[1],
				'www/images/template/user-location-icon.png'
			)
		);

		foreach ($posts as $post)
		{
			$postData[$post->id] = array(
				$post->latitude,
				$post->longitude
			);
		}

		$this->view->headScript()->appendScript(
			'var mapCenter=' . json_encode($mapCenter) . ',' .
			'userLocation=' . json_encode($userLocation) . ',' .
			'postData=' . json_encode($postData) . ';'
		);
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

			if (!v::string()->lat()->validate($lat))
			{
				throw new RuntimeException('Incorrect latitude value: ' .
					var_export($lat, true));
			}

			$lng = $this->_request->getPost('lng');

			if (!v::string()->lng()->validate($lng))
			{
				throw new RuntimeException('Incorrect longitude value: ' .
					var_export($lng, true));
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
					// TODO: convert to meters
					'radius' => 0.018939,
					// TODO: render, validate filters
					// 'keywords' => $this->_request->getPost('keywords'),
					// 'filter' => strtolower($this->_request->getPost('filter')),
					'limit' => 1
				), $user);

				if (!$result->count())
				{
					return $this->__userLocation($user);
				}

				$post = $result->current();
			}

			$query = $model->searchQuery(array(
				'latitude' => $lat,
				'longitude' => $lng,
				// TODO: convert to meters
				'radius' => 0.018939,
				// TODO: render, validate filters
				// 'keywords' => $this->_request->getPost('keywords'),
				// 'filter' => strtolower($this->_request->getPost('filter'))
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
			$this->_helper->viewRenderer->setNoRender(true);
			echo $e instanceof RuntimeException ? $e->getMessage() :
				'Internal Server Error';
		}
	}

	/**
	 * User location action.
	 *
	 * @return void
	 */
	protected function __userLocation($user)
	{
		$this->_helper->viewRenderer->setNoRender(true);
		echo My_ViewHelper::render('post/_user_locaton.html', array('user' => $user));
	}
}
