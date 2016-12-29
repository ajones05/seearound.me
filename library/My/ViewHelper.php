<?php
/**
 * View helper class.
 */
class My_ViewHelper
{
	/**
	 * Processes a view script and returns the output.
	 *
	 * @param string $name
	 * @param array $vars
	 * @param script $path
	 * @return string
	 */
	public static function render($name, array $vars=[], $path='views/scripts')
	{
		$view = new Zend_View;
		$view->addScriptPath(APPLICATION_PATH . '/' . $path);
		$view->assign($vars);

		if (!preg_match('/\..+$/', $name))
		{
			$name .= '.html';
		}

		return $view->render($name);
	}
}
