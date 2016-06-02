<?php
/**
 * Pages controller class.
 */
class PageController extends Zend_Controller_Action
{
	/**
	 * Initialize object.
	 *
	 * @return void
	 */
	public function init()
	{
		$this->view->layout()->setLayout('page');
	}

	/**
	 * Render page action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$view = $this->_request->getParam('view');

		if (!$view)
		{
			throw new Exception('Incorrect view name');
		}

		$this->view->bodyClass = [$view];
		$this->render($view);
	}
}
