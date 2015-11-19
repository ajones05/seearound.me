<?php
/**
 * Log helper class.
 */
class My_Log
{
	/**
	 * Logs exception.
	 *
	 * @param	Exception $exception
	 * @return	void
	 */
	public static function exception($exception)
	{
		Zend_Registry::get('logger')
			->setEventItem('request', var_export($_REQUEST, true))
			->setEventItem('server', var_export($_SERVER, true))
			->exception($exception);
	}
}
