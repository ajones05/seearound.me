<?php
/**
 * Regex helper class.
 */
class My_Regex
{
	/**
	 * Contains url protocol regex
	 * @const string
	 */
	const PROTOCOL = '(?:(?:[a-z]+:)?\/\/)';

	/**
	 * Contains url auth regex
	 * @const string
	 */
	const AUTH = '(?:\\S+(?::\\S*)?@)?';

	/**
	 * Contains ip v4 regex
	 * @const string
	 */
	const IPV4 = '(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])(?:\\.(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])){3}';

	/**
	 * Contains ip v6 regex
	 * @const string
	 */
	const IPV6 = '(?:(?:[0-9a-fA-F:]){1,4}(?:(?::(?:[0-9a-fA-F]){1,4}|:)){2,7})+';

	/**
	 * Contains url host regex
	 * @const string
	 */
	const HOST = '(?:(?:[a-z\\x{00a1}-\\x{ffff}0-9]-*)*[a-z\\x{00a1}-\\x{ffff}0-9]+)';

	/**
	 * Contains url domain regex
	 * @const string
	 */
	const DOMAIN = '(?:\\.(?:[a-z\\x{00a1}-\\x{ffff}0-9]-*)*[a-z\\x{00a1}-\\x{ffff}0-9]+)*';

	/**
	 * Contains url tld regex
	 * @const string
	 */
	const TLD = '(?:\\.(?:[a-z\\x{00a1}-\\x{ffff}]{2,}))\\.?';

	/**
	 * Contains url port regex
	 * @const string
	 */
	const PORT = '(?::\\d{2,5})?';

	/**
	 * Contains url path regex
	 * @const string
	 */
	const PATH = '(?:[\/?#][^\\s"]*)?';

	/**
	 * Contains url path regex
	 * @const string
	 */
	const BASE64 = '[a-zA-Z0-9+\/]+={0,2}';

	/**
	 * Returns URL regex.
	 *
	 * @return string
	 */
	public static function url()
	{
		$regex = '(?:' . self::PROTOCOL . '|www\\.)'
			. self::AUTH 
			. '(?:localhost|' .self::IPV4 . '|' . self::IPV6 . '|' .
					self::HOST . self::DOMAIN . self::TLD . ')'
			. self::PORT
			. self::PATH;
		return $regex;
	}
}
