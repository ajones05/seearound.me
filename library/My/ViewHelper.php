<?php
/**
 * View helper class.
 */
class My_ViewHelper
{
	/**
	 * Processes a view script and returns the output.
	 *
	 * @param	string	$name
	 * @param	array	$vars
	 *
	 * @return	string
	 */
	public static function render($name, array $vars = array())
	{
		$view = new Zend_View;
		$view->addScriptPath(APPLICATION_PATH . '/views/scripts');
		$view->assign($vars);

		return $view->render($name);
	}
}
