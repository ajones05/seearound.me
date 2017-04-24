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
		if ($this->_request->get('view') == 'about')
		{
			$this->view->layout()->setLayout('default');
			$this->view->headLink()
				->appendStylesheet($this->view->baseUrl('css/default.min.css'));
			$this->view->menuItems = [
				['/', 'Home'],
				['login', 'Desktop Login']
			];
		}
		else
		{
			$this->view->layout()->setLayout('page');
		}
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
