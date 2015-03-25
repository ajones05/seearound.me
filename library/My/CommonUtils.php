<?php
/**
 * Common utils class.
 */
class My_CommonUtils
{
	/**
	 * Replaces link to href.
	 *
	 * @param	string	$body
	 *
	 * @return	string
	 */
	public static function linkClickable($body)
	{
		return preg_replace_callback(
			// '/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)/m',
			'/((http(s)?:\/\/.)|(www\.))[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)/m',
			function($match)
			{
				$text = trim($match[0]);
				$pieces = parse_url($text);
				$scheme = isset($pieces['scheme']) ? $pieces['scheme'] : 'http';
				$host = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];
				$link = $scheme . '://' . $host;

				if (isset($pieces['path']) && $pieces['path'] != $host)
				{
					$link .= $pieces['path'];
				}

				if (isset($pieces['query']))
				{
					$link .= '?' . $pieces['query'];
				}

				if (isset($pieces['fragment']))
				{
					$link .= '#' . $pieces['fragment'];
				}

				return '<a href="' . htmlspecialchars($link) . '">' . $text . '</a>';
			},
			$body
		);
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
