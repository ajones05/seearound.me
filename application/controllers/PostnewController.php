<?php
use Respect\Validation\Validator as v;

/**
 * Post controller class.
 * Handles post actions.
 */
class PostnewController extends Zend_Controller_Action
{
	/**
	 * Post details action.
	 *
	 * @return void
	 */
	public function viewAction()
	{
		$userModel = new Application_Model_User;
		$user = Application_Model_User::getAuth();

		$id = $this->_request->getParam('id');

		if (!v::intVal()->validate($id))
		{
			throw new RuntimeException('Incorrect ID value: ' .
				var_export($id, true));
		}

		$this->view->layout()->setLayout('map');
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
			$postOptions['userBlock'] = true;
		}

		if (!Application_Model_News::checkId($id, $post, $postOptions))
		{
			throw new RuntimeException('Incorrect post ID: ' .
				var_export($id, true));
		}

		$this->view->appendScript = [];

		if ($user != null)
		{
			$this->view->appendScript[] = 'user=' . json_encode([
				'name' => $user['Name'],
				'image' => $this->view->baseUrl(
					Application_Model_User::getThumb($user, '55x55')),
				'is_admin' => $user['is_admin']
			]);
		}

		if ($post->isdeleted || $post->vote <= -4)
		{
			$this->_helper->viewRenderer->setNoRender(true);
			$geolocation = My_Ip::geolocation();
			$this->view->appendScript[] = 'opts=' . json_encode([
				'lat' => $geolocation[0],
				'lng' => $geolocation[1]]
			);
			$this->view->viewPage = 'post-offline';
			echo $this->view->partial('post/_item_empty.html');
			return true;
		}

		$ownerThumb = Application_Model_User::getThumb($post, '55x55',
			['alias' => 'owner_']);

		$this->view->appendScript[] = 'opts=' . json_encode([
			'lat' => $post->latitude,
			'lng' => $post->longitude
		]);

		$this->view->appendScript[] = 'post=' . json_encode([
			'id' => $post->id,
			'address' => Application_Model_Address::format($post),
			'owner' => ['image' => $this->view->baseUrl($ownerThumb)],
		]);

		if ($user != null)
		{
			$this->view->appendScript[] = 'settings=' . json_encode([
				'bodyMaxLength' => Application_Form_Post::$bodyMaxLength
			]);
		}

		if (!empty($post['link_id']))
		{
			$this->view->link = [
				'id' => $post['link_id'],
				'link' => $post['link_link'],
				'title' => $post['link_title'],
				'description' => $post['link_description'],
				'author' => $post['link_author'],
				'image_id' => $post['link_image_id'],
				'image_name' => $post['link_image_name']
			];
		}

		$this->view->post = $post;
		$this->view->owner = [
			'Name' => $post['owner_name'],
			'image_name' => $post['owner_image_name']
		];
		$this->view->hidden = true;

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

		$this->view->viewPage = 'post';
	}
}
