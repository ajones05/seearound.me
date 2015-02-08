<?php
require_once ROOT_PATH . '/vendor/autoload.php';

/**
 * Ip hepler class.
 */
class My_Ip
{
	/**
	 *
	 *
	 *
	 * @return	string
	 */
	public static function getIpAddress()
	{
		if (isset($_GET["ip"]))
		{
			return $_GET["ip"];
		}

		if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
		{
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		}

		if (!empty($_SERVER["REMOTE_ADDR"]))
		{
			return $_SERVER["REMOTE_ADDR"];
		}

		return false;
	}

	/**
	 *
	 *
	 * @param	string	$ip_address
	 *
	 * @return	mixed
	 */
	public static function getCity($ip_address = null)
	{
		if ($ip_address == null && ($ip_address = self::getIpAddress()) == null)
		{
			return false;
		}

		try
		{
			$reader = new GeoIp2\Database\Reader(ROOT_PATH . '/includes/maxmind-db/GeoLite2-City.mmdb');

			return $reader->city($ip_address);
		}
		catch (Exception $e)
		{
			return false;
		}
	}
}