<?php
/**
 * Common utils class.
 */
class My_CommonUtils
{
	/**
	 * Render html.
	 *
	 * @param	string	$body
	 * @param	integer	$limit
	 *
	 * @return	string
	 */
	public static function renderHtml($body, $limit = 0)
	{
		$output = '';

		for ($i = 0; $i < strlen($body);)
		{
			if (preg_match('/^(((f|ht)tps?:\/\/.)|(www\.))[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=!;]*)/',
				substr($body, $i), $matches))
			{
				$i += strlen($matches[0]);

				$text = $matches[0];

				if ($limit && $i >= $limit)
				{
					$text = My_StringHelper::stringLimit($text, $limit - $i);
				}

				$output .= self::renderLink($matches[0], $text);
			}
			else
			{
				$output .= $body[$i];

				$i++;
			}

			if ($limit && $i >= $limit)
			{
				$output .= '...';

				break;
			}
		}

		return nl2br($output);
	}

	/**
	 * Replaces link to href.
	 *
	 * @param	string	$link
	 * @param	string	$text
	 *
	 * @return	string
	 */
	public static function renderLink($link, $text)
	{
		$url = parse_url(trim($link));
		$scheme = isset($url['scheme']) ? $url['scheme'] : 'http';
		$host = isset($url['host']) ? $url['host'] : $url['path'];
		$link = $scheme . '://' . $host;

		if (isset($url['path']) && $url['path'] != $host)
		{
			$link .= $url['path'];
		}

		if (isset($url['query']))
		{
			$link .= '?' . $url['query'];
		}

		if (isset($url['fragment']))
		{
			$link .= '#' . $url['fragment'];
		}

		return '<a href="' . htmlspecialchars($link) . '">' . $text . '</a>';
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
