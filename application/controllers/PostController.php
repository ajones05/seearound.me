<?php
use Respect\Validation\Validator as v;

/**
 * Post controller class.
 * Handles post actions.
 */
class PostController extends Zend_Controller_Action
{
	/**
	 * Post details action.
	 *
	 * @return void
	 */
	public function viewAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();
		$user = $auth ? (new Application_Model_User)->findById($auth['user_id']) : null;

		$id = $this->_request->getParam('id');

		if (!v::intVal()->validate($id))
		{
			throw new RuntimeException('Incorrect ID value: ' .
				var_export($id, true));
		}

		// TODO: load link
		// TODO: load user details
		if (!Application_Model_News::checkId($id, $post, 0))
        {
			throw new RuntimeException('Incorrect post ID: ' .
				var_export($id, true));
        }

		$owner = $post->findDependentRowset('Application_Model_User')->current();

		$headScript = 'var opts=' . json_encode(['latitude' => $post->latitude,
			'longitude' => $post->longitude], JSON_FORCE_OBJECT) . ',' .
			'owner=' . json_encode([
				'image' => $owner->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
			]) . ',' .
			'post=' . json_encode([
				'id'=>$post->id,
				'lat'=>$post->latitude,
				'lng'=>$post->longitude,
				'address'=>$post->Address
			]);

		if ($user)
		{
			$addressModel = new Application_Model_Address;
			$userAddress = $user->findDependentRowset('Application_Model_Address')->current();

			$headScript .= ',user=' . json_encode([
				'name' => $user->Name,
				'address' => $addressModel->format($userAddress->toArray()),
				'image' => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
			]);
		}

		$this->view->user = $user;
		$this->view->post = $post;
		$this->view->owner = $owner;
		$this->view->searchForm = new Application_Form_PostSearch;;
		$this->view->headScript()->appendScript($headScript . ';');
		$this->view->doctype('XHTML1_RDFA');
		$this->view->headMeta()
			->setProperty('og:url', $this->view->serverUrl() . $this->view->baseUrl('post/' . $post->id))
			->setProperty('og:title', 'SeeAround.me')
			->setProperty('og:description', My_StringHelper::stringLimit($post->news, 155, '...'));

		$image = $post->findManyToManyRowset('Application_Model_Image',
			'Application_Model_NewsImage')->current();

		if ($image)
		{
			$thumb = $image->findThumb([320, 320]);
		}
		else
		{
			$link = $post->findDependentRowset('Application_Model_NewsLink')->current();

			if ($link)
			{
				$image = $link->findManyToManyRowset('Application_Model_Image',
					'Application_Model_NewsLinkImage')->current();

				if ($image)
				{
					$thumb = $image->findThumb([448, 320]);
				}
			}

			if (!$image)
			{
				$image = $owner->findManyToManyRowset('Application_Model_Image',
					'Application_Model_UserImage')->current();

				if ($image)
				{
					$thumb = $image->findThumb([320, 320]);
				}
			}
		}

		if (!$image)
		{
			$config = Zend_Registry::get('config_global');
			$image = (new Application_Model_Image)
				->find($config->user->default_image)->current();
			$thumb = $image->findThumb([320, 320]);
		}

		$this->view->headMeta($this->view->serverUrl() .
			$this->view->baseUrl($thumb->path), 'og:image', 'property')
			->setProperty('og:image:width', $thumb->width)
			->setProperty('og:image:height', $thumb->height);

		$this->view->layout()->setLayout('posts');
	}

	/**
	 * Posts list action.
	 *
	 * @return void
	 */
	public function listAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			$this->_helper->flashMessenger('Please log in to access this page.');
			$this->_redirect($this->view->baseUrl('/'));
		}

		$point = $this->_request->getParam('point');

		if (!v::optional(v::equals(1))->validate($point))
		{
			throw new RuntimeException('Incorrect point value: ' .
				var_export($point, true));
		}

		$center = $this->_request->getParam('center');

		if (!v::optional(v::stringType()->latlng())->validate($center))
		{
			throw new RuntimeException('Incorrect center value: ' .
				var_export($center, true));
		}

		$searchForm = new Application_Form_PostSearch;
		$userAddress = $user->findDependentRowset('Application_Model_Address')->current();
		$userData = new Zend_Session_Namespace('data');
		$isValidData = $searchForm->validateSearch((array) $userData->getIterator());

		if ($center)
		{
			$center = explode(',', $center);
		}
		else
		{
			$center = $isValidData ? [$userData->latitude, $userData->longitude] :
				[$userAddress->latitude, $userAddress->longitude];
		}

		$searchParameters = [
			'latitude' => $center[0],
			'longitude' => $center[1],
			'keywords' => $this->_request->getParam('keywords'),
			'filter' => $this->_request->getParam('filter'),
		];

		if ($isValidData && isset($userData->radius))
		{
			$searchParameters['radius'] = $userData->radius;
		}

		if (!$searchForm->validateSearch($searchParameters))
		{
			throw new RuntimeException(
				implode('<br>', $searchForm->getErrorMessages()));
		}

		if ($point)
		{
			$posts = (new Application_Model_News)->search(array_merge(
				$searchParameters, ['limit' => 15, 'radius' => 0.018939]
			), $user);

			if (count($posts))
			{
				$data = [];

				foreach ($posts as $post)
				{
					$data[$post->id] = [
						$post->latitude,
						$post->longitude,
						My_ViewHelper::render('post/_list_item', [
							'post' => $post,
							'owner' => $post->findDependentRowset('Application_Model_User')->current(),
							'user' => $user,
							'limit' => 350
						]
					)];
				}

				$this->view->posts = $posts;
				$this->view->headScript()->appendScript('var postData=' .
					json_encode($data) . ';');
			}

			$searchParameters['point'] = 1;
		}
		else
		{
			$this->_helper->viewRenderer->setNoRender(true);
		}

		$this->view->isList = true;
		$this->view->user = $user;
		$this->view->searchForm = $searchForm;
		$this->view->headScript()->appendScript(
			'var user=' . json_encode([
				'name' => $user->Name,
				'address' => Application_Model_Address::format($userAddress->toArray()),
				'image' => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
				'location' => [$userAddress->latitude, $userAddress->longitude]
			]) . ',' .
			'isList=true' . ',' .
			'opts=' . json_encode($searchParameters, JSON_FORCE_OBJECT) . ';'
		);

		$this->view->layout()->setLayout('posts');
	}

	/**
	 * Posts list action.
	 *
	 * @return void
	 */
	public function loadAction()
	{
		$this->_helper->layout()->disableLayout();

		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('Please log in to access this page.');
			}

			$point = $this->_request->getParam('point');

			if (!v::optional(v::equals(1))->validate($point))
			{
				throw new RuntimeException('Incorrect point value: ' .
					var_export($point, true));
			}

			$new = $this->_request->getPost('new', []);

			if (!v::optional(v::arrayVal())->validate($new))
			{
				throw new RuntimeException('Incorrect new value');
			}

			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'radius' => $this->_request->getPost('radius', 0.8),
				'keywords' => $this->_request->getPost('keywords'),
				'filter' => $this->_request->getPost('filter'),
				'start' => $this->_request->getPost('start', 0)
			];

			if (!$searchForm->validateSearch($searchParameters))
			{
				throw new RuntimeException(
					implode('<br>', $searchForm->getErrorMessages()));
			}

			$result = (new Application_Model_News)->search(array_merge(
				$searchParameters, ['limit' => 15, 'exclude_id' => $new,
					'radius' => $point ? 0.018939 : $searchParameters['radius']]
			), $user);

			$response = ['status' => 1];

			if (count($result))
			{
				$data = [];

				foreach ($result as $post)
				{
					$data[] = [
						$post->id,
						$post->latitude,
						$post->longitude,
						My_ViewHelper::render('post/_list_item', [
							'post' => $post,
							'owner' => $post->findDependentRowset('Application_Model_User')->current(),
							'user' => $user,
							'limit' => 350
						])
					];
				}

				$response['data'] = $data;
			}
			elseif ($searchParameters['start'] == 0)
			{
				$response['empty'] = My_ViewHelper::render('post/_list_empty');
			}

			$userData = new Zend_Session_Namespace('data');
			$userData->radius = $searchParameters['radius'];
			$userData->latitude = $searchParameters['latitude'];
			$userData->longitude = $searchParameters['longitude'];
		}
		catch (Exception $e)
		{
			My_Log::exception($e);

			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			];
		}

		$this->_helper->json($response);
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

			$point = $this->_request->getParam('point');

			if (!v::optional(v::equals(1))->validate($point))
			{
				throw new RuntimeException('Incorrect point value: ' .
					var_export($point, true));
			}

			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'keywords' => $this->_request->getPost('keywords'),
				'filter' => $this->_request->getPost('filter'),
			];

			if (!$searchForm->validateSearch($searchParameters))
			{
				throw new RuntimeException(
					implode("\n", $searchForm->getErrorMessages()));
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
				$result = (new Application_Model_News)->search(array_merge(
					$searchParameters, ['limit' => 1, 'radius' => 0.018939]
				), $user);

				if (!$result->count())
				{
					$this->_helper->viewRenderer->setNoRender(true);
					echo $this->view->partial('post/user-tooltip.html',
						['user' => $user]);
					return true;
				}

				$post = $result[0];
			}

			$query = $model->searchQuery(array_merge(
				$searchParameters, ['radius' => 0.018939]
			), $user);

			$query->join(['r' => new Zend_Db_Expr('(SELECT @rownum := 0)')], '', '');
			$query->from('', ['(@rownum:=@rownum+1) AS _position']);
			$stmt = $model->getAdapter()->query('SELECT n._position FROM ('.
				$query->assemble() . ') n WHERE n.id=' . $post->id, []);

			$currentPosition = $stmt->fetch();

			if (!$currentPosition)
			{
				throw new RuntimeException('Incorrect post position', -1);
			}

			$where = [];

			if ($currentPosition['_position'] > 1)
			{
				$where[] = 'n._position=' . ($currentPosition['_position'] - 1);
			}

			$where[] = 'n._position=' . ($currentPosition['_position'] + 1);

			$stmt = $model->getAdapter()->query('SELECT n.id FROM ('.
				$query->assemble() . ') n WHERE (' . implode(' OR ', $where) . ')', []);

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
			$this->view->point = $point;
			$this->view->owner = $post->findDependentRowset('Application_Model_User')->current();
			$this->view->position = $searchParameters['latitude'] . ',' . $searchParameters['longitude'];
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
			$model = new Application_Model_News;
			$post = $model->save($data);

			$response = [
				'status' => 1,
				'data' => [
					[
						$post->id,
						$post->latitude,
						$post->longitude,
						$this->view->partial('post/_list_item.html', [
							'post' => $post,
							'owner' => $user,
							'user' => $user
						])
					]
				]
			];

			if ($this->_request->getPost('reset', 0))
			{
				$result = $model->search([
					'latitude' => $post->latitude,
					'longitude' => $post->longitude,
					'radius' => 0.8,
					'limit' => 14,
					'exclude_id' => [$post->id]
				], $user);

				foreach ($result as $post)
				{
					$owner = $post->findDependentRowset('Application_Model_User')->current();
					$response['data'][] = [
						$post->id,
						$post->latitude,
						$post->longitude,
						$this->view->partial('post/_list_item.html', [
							'post' => $post,
							'owner' => $post->findDependentRowset('Application_Model_User')->current(),
							'user' => $user,
							'limit' => 350
						])
					];
				}
			}
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			];
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

			$response = [
				'status' => 1,
				'html' => $this->view->partial('post/edit.html',
					['user' => $user, 'post' => $post])
			];
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			];
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

			$response = ['status' => 1];

			if (count($comments))
			{
				foreach ($comments as $comment)
				{
					$response['data'][] = My_ViewHelper::render('post/_comment', [
						'user' => $user,
						'comment' => $comment,
						'post' => $post,
						'limit' => 250
					]);
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
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			];
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

			$response = [
				'status' => 1,
				// TODO: remove spaces, new line...
				'html' => My_ViewHelper::render('post/_comment', [
					'user' => $user,
					'comment' => $comment,
					'post' => $post
				])
			];
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			];
		}

		$this->_helper->json($response);
	}
}
