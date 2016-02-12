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
	 * @param	array $thumbs Thumbnails list in format array(width, height, path, mode)
	 *	mode - 0 - letterbox; 1 - crop to fit; 2 - crop to thumbnail width
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
				$source = imagecreatefromgif($image);
				break;
			case IMAGETYPE_JPEG:
				$source = imagecreatefromjpeg($image);
				break;
			case IMAGETYPE_PNG:
				$source = imagecreatefrompng($image);
				break;
			default: 
				throw new InvalidArgumentException('Incorrect image type: ' . $imageType);
		}

		if (!$source)
		{
			throw new InvalidArgumentException('Incorrect image file: ' . $image);
		}

		$source_img_w = imageSX($source);
		$source_img_h = imageSY($source);
		$source_ratio = $source_img_w / $source_img_h;

		foreach ($thumbs as $thumb)
		{
			$source_x = 0;
			$source_y = 0;

			$mode = My_ArrayHelper::getProp($thumb, 3);

			if ($mode > 0)
			{
				$thumb_ratio = $thumb[0] / $thumb[1];

				if ($mode === 1)
				{
					if ($source_ratio > $thumb_ratio)
					{
						$temp_width = $source_img_h * $thumb_ratio;
						$temp_height = $source_img_h;
						$source_x = ($source_img_w - $temp_width) / 2;
					}
					else
					{
						$temp_width = $source_img_w;
						$temp_height = (int)($source_img_w / $thumb_ratio);
						$source_y = ($source_img_h - $temp_height) / 2;
					}
				}
				elseif ($mode === 2)
				{
					$temp_width = $source_img_w;
					$temp_height = max($source_img_w / $thumb_ratio, 1);

					if ($source_img_w > $source_img_h)
					{
						$thumb_height = $source_img_h * ($thumb[0] / $source_img_w);
						$temp_height /= $thumb[1] / $thumb_height;
						$thumb[1] = max($thumb_height, 1);
						$source_y = 0;
					}
					else
					{
						$source_y = ($source_img_h - $temp_height) / 2;
					}
				}
				else
				{
					throw new InvalidArgumentException('Incorrect thumbnail mode: ' . $mode);
				}

				$img_w = $temp_width;
				$img_h = $temp_height;
				$new_w = $thumb[0];
				$new_h = $thumb[1];
			}
			else
			{
				if ($source_img_w > $source_img_h)
				{
					$new_w = $thumb[0];
					$new_h = $source_img_h * ($thumb[0] / $source_img_w);
				}
				elseif ($source_img_w < $source_img_h)
				{
					$new_w = $source_img_w * ($thumb[1] / $source_img_h);
					$new_h = $thumb[1];
				}
				else
				{
					$factor = max($source_img_w / $thumb[0], $source_img_h / $thumb[1]);
					$new_w = $source_img_w / $factor;
					$new_h = $source_img_h / $factor;
				}

				if ($new_w < 1)
				{
					$new_h = min($thumb[1], $new_h * (1 / $new_w));
					$new_w = 1;
				}

				if ($new_h < 1)
				{
					$new_w = min($thumb[0], $new_w * (1 / $new_h));
					$new_h = 1;
				}

				$img_w = $source_img_w;
				$img_h = $source_img_h;
			}

			$new = imagecreatetruecolor($new_w, $new_h);

			if ($imageType === IMAGETYPE_GIF || $imageType === IMAGETYPE_PNG)
			{
				imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
				imagealphablending($new, false);
				imagesavealpha($new, true);
			}

			imagecopyresampled($new, $source, 0, 0, $source_x, $source_y, $new_w, $new_h, $img_w, $img_h);

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
