<?php
require_once ROOT_PATH . '/vendor/autoload.php';

/**
 * Ip hepler class.
 */
class My_Ip
{
	/**
	 * @var	array 
	 */
	protected static $city = array();

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

		if (!isset(self::$city[$ip_address]))
		{
			try
			{
				self::$city[$ip_address] = (new GeoIp2\Database\Reader(ROOT_PATH . '/includes/maxmind-db/GeoLite2-City.mmdb'))->city($ip_address);
			}
			catch (Exception $e)
			{
				return false;
			}
		}

		return self::$city[$ip_address];
	}

	/**
	 * Returns user geolocation by IP address.
	 *
	 * @return	array
	 */
	public static function geolocation($default = true)
	{
		$city = self::getCity();

		if ($city && isset($city->location->latitude) && isset($city->location->longitude))
		{
			$addrress = '';
			
			if (isset($city->city->names['en']))
			{
				$addrress .= $city->city->names['en'];

				if (isset($city->postal->code))
				{
					$addrress .= ' ' . $city->postal->code;
				}

				if (isset($city->subdivisions[0]->isoCode))
				{
					$addrress .= ' ' . $city->subdivisions[0]->isoCode;
				}
			}

			return array($city->location->latitude, $city->location->longitude, $addrress);
		}

		if ($default)
		{
			$config = Zend_Registry::get('config_global');
			return array($config->geolocation->lat, $config->geolocation->lng, $config->geolocation->address);
		}

		return false;
	}
}
