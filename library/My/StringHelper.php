<?php

/**
 * String class helper.
 */
class My_StringHelper
{
	/**
	 * @const	string
	 */
	const KEY_DICTONARY = '123456789ABCDEFGHIKLMNPQRSTVXYZ';

	/**
	 *
	 *
	 * @param	string	$text
	 *
	 * @return	string
	 */
	public static function stringLimit($text, $limit, $dots = "")
	{
		$count = strlen($text);

		if ($count > $limit)
		{
			$text = substr(trim($text, '.'), 0, $limit - strlen($dots)) . $dots;
		}

		return $text;
	}

	/**
	 * Generates alphanumeric key.
	 *
	 * @param	integer	$len	Key length.
	 *
	 * @return	string	Generated key.
	 */
	public static function generateKey($len)
	{
		$key = '';
		$maxIndex = strlen(self::KEY_DICTONARY) - 1;

		for ($i = 0; $i < $len; $i++)
		{
			$key .= substr(self::KEY_DICTONARY, rand(0, $maxIndex), 1);
		}

		return $key;
	}
}
