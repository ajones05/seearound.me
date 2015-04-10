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
	 * @param	boolean	$metadata
	 * @return	string
	 */
	public static function renderHtml($body, $limit = 0, $metadata = false)
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

				$link = self::renderLink($matches[0]);

				$output .= '<a href="' . htmlspecialchars($link) . '">' . $text . '</a>';

				if ($metadata)
				{
					$link_metadata = self::renderLinkMetaData($link);

					if ($link_metadata != '')
					{
						$output .= $link_metadata;
						$metadata = false;
					}
				}
			}
			else
			{
				$output .= nl2br($body[$i]);

				$i++;
			}

			if ($limit && $i >= $limit)
			{
				$output .= '...';

				break;
			}
		}

		return $output;
	}

	/**
	 * Replaces link to href.
	 *
	 * @param	string	$link
	 * @return	string
	 */
	public static function renderLink($link)
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

		return $link;
	}

	/**
	 * Renders url metadata info.
	 *
	 * @param	string	$url
	 * @return	string
	 */
	public static function renderLinkMetaData($url)
	{
		libxml_use_internal_errors(true);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, '');
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:37.0) Gecko/20100101 Firefox/37.0');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$html = curl_exec($ch);

		curl_close($ch);

		if (trim($html) !== '')
		{
			$dom = new DomDocument;
			@$dom->loadHTML($html);

			$names = $properties = array();

			$meta = $dom->getElementsByTagName('meta');

			if ($meta->length)
			{
				foreach ($meta as $tag)
				{
					$property = $tag->getAttribute('property');

					if ($property != '')
					{
						$properties[$property] = $tag->getAttribute('content');
						continue;
					}

					$name = $tag->getAttribute('name');

					if ($name != '')
					{
						$names[$name] = $tag->getAttribute('content');
					}
				}
			}

			$title = My_ArrayHelper::getProp($properties, 'og:title');

			if ($title == '')
			{
				$__title = $dom->getElementsByTagName('title');

				if ($__title->length > 0)
				{
					$title = $__title->item(0)->textContent;
				}
			}

			if ($title != null)
			{
				return My_ViewHelper::render(
					'news/link-meta.html',
					array(
						'link' => $url,
						'title' => $title,
						'description' => My_ArrayHelper::getProp($properties, 'og:description',
							My_ArrayHelper::getProp($names, 'description')),
						'image' => My_ArrayHelper::getProp($properties, 'og:image'),
						'author' => My_ArrayHelper::getProp($names, 'author'),
					)
				);
			}
		}

		return '';
	}
}
