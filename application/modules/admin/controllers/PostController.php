<?php
use Respect\Validation\Validator as v;

/**
 * Admin module post controller class.
 */
class Admin_PostController extends Zend_Controller_Action
{
	/**
   * Initialize object
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
	 * Posts list action.
	 */
	public function listAction()
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

			$keywords = $this->_request->get('keywords');

			if (!v::optional(v::stringType())->validate($keywords))
			{
				throw new RuntimeException('Incorrect keywords value type: ' .
					var_export($keywords, true));
			}

			$emptyCategory = $this->_request->get('empty-category');

			if (!v::optional(v::intVal())->validate($emptyCategory))
			{
				throw new RuntimeException('Incorrect empty category value type: ' .
					var_export($filterCategory, true));
			}

			$postModel = new Application_Model_News;
			$query = $postModel->select()->setIntegrityCheck(false)
				->from(['post' => 'news'], ['count' => 'count(post.id)'])
				->where('post.isdeleted=0');

			if ($emptyCategory != null)
			{
				$query->where('post.category_id IS NULL');
			}

			if ($keywords != null)
			{
				$query->where('post.news LIKE ?', '%' . $keywords . '%');
			}

			if (!$isAjax)
			{
				$this->view->resultCount = $postModel->fetchRow($query)->count;
			}

			$query
				->reset('from')
				->reset('columns')
				->from(['post' => 'news'], 'post.*')
				->limit(20, $start)
				->order($postModel->postScore('post') . ' DESC')
				->joinLeft(['user' => 'user_data'], 'user.id=post.user_id', [
					'user_name' => 'Name'
				])
				->joinLeft(['link' => 'news_link'], 'link.news_id=post.id', [
					'link_id' => 'id',
					'link_link' => 'link',
					'link_title' => 'title',
					'link_description' => 'description',
					'link_author' => 'author',
					'link_image_id' => 'image_id',
					'link_image_name' => 'image_name'
				]);

			My_Query::setThumbsQuery($query, [[448,320]], 'post');
			My_Query::setThumbsQuery($query, [[448,320]], 'link');

			$posts = $postModel->fetchAll($query);

			if ($isAjax)
			{
				$response = ['status' => 1];

				if ($posts->count())
				{
					foreach ($posts as $post)
					{
						$response['data'][] = My_ViewHelper::render('post/_list-item',
							['post' => $post], 'modules/admin/views/scripts');
					}
				}

				$this->_helper->json($response);
			}
			else
			{
				$this->view->count = $posts->count();
				$this->view->posts = $posts;
				$this->view->layoutOpts = ['search' => [
					'keywords' => $keywords,
					'action' => 'admin/post/list',
				]];
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
	
	/**
	 * Update post category action.
	 */
	public function updateCategoryAction()
	{
		try
		{
			$id = $this->_request->getParam('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect ID value: ' .
					var_export($id, true));
			}

			$category_id = $this->_request->getParam('category_id');

			if (!v::in(array_keys(Application_Model_News::$categories))
				->validate($category_id))
			{
				throw new RuntimeException('Incorrect category ID value: ' .
					var_export($category_id, true));
			}

			if (!Application_Model_News::checkId($id, $post, ['join' => false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($id, true));
			}

			$post->category_id = $category_id;
			$post->save();

			$response = ['status' => 1];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e->getMessage()
			];
		}

		$this->_helper->json($response);
	}

	/**
	 * Post likes list action.
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

			$post_id = $this->_request->get('post_id');

			if (!v::optional(v::intVal())->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value type: ' .
					var_export($post_id, true));
			}

			$likeModel = new Application_Model_Voting;
			$query = $likeModel->select()->setIntegrityCheck(false)
				->from('votings', 'votings.*')
				->joinLeft(['p' => 'news'], 'p.id=votings.news_id', '')
				->where('p.isdeleted=0')
				->joinLeft(['u' => 'user_data'], 'u.id=votings.user_id',
					['user_name' => 'Name'])
				->group('votings.id')
				->order('votings.id DESC');

			if ($post_id != null)
			{
				$query->where('votings.news_id=?', $post_id);
			}

			switch ($source)
			{
				case 'new':
					$id = $this->_request->get('id');

					if (!v::optional(v::intVal())->validate($id))
					{
						throw new RuntimeException('Incorrect id value type: ' .
							var_export($id, true));
					}

					if ($id != null)
					{
						$query->where('votings.id>?', $id);
					}

					$query->limit(100, 0);
					break;
				default:
					$query->limit(100, $start);
			}

			$result = $likeModel->fetchAll($query);

			if ($isAjax)
			{
				$response = ['status' => 1];

				if ($result->count())
				{
					foreach ($result as $like)
					{
						$response['data'][] = My_ViewHelper::render('post/_post-like',
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
