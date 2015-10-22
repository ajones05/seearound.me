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

		$mapCenter = $point ? explode(',', $point) :
			($center ? explode(',', $center) : $user->location());

		$posts = (new Application_Model_News)->search(array(
			'keywords' => $keywords,
			'latitude' => $mapCenter[0],
			'longitude' => $mapCenter[1],
			'radius' => $point ? 0.018939 : 0.8,
			'limit' => 15
		), $user);

		$this->view->user = $user;
		$this->view->posts = $posts;
		$this->view->headScript()->appendScript('var mapCenter = ' . json_encode($mapCenter) . ';');
	}
}
