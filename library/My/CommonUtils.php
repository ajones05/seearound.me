<?php
/**
 * Common utils class.
 */
class My_CommonUtils
{
	/**
	 *
	 *
	 * @param	string	$text
	 *
	 * @return	string
	 */
	public static function linkClickable($text)
	{
		$text = preg_replace("/(?<!http:\/\/)www\./","http://www.", $text);
		return preg_replace('!(((f|ht)tp://)[-a-zA-Z?-??-?()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1" target="_blank">$1</a>', $text);
	}

	/**
	 *
	 *
	 * @param	string	$text
	 *
	 * @return	string
	 */
	public static function commentTexts($text)
	{
		$subContent = '';
		$nextLine = 50;
		$textCounter = 0;

		while ($textCounter < strlen($text))
		{
			$substring = preg_replace('/\s+?(\S+)?$/', '', substr($text, $textCounter, $nextLine));
			$subContent .= $substring."<br>";
			$textCounter = $textCounter+($nextLine-1);
		}

		return $subContent;
	}
}
