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
		$this->view->layout()->setLayout('login');
	}

	/**
	 * Privacy page action.
	 *
	 * @return void
	 */
	public function privacyAction()
	{
	}
}
