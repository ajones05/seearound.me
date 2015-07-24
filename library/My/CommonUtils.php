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
	 * @var	array 	The extensions for image types
	 */
	public static $imagetype_extension = array(
		IMAGETYPE_GIF => 'gif',
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG => 'png'
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

	/**
	 *
	 *
	 * @return	string
	 */
	public static function dateConversion($secountds = null)
	{
		if ($secountds)
		{
			$data_ref = date('Y-m-d H:i:s', $secountds);
		}
		else
		{
			$data_ref = date('Y-m-d H:i:s');
		}

		$current_date = date('Y-m-d H:i:s');
		$diff = strtotime($current_date) - strtotime($data_ref);
		$years = floor($diff / (365*60*60*24)); 
		$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
		$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
		$hours = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60));
		$minuts = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);
		$seconds = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minuts*60));

		if ($years > 0)
		{
			if ($years == '1')
			{
				return $years . ' Year';
			}
			else
			{
				return $years . ' Years';
			}
		}
		elseif ($months > 0)
		{
			if ($years == '1')
			{
				return $months . ' Month';
			}
			else
			{
				return $months . ' Months';
			}
		}
		elseif ($days > 0)
		{
			if ($days == '1')
			{
				return 'Yesterday';
			}
			else
			{
				return $days . ' Days';
			}
		}
		else
		{
			return 'Today';
		}
	}

	/**
	 * Creates image thumbnails.
	 *
	 * @param	string $image Image full path
	 * @param	array $thumbs Thumbnails list in format array(width, height, path)
	 * @param	integer $imageType Image type
	 * @return	void
	 */
	public static function createThumbs($image, array $thumbs, $imageType = null)
	{
		if (!$imageType)
		{
			$imageType = exif_imagetype($image);
		}

		switch($imageType)
		{
			case IMAGETYPE_GIF:
				$resource = imagecreatefromgif($image);
				break;
			case IMAGETYPE_JPEG:
				$resource = imagecreatefromjpeg($image);
				break;
			case IMAGETYPE_PNG:
				$resource = imagecreatefrompng($image);
				break;
			default: 
				throw new InvalidArgumentException('Incorrect image type: ' . $imageType);
		}

		if (!$resource)
		{
			throw new InvalidArgumentException('Incorrect image file: ' . $image);
		}

		$img_w = imageSX($resource);
		$img_h = imageSY($resource);

		foreach ($thumbs as $thumb)
		{
			if ($img_w > $img_h)
			{
				$new_w = $thumb[0];
				$new_h = $img_h * ($thumb[0] / $img_w);
			}
			elseif ($img_w < $img_h)
			{
				$new_w = $img_w * ($thumb[1] / $img_h);
				$new_h = $thumb[1];
			}
			else
			{
				$factor = max($img_w / $thumb[0], $img_h / $thumb[1]);
				$new_w = $img_w / $factor;
				$new_h = $img_h / $factor;
			}

			$new = imagecreatetruecolor($new_w, $new_h);

			if ($imageType === IMAGETYPE_GIF || $imageType === IMAGETYPE_PNG)
			{
				imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
				imagealphablending($new, false);
				imagesavealpha($new, true);
			}

			imagecopyresampled($new, $resource, 0, 0, 0, 0, $new_w, $new_h, $img_w, $img_h);

			switch($imageType)
			{
				case IMAGETYPE_GIF:
					imagegif($new, $thumb[2]);
					break;
				case IMAGETYPE_JPEG:
					imagejpeg($new, $thumb[2], 60);
					break;
				case IMAGETYPE_PNG:
					imagepng($new, $thumb[2], 4);
					break;
			}
		}
	}
}
