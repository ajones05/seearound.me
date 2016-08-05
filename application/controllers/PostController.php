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
		$userModel = new Application_Model_User;
		$user = Application_Model_User::getAuth(true);

		$id = $this->_request->getParam('id');

		if (!v::intVal()->validate($id))
		{
			throw new RuntimeException('Incorrect ID value: ' .
				var_export($id, true));
		}

		$this->view->layout()->setLayout('posts');
		$this->view->searchForm = new Application_Form_PostSearch;
		$this->view->user = $user;

		$postOptions = [
			'link' => ['thumbs'=>[[448,320]]],
			'deleted' => true,
			'thumbs' => [[448,320],[960,960]]
		];

		if ($user != null)
		{
			$postOptions['user'] = $user;
			$postOptions['userVote'] = true;
		}

		if (!Application_Model_News::checkId($id, $post, $postOptions))
		{
			throw new RuntimeException('Incorrect post ID: ' .
				var_export($id, true));
		}

		if ($post->isdeleted)
		{
			$this->_helper->viewRenderer->setNoRender(true);
			$geolocation = My_Ip::geolocation();
			$this->view->headScript()->appendScript('var opts=' . json_encode([
					'latitude' => $geolocation[0],
					'longitude' => $geolocation[1]
			], JSON_FORCE_OBJECT));

			echo $this->view->partial('post/_item_empty.html');
			return true;
		}

		$ownerThumb = Application_Model_User::getThumb($post, '55x55',
			['alias' => 'owner_']);

		$headScript = 'var opts=' . json_encode(['latitude' => $post->latitude,
			'longitude' => $post->longitude], JSON_FORCE_OBJECT) .
			',owner=' . json_encode([
				'image' => $this->view->baseUrl($ownerThumb)
			]) .
			',post=' . json_encode([
				'id'=>$post->id,
				'lat'=>$post->latitude,
				'lng'=>$post->longitude,
				'address'=>Application_Model_Address::format($post) ?: $post->address
			]);

		if ($user)
		{
			$headScript .= ',user=' . json_encode([
				'name' => $user['Name'],
				'image' => $this->view->baseUrl(
					Application_Model_User::getThumb($user, '55x55'))
			]) .
			',settings=' . json_encode([
				'bodyMaxLength' => Application_Form_News::$bodyMaxLength
			]);
		}

		$this->view->post = $post;
		$this->view->headScript()->appendScript($headScript . ';');
		$this->view->doctype('XHTML1_RDFA');
		$this->view->headMeta()
			->setProperty('og:url', $this->view->serverUrl() . $this->view->baseUrl('post/' . $post->id))
			->setProperty('og:title', 'SeeAround.me')
			->setProperty('og:description', My_StringHelper::stringLimit($post->news, 155, '...'));

		if ($post->image_id != null || $post->link_image_id != null)
		{
			$thumb = $post->image_id ? My_Query::getThumb($post, [448,320], 'news') :
				My_Query::getThumb($post, [448,320], 'link');
			$imagePath = $thumb['path'];
			$imageWidth = $thumb['width'];
			$imageHeight = $thumb['height'];
		}
		else
		{
			$imagePath = 'www/images/logo-social.png';
			$imageWidth = 278;
			$imageHeight = 278;
		}

		$this->view->headMeta($this->view->serverUrl() .
			$this->view->baseUrl($imagePath), 'og:image', 'property')
			->setProperty('og:image:width', $imageWidth)
			->setProperty('og:image:height', $imageHeight);

		$this->view->addClass = ['post'];
	}

	/**
	 * Posts list action.
	 *
	 * @return void
	 */
	public function listAction()
	{
		$user = Application_Model_User::getAuth(true);

		if ($user == null)
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
		$userData = (new Zend_Session_Namespace('userData'))->data;
		$isValidData = $userData ? $searchForm->validateSearch($userData) : false;

		if ($center)
		{
			$center = explode(',', $center);
		}
		else
		{
			$center = $isValidData ? [$userData['latitude'], $userData['longitude']] :
				[$user['latitude'], $user['longitude']];
		}

		$searchParameters = [
			'latitude' => $center[0],
			'longitude' => $center[1],
			'keywords' => $this->_request->getParam('keywords'),
			'filter' => $this->_request->getParam('filter'),
		];

		if ($isValidData && !empty($userData['radius']))
		{
			$searchParameters['radius'] = $userData['radius'];
		}

		if (!$searchForm->validateSearch($searchParameters))
		{
			throw new RuntimeException(
				implode('<br>', $searchForm->getErrorMessages()));
		}

		$posts = (new Application_Model_News)->search(array_merge(
			$searchParameters,
			['limit' => 15, 'radius' => $point ? 0.018939 :
				My_ArrayHelper::getProp($searchParameters, 'radius', 1.5)]
		), $user, [
			'link' => ['thumbs'=>[[448,320]]],
			'userVote' => true,
			'thumbs' => [[448,320],[960,960]]
		]);

		if (count($posts))
		{
			$data = [];

			foreach ($posts as $post)
			{
				$data[$post->id] = [$post->latitude, $post->longitude];
			}

			$this->view->posts = $posts;
			$this->view->headScript()->appendScript('var postData=' .
				json_encode($data) . ';');
		}

		$this->view->isList = true;
		$this->view->user = $user;
		$this->view->searchForm = $searchForm;

		if ($point)
		{
			$searchParameters['point'] = 1;
		}

		$this->view->headScript()->appendScript(
			'var user=' . json_encode([
				'name' => $user['Name'],
				'image' => $this->view->baseUrl(
					Application_Model_User::getThumb($user, '55x55')),
				'location' => [$user['latitude'], $user['longitude']]
			]) .
			',isList=true' .
			',opts=' . json_encode($searchParameters, JSON_FORCE_OBJECT) .
			',timizoneList=' . json_encode(My_CommonUtils::$timezone) .
			',settings=' . json_encode([
				'bodyMaxLength' => Application_Form_News::$bodyMaxLength
			]) . ';'
		);

		$this->view->addClass = ['posts'];
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
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
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
				'radius' => $this->_request->getPost('radius', 1.5),
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
			), $user, [
				'link' => ['thumbs'=>[[448,320]]],
				'userVote' => true,
				'thumbs' => [[448,320],[960,960]]
			]);

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
						$this->view->partial('post/_list_item.html', [
							'post' => $post,
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

			$userData = new Zend_Session_Namespace('userData');
			$userData->data = [
				'radius' => $searchParameters['radius'],
				'latitude' => $searchParameters['latitude'],
				'longitude' => $searchParameters['longitude']
			];
			$userData->setExpirationSeconds(3);
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
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
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
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$readmore = $this->_request->getPost('readmore', 0);

			if (!v::optional(v::intVal()->equals(1))->validate($readmore))
			{
				throw new RuntimeException('Incorrect readmore value: ' .
					var_export($readmore, true));
			}

			$point = $this->_request->getParam('point');

			if (!v::optional(v::intVal()->equals(1))->validate($point))
			{
				throw new RuntimeException('Incorrect point value: ' .
					var_export($point, true));
			}

			$start = $this->_request->getParam('start');

			if (!v::intVal()->min(0)->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
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
			$post = $model->fetchRow($model->searchQuery($searchParameters +
				['radius' => 0.018939], $user)->limit(1, $start));

			if (!$post)
			{
				$this->_helper->viewRenderer->setNoRender(true);
				echo $this->view->partial('post/user-tooltip.html',
					['user' => $user]);
				return true;
			}

			$count = $model->fetchRow($model->searchQuery($searchParameters +
				['radius' => 0.018939], $user, ['count' => true]))->count;

			if ($start)
			{
				$this->view->prev = $start - 1;
			}

			if (++$start < $count)
			{
				$this->view->next = $start;
			}

			$this->view->post = $post;
			$this->view->point = $point;
			$this->view->readmore = $readmore;
			$this->view->position = $searchParameters['latitude'] . ',' .
				$searchParameters['longitude'];
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
			$userModel = new Application_Model_User;
			$user = $userModel->getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$user_id = $this->_request->getPost('user_id');

			if (!v::optional(v::intVal())->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value');
			}

			if (trim($user_id) !== '')
			{
				if (!$user['is_admin'])
				{
					throw new RuntimeException('You are not authorized to access the parameter user_id');
				}

				if (!$userModel->checkId($user_id, $customUser))
				{
					throw new RuntimeException('Incorrect user ID: ' .
						var_export($user_id, true));
				}
			}

			$reset = $this->_request->getPost('reset');

			if (!v::optional(v::intVal()->equals(1))->validate($reset))
			{
				throw new RuntimeException('Incorrect reset value: ' .
					var_export($reset, true), -1);
			}

			$data = $this->_request->getPost();
			$postForm = new Application_Form_News;
			$postForm->setScenario('new');

			if (!$postForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $postForm->getErrorMessages()));
			}

			$addressForm = new Application_Form_Address;

			if (!$addressForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $addressForm->getErrorMessages()));
			}

			$address = (new Application_Model_Address)
				->createRow($addressForm->getValues());
			$address->save();

			$model = new Application_Model_News;
			$postUser = $user_id ? $customUser : $user;
			$post = $model->save($postForm->getValues() +
				['user_id' => $postUser['id'], 'address_id' => $address->id]);
			// TODO: refactoring
			$post = $model->findById($post->id, [
				'link' => ['thumbs'=>[[448,320]]],
				'userVote' => true,
				'thumbs' => [[448,320],[960,960]]
			]);

			$response = [
				'status' => 1,
				'data' => [
					[
						$post->id,
						$address->latitude,
						$address->longitude,
						$this->view->partial('post/_list_item.html', [
							'post' => $post,
							'owner' => $postUser,
							'user' => $user
						])
					]
				]
			];

			if ($reset)
			{
				$result = $model->search([
					'latitude' => $address->latitude,
					'longitude' => $address->longitude,
					'radius' => 1.5,
					'limit' => 14,
					'exclude_id' => [$post->id]
				], $user, [
					'link' => ['thumbs'=>[[448,320]]],
					'userVote' => true,
					'thumbs' => [[448,320],[960,960]]
				]);

				foreach ($result as $post)
				{
					$response['data'][] = [
						$post->id,
						$post->latitude,
						$post->longitude,
						$this->view->partial('post/_list_item.html', [
							'post' => $post,
							'user' => $user,
							'limit' => 350
						])
					];
				}
			}

			(new Application_Model_User)->updateWithCache([
				'post' => $user['post']+1
			], $user);
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
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::canEdit($post, $user))
			{
				throw new RuntimeException('You have not access for this action');
			}

			$response = [
				'status' => 1,
				'latitude' => $post->latitude,
				'longitude' => $post->longitude,
				'body' => $post->news
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
	 * Before save post action.
	 *
	 * @return void
	 */
	public function beforeSaveAction()
	{
		try
		{
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$data = $this->_request->getPost();

			// TODO: change post body field name
			if (isset($data['body']))
			{
				$data['news'] = $data['body'];
			}

			$postForm = new Application_Form_News;
			$postForm->setScenario('before-save');

			if (!$postForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $postForm->getErrorMessages()));
			}

			$linkModel = new Application_Model_NewsLink;
			$linkExist = null;

			if (preg_match_all('/' . My_CommonUtils::$link_regex . '/', $data['body'], $linkMatches))
			{
				foreach ($linkMatches[0] as $link)
				{
					$linkExist = $linkModel->findByLinkTrim($linkModel->trimLink($link));

					if ($linkExist != null)
					{
						break;
					}
				}
			}

			$response = ['status' => 1];

			if ($linkExist != null)
			{
				$response['post_id'] = $linkExist->news_id;
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
	 * Save post action.
	 *
	 * @return void
	 */
	public function saveAction()
	{
		try
		{
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			$model = new Application_Model_News;

			if (!$model->checkId($id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID');
			}

			if (!Application_Model_News::canEdit($post, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$data = $this->_request->getPost();

			$postForm = new Application_Form_News;
			$postForm->setScenario('save');

			if (!$postForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $postForm->getErrorMessages()));
			}

			$post = $model->save(['news' => $data['news']], $post);
			// TODO: refactoring
			$post = $model->findById($post->id, ['link'=>['thumbs'=>[[448,320]]]]);

			$response = [
				'status' => 1,
				'html' => $post->renderContent()
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
	 * Save post location action.
	 *
	 * @return void
	 */
	public function saveLocationAction()
	{
		try
		{
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getParam('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!(new Application_Model_News)->checkId($id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::canEdit($post, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$addressForm = new Application_Form_Address;

			if (!$addressForm->isValid($this->_request->getPost()))
			{
				throw new RuntimeException(
					implode("\n", $addressForm->getErrorMessages()));
			}

			$data = $addressForm->getValues();

			(new Application_Model_Address)
				->update($data, 'id=' . $post['address_id']);

			$response = [
				'status' => 1,
				'address' => Application_Model_Address::format($data)
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
	 * Delete post action.
	 *
	 * @return void
	 */
	public function deleteAction()
	{
		try
		{
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, ['join'=>false]))
			{
				throw new Exception('Incorrect post ID.');
			}

			if (!Application_Model_News::canEdit($post, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			(new Application_Model_News)
				->update(['isdeleted' => 1], 'id=' . $id);

			(new Application_Model_User)->updateWithCache([
				'post' => $user['post']-1
			], $user);

			$response = ['status' => 1];
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
	 * Vote post action.
	 *
	 * @return void
	 */
	public function voteAction()
	{
		try
		{
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($id, true));
			}

			$vote = $this->_request->getPost('vote');

			if (!v::intVal()->oneOf(v::equals(-1),v::equals(1))->validate($vote))
			{
				throw new RuntimeException('Incorrect vote value: ' .
					var_export($vote, true));
			}

			$model = new Application_Model_Voting;

			if (!$model->canVote($user, $post))
			{
				throw new RuntimeException('You cannot vote this post');
			}

			$userVote = $model->findVote($post->id, $user['id']);

			if ($userVote != null)
			{
				$model->cancelVote($userVote);
			}

			$updateVote = null;

			if (!$user['is_admin'] && $userVote)
			{
				$updateVote = $post->vote - $userVote->vote;
			}

			if ($user['is_admin'] || !$userVote || $userVote->vote != $vote)
			{
				$model->insert([
					'vote' => $vote,
					'user_id' => $user['id'],
					'news_id' => $post->id,
					'active' => 1
				]);

				$updateVote = $post->vote + $vote;
				$activeVote = $vote;
			}
			else
			{
				$activeVote = 0;
			}

			if ($updateVote !== null)
			{
				(new Application_Model_News)
					->update(['vote' => $updateVote], 'id=' . $id);

				(new Application_Model_User)->updateWithCache([
					'vote' => $user['vote']+$vote
				], $user);
			}

			$response = [
				'status' => 1,
				'vote' => $updateVote !== null ? $updateVote : $post->vote,
				'active' => $activeVote
			];
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_helper->json($response);
	}

	/**
	 * Read more post action.
	 *
	 * @return void
	 */
	public function readMoreAction()
	{
		try
		{
			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, ['link'=>['thumbs'=>[[448,320]]]]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($id, true));
			}

			$response = [
				'status' => 1,
				'html' => $post->renderContent()
			];
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_helper->json($response);
	}

	/**
	 * Function to share public post through mail.
	 *
	 * @return	void
	 */
	public function shareEmailAction()
	{
		try
		{
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($id, true));
			}

			$email = $this->_request->getPost('email');

			if (!v::email()->validate($email))
			{
				throw new RuntimeException('Incorrect email value: ' .
					var_export($email, true));
			}

			$body = $this->_request->getPost('body');

			if (!v::stringType()->length(1, 65535)->validate($body))
			{
				throw new RuntimeException('Incorrect body value: ' .
					var_export($body, true));
			}

			My_Email::send($email, 'Interesting local news', [
				'template' => 'post-share',
				'assign' => [
					'user' => $user,
					'news' => $post,
					'message' => $body,
				]
			]);

			$response = ['status' => 1];
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
			$user = Application_Model_User::getAuth(true);

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::intVal()->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$limit = 30;
			$model = new Application_Model_Comments;
			$comments = $model->findAllByNewsId($id, [
				'limit' => $limit,
				'start' => $start,
				'owner_thumbs' => [[55,55]]
			]);

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

				$count = max($post->comment - ($start + $limit), 0);

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
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$post_id = $this->_request->getParam('post_id');

			if (!v::intVal()->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($post_id, true));
			}

			if (!Application_Model_News::checkId($post_id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($post_id, true));
			}

			$form = new Application_Form_Comment;

			if (!$form->isValid($this->_request->getPost()))
			{
				throw new RuntimeException('Validate error');
			}

			$data = $form->getValues();

			$comment_id = (new Application_Model_Comments)->insert($data+[
				'user_id' => $user['id'],
				'news_id' => $post_id,
				'created_at' => new Zend_Db_Expr('NOW()'),
				'updated_at' => new Zend_Db_Expr('NOW()')
			]);

			(new Application_Model_News)->update([
				'comment' => $post['comment']+1
			], 'id=' . $post_id);

			$updateUser = ['comment' => $user['comment']+1];

			if ($post['user_id'] != $user['id'])
			{
				$updateUser['comment_other'] = $user['comment_other']+1;
			}

			(new Application_Model_User)
				->updateWithCache($updateUser, $user);

			$response = [
				'status' => 1,
				'html' => My_ViewHelper::render('post/_comment', [
					'user' => $user,
					'comment' => [
						'id' => $comment_id,
						'user_id' => $user['id'],
						'comment' => $data['comment']
					],
					'post' => $post,
					'is_new' => true
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

	/**
	 * Delete comment action.
	 *
	 * @return void
	 */
	public function deleteCommentAction()
	{
		try
		{
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect comment ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_Comments::checkId($id, $comment,
				['post' => ['post_user_id' => 'user_id', 'post_comment' => 'comment']]))
			{
				throw new RuntimeException('Incorrect comment ID: ' .
					var_export($id, true));
			}

			$post = [
				'user_id' => $comment['post_user_id'],
				'comment' => $comment['post_comment']
			];

			if (!Application_Model_Comments::canEdit($comment, $post, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			(new Application_Model_Comments)->update([
				'isdeleted' => 1,
				'updated_at' => new Zend_Db_Expr('NOW()')
			], 'id=' . $comment['id']);

			(new Application_Model_News)->update([
				'comment' => $post['comment']-1
			], 'id=' . $comment['news_id']);

			$updateUser = ['comment' => $user['comment']-1];

			if ($post['user_id'] != $user['id'])
			{
				$updateUser['comment_other'] = $user['comment_other']-1;
			}

			(new Application_Model_User)
				->updateWithCache($updateUser, $user);

			$response = ['status' => 1];
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
	 * Read more comment action.
	 *
	 * @return void
	 */
	public function readMoreCommentAction()
	{
		try
		{
			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect comment ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_Comments::checkId($id, $comment))
			{
				throw new RuntimeException('Incorrect comment ID');
			}

			$response = [
				'status' => 1,
				'html' => Application_Model_Comments::renderContent($comment)
			];
		}
		catch (Exception $e)
		{
			My_Log::exception($e);
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_helper->json($response);
	}

	/**
	 * Post option dialog action.
	 *
	 * @return void
	 */
	public function postOptionsAction()
	{
		try
		{
			$userModel = new Application_Model_User;
			$user = $userModel->getAuth(true);

			if ($user == null || !$user['is_admin'])
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$user_id = $this->_request->getParam('user_id');

			if (!v::optional(v::intVal())->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value');
			}

			if (trim($user_id) !== '')
			{
				if (!$userModel->checkId($user_id, $customUser))
				{
					throw new RuntimeException('Incorrect user ID: ' .
						var_export($user_id, true));
				}

				$this->view->customUser = $customUser;
			}

			$this->view->layout()->setLayout('iframe');
			$this->view->headLink()->appendStylesheet(My_Layout::assetUrl(
				'bower_components/jquery-ui/themes/base/jquery-ui.min.css'));
		}
		catch (Exception $e)
		{
			die($e instanceof RuntimeException ? $e->getMessage() :
				'Internal Server Error');
		}
	}
}
