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
		$encoding = 'UTF-8';
		$count = mb_strlen($text, $encoding);

		if ($count > $limit)
		{
			$text = mb_substr(trim($text, '.'), 0,
				$limit - mb_strlen($dots, $encoding), $encoding) . $dots;
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

	/**
	 * Returns multiple prefix.
	 *
	 * @param	value $value
	 * @return	string
	 */
	public static function multiplePrefix($value, $multiple = 's', $single = '')
	{
		return (string)$value === '1' ? $single : $multiple;
	}

	/**
	 * Removes control characters from both ends of this string
	 * returning null if the string is empty ("") after the trim or if it is null.
	 *
	 * @param	string $string
	 * @param	array $values
	 * @return	string
	 */
	public static function trimToNull($string, array $values = [''])
	{
		return self::toNull(trim($string), $values);
	}

	/**
	 * Returning null if the string is equals one of values.
	 *
	 * @param	string $string
	 * @param	array $values
	 * @return	string
	 */
	public static function toNull($string, array $values)
	{
		foreach ($values as $value)
		{
			if ($string === $value)
			{
				return null;
			}
		}

		return $string;
	}
}
