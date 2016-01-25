<?php

class ErrorController extends Zend_Controller_Action
{
	/**
	 * Error handle action.
	 *
	 * @return void
	 */
	public function errorAction()
	{
		$this->view->layout()->setLayout('error');

		$errors = $this->_getParam('error_handler');

		if (!$errors || !$errors instanceof ArrayObject)
		{
			return;
		}

		switch ($errors->type)
		{
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				$this->getResponse()->setHttpResponseCode(404);
				$priority = Zend_Log::NOTICE;
				break;
			default:
				$this->getResponse()->setHttpResponseCode(500);
				$priority = Zend_Log::CRIT;
				break;
		}

		$log = $this->getInvokeArg('bootstrap')->getResource('Log');

		if ($log)
		{
			$log->log($errors->exception->getMessage(), $priority);
			$log->log('Request Parameters: ' . var_export($errors->request->getParams(), true), $priority);
			$log->log('Request Parameters: ' . var_export($_SERVER, true), $priority);
		}

		if ($this->getInvokeArg('displayExceptions'))
		{
			$this->view->error = $errors;
		}
		else
		{
			$this->_helper->viewRenderer->setNoRender(true);
		}
    }
}
