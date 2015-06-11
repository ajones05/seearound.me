<?php
/**
 * Common utils class.
 */
class My_CommonUtils
{
	/**
	 * @var	array 	The extensions for mime types
	 */
	public static $mimetype_extension = array(
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
	);

	/**
	 * @var	string
	 */
	public static $link_regex = '(((f|ht)tps?:\/\/.)|(www\.))[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=!;]*)';

	/**
	 * Returns url metadata info.
	 *
	 * @param	string	$url
	 * @return	string
	 */
	public static function getLinkMeta($url)
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

		$result = array();
		
		if (trim($html) !== '')
		{
			$dom = new DomDocument;
			@$dom->loadHTML($html);

			$meta = $dom->getElementsByTagName('meta');

			if ($meta->length)
			{
				foreach ($meta as $tag)
				{
					$property = $tag->getAttribute('property');

					if ($property != '')
					{
						$result['property'][$property] = My_StringHelper::utf8_decode($tag->getAttribute('content'));
						continue;
					}

					$name = $tag->getAttribute('name');

					if ($name != '')
					{
						$result['name'][$name] = My_StringHelper::utf8_decode($tag->getAttribute('content'));
					}
				}
			}

			$title = $dom->getElementsByTagName('title');

			if ($title->length > 0)
			{
				$result['title'] = My_StringHelper::utf8_decode($title->item(0)->textContent);
			}
		}

		return $result;
	}

	/**
	 * Checks and fix link fomat.
	 *
	 * @param	string	$url
	 * @return	string
	 */
	public static function renderLink($link)
	{
		if (!preg_match('/^(f|ht)tps?/', $link))
		{
			$link = '//' . $link;
		}

		return $link;
	}

	/**
	 * Generates code.
	 *
	 * @return	string
	 */
    public static function generateCode()
	{
        mt_srand();
        return substr(md5(mt_rand(0, time())), mt_rand(0, 20), 10);
    }
}
