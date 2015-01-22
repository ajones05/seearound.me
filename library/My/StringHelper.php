<?php

/**
 * String class helper.
 */
class My_StringHelper
{
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
			$text = substr($text, 0, $limit - strlen($dots)) . $dots;
		}

		return $text;
	}
}
