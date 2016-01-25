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
		$count = mb_strlen($text, 'UTF-8');

		if ($count > $limit)
		{
			$text = substr(trim($text, '.'), 0, $limit - mb_strlen($dots, 'UTF-8')) . $dots;
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

	public static function utf8_decode($string)
	{
		$__string = $string;
		$count = 0;

		while (mb_detect_encoding($__string) == 'UTF-8')
		{
			$__string = utf8_decode($__string);
			$count++;
		}

		for ($i = 0; $i < $count - 1; $i++)
		{
			$string = utf8_decode($string);
		}

		return $string;
	}

	/**
	 * Converts empty string to NULL.
	 *
	 * @param	string $str
	 * @return	mixed
	 */
	public static function emptyToNull($str)
	{
		return trim($str) !== '' ? $str : null;
	}
}
