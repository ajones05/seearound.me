<?php
/**
 * Email helper class.
 */
class My_Email
{
	/**
	 * Function to send an email.
	 *
	 * @param	mixed	$recipient
	 * @param	string	$subject
	 * @param	array	$parameters
	 *
	 * @return	boolean
	 */
	public static function send($recipient, $subject, array $parameters = array())
	{
		$settings = isset($parameters['settings']) ? $parameters['settings'] :
			Application_Model_Setting::getInstance();

		if (!isset($parameters['body']))
		{
			if (!isset($parameters['template']))
			{
				throw new RuntimeException('Incorrect email template name');
			}
			
			if (!isset($parameters['assign']))
			{
				$parameters['assign'] = array();
			}

			$parameters['body'] = self::renderBody($parameters['template'], $parameters['assign']);
		}

		$mail = new Zend_Mail('utf-8');
		$mail->setFrom($settings['email_fromAddress'], $settings['email_fromName']);
		$mail->addTo($recipient);
		$mail->setSubject($subject);
		$mail->setBodyHtml($parameters['body']);

		return $mail->send();
	}

	/**
	 * Renders email body with email layout.
	 *
	 * @param	string	$template
	 * @param	array	$assign
	 *
	 * @return	string
	 */
	public static function renderBody($template, array $assign = array())
	{
		$view = new Zend_View;
		$view->setScriptPath(APPLICATION_PATH . '/views/scripts/email');

		if (empty($assign['opts']['baseUrl']))
		{
			$assign['opts']['baseUrl'] = $view->serverUrl() . '/' . $view->baseUrl();
		}

		$view->assign($assign);

		$layout = clone (new Zend_View_Helper_Layout)->layout();
		$layout->setLayout('email');
		$layout->setView($view);
		$layout->content = $view->render($template . '.html');

		return $layout->render();
	}
}
