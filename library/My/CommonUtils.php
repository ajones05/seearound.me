<?php
/**
 * Common utils class.
 */
class My_CommonUtils
{
	/**
	 * @var	string
	 */
	const KEY_DICTONARY = '0123456789ABCDEFGHIKLMNPQRSTVXYZ';

	/**
	 * @var	array 	The extensions for mime types
	 */
	public static $mimetype_extension = [
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
	];

	/**
	 * @var	array 	The extensions for image types
	 */
	public static $imagetype_extension = [
		IMAGETYPE_GIF => 'gif',
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG => 'png'
	];

	/**
	 * @var	array
	 */
	public static $timezone = [
		'Pacific/Niue' => '(GMT-11:00) Niue',
		'Pacific/Pago_Pago' => '(GMT-11:00) Pago Pago',
		'Pacific/Honolulu' => '(GMT-10:00) Hawaii Time',
		'Pacific/Rarotonga' => '(GMT-10:00) Rarotonga',
		'Pacific/Tahiti' => '(GMT-10:00) Tahiti',
		'Pacific/Marquesas' => '(GMT-09:30) Marquesas',
		'America/Anchorage' => '(GMT-08:00) Alaska Time',
		'Pacific/Gambier' => '(GMT-09:00) Gambier',
		'America/Los_Angeles' => '(GMT-07:00) Pacific Time',
		'America/Tijuana' => '(GMT-07:00) Pacific Time - Tijuana',
		'America/Vancouver' => '(GMT-07:00) Pacific Time - Vancouver',
		'America/Whitehorse' => '(GMT-07:00) Pacific Time - Whitehorse',
		'Pacific/Pitcairn' => '(GMT-08:00) Pitcairn',
		'America/Dawson_Creek' => '(GMT-07:00) Mountain Time - Dawson Creek',
		'America/Denver' => '(GMT-06:00) Mountain Time',
		'America/Edmonton' => '(GMT-06:00) Mountain Time - Edmonton',
		'America/Hermosillo' => '(GMT-07:00) Mountain Time - Hermosillo',
		'America/Mazatlan' => '(GMT-06:00) Mountain Time - Chihuahua, Mazatlan',
		'America/Phoenix' => '(GMT-07:00) Mountain Time - Arizona',
		'America/Yellowknife' => '(GMT-06:00) Mountain Time - Yellowknife',
		'America/Belize' => '(GMT-06:00) Belize',
		'America/Chicago' => '(GMT-05:00) Central Time',
		'America/Costa_Rica' => '(GMT-06:00) Costa Rica',
		'America/El_Salvador' => '(GMT-06:00) El Salvador',
		'America/Guatemala' => '(GMT-06:00) Guatemala',
		'America/Managua' => '(GMT-06:00) Managua',
		'America/Mexico_City' => '(GMT-05:00) Central Time - Mexico City',
		'America/Regina' => '(GMT-06:00) Central Time - Regina',
		'America/Tegucigalpa' => '(GMT-06:00) Central Time - Tegucigalpa',
		'America/Winnipeg' => '(GMT-05:00) Central Time - Winnipeg',
		'Pacific/Easter' => '(GMT-05:00) Easter Island',
		'Pacific/Galapagos' => '(GMT-06:00) Galapagos',
		'America/Bogota' => '(GMT-05:00) Bogota',
		'America/Cancun' => '(GMT-05:00) America Cancun',
		'America/Guayaquil' => '(GMT-05:00) Guayaquil',
		'America/Havana' => '(GMT-04:00) Havana',
		'America/Iqaluit' => '(GMT-04:00) Eastern Time - Iqaluit',
		'America/Jamaica' => '(GMT-05:00) Jamaica',
		'America/Lima' => '(GMT-05:00) Lima',
		'America/Nassau' => '(GMT-04:00) Nassau',
		'America/New_York' => '(GMT-04:00) Eastern Time',
		'America/Panama' => '(GMT-05:00) Panama',
		'America/Port-au-Prince' => '(GMT-05:00) Port-au-Prince',
		'America/Rio_Branco' => '(GMT-05:00) Rio Branco',
		'America/Toronto' => '(GMT-04:00) Eastern Time - Toronto',
		'America/Caracas' => '(GMT-04:30) Caracas',
		'America/Asuncion' => '(GMT-04:00) Asuncion',
		'America/Barbados' => '(GMT-04:00) Barbados',
		'America/Boa_Vista' => '(GMT-04:00) Boa Vista',
		'America/Campo_Grande' => '(GMT-04:00) Campo Grande',
		'America/Cuiaba' => '(GMT-04:00) Cuiaba',
		'America/Curacao' => '(GMT-04:00) Curacao',
		'America/Grand_Turk' => '(GMT-04:00) Grand Turk',
		'America/Guyana' => '(GMT-04:00) Guyana',
		'America/Halifax' => '(GMT-03:00) Atlantic Time - Halifax',
		'America/La_Paz' => '(GMT-04:00) La Paz',
		'America/Manaus' => '(GMT-04:00) Manaus',
		'America/Martinique' => '(GMT-04:00) Martinique',
		'America/Port_of_Spain' => '(GMT-04:00) Port of Spain',
		'America/Porto_Velho' => '(GMT-04:00) Porto Velho',
		'America/Puerto_Rico' => '(GMT-04:00) Puerto Rico',
		'America/Santiago' => '(GMT-03:00) Santiago',
		'America/Santo_Domingo' => '(GMT-04:00) Santo Domingo',
		'America/Thule' => '(GMT-03:00) Thule',
		'Antarctica/Palmer' => '(GMT-03:00) Palmer',
		'Atlantic/Bermuda' => '(GMT-03:00) Bermuda',
		'America/St_Johns' => '(GMT-02:30) Newfoundland Time - St. Johns',
		'America/Araguaina' => '(GMT-03:00) Araguaina',
		'America/Argentina/Buenos_Aires' => '(GMT-03:00) Buenos Aires',
		'America/Bahia' => '(GMT-03:00) Salvador',
		'America/Belem' => '(GMT-03:00) Belem',
		'America/Cayenne' => '(GMT-03:00) Cayenne',
		'America/Fortaleza' => '(GMT-03:00) Fortaleza',
		'America/Godthab' => '(GMT-02:00) Godthab',
		'America/Maceio' => '(GMT-03:00) Maceio',
		'America/Miquelon' => '(GMT-02:00) Miquelon',
		'America/Montevideo' => '(GMT-03:00) Montevideo',
		'America/Paramaribo' => '(GMT-03:00) Paramaribo',
		'America/Recife' => '(GMT-03:00) Recife',
		'America/Sao_Paulo' => '(GMT-03:00) Sao Paulo',
		'Antarctica/Rothera' => '(GMT-03:00) Rothera',
		'Atlantic/Stanley' => '(GMT-03:00) Stanley',
		'America/Noronha' => '(GMT-02:00) Noronha',
		'Atlantic/South_Georgia' => '(GMT-02:00) South Georgia',
		'America/Scoresbysund' => '(GMT+00:00) Scoresbysund',
		'Atlantic/Azores' => '(GMT+00:00) Azores',
		'Atlantic/Cape_Verde' => '(GMT-01:00) Cape Verde',
		'Africa/Abidjan' => '(GMT+00:00) Abidjan',
		'Africa/Accra' => '(GMT+00:00) Accra',
		'Africa/Bissau' => '(GMT+00:00) Bissau',
		'Africa/Casablanca' => '(GMT+01:00) Casablanca',
		'Africa/El_Aaiun' => '(GMT+01:00) El Aaiun',
		'Africa/Monrovia' => '(GMT+00:00) Monrovia',
		'America/Danmarkshavn' => '(GMT+00:00) Danmarkshavn',
		'Atlantic/Canary' => '(GMT+01:00) Canary Islands',
		'Atlantic/Faroe' => '(GMT+01:00) Faeroe',
		'Atlantic/Reykjavik' => '(GMT+00:00) Reykjavik',
		'Etc/GMT' => '(GMT+00:00) GMT (no daylight saving)',
		'Europe/Dublin' => '(GMT+01:00) Dublin',
		'Europe/Lisbon' => '(GMT+01:00) Lisbon',
		'Europe/London' => '(GMT+01:00) London',
		'Africa/Algiers' => '(GMT+01:00) Algiers',
		'Africa/Ceuta' => '(GMT+02:00) Ceuta',
		'Africa/Lagos' => '(GMT+01:00) Lagos',
		'Africa/Ndjamena' => '(GMT+01:00) Ndjamena',
		'Africa/Tunis' => '(GMT+01:00) Tunis',
		'Africa/Windhoek' => '(GMT+01:00) Windhoek',
		'Europe/Amsterdam' => '(GMT+02:00) Amsterdam',
		'Europe/Andorra' => '(GMT+02:00) Andorra',
		'Europe/Belgrade' => '(GMT+02:00) Central European Time - Belgrade',
		'Europe/Berlin' => '(GMT+02:00) Berlin',
		'Europe/Brussels' => '(GMT+02:00) Brussels',
		'Europe/Budapest' => '(GMT+02:00) Budapest',
		'Europe/Copenhagen' => '(GMT+02:00) Copenhagen',
		'Europe/Gibraltar' => '(GMT+02:00) Gibraltar',
		'Europe/Luxembourg' => '(GMT+02:00) Luxembourg',
		'Europe/Madrid' => '(GMT+02:00) Madrid',
		'Europe/Malta' => '(GMT+02:00) Malta',
		'Europe/Monaco' => '(GMT+02:00) Monaco',
		'Europe/Oslo' => '(GMT+02:00) Oslo',
		'Europe/Paris' => '(GMT+02:00) Paris',
		'Europe/Prague' => '(GMT+02:00) Central European Time - Prague',
		'Europe/Rome' => '(GMT+02:00) Rome',
		'Europe/Stockholm' => '(GMT+02:00) Stockholm',
		'Europe/Tirane' => '(GMT+02:00) Tirane',
		'Europe/Vienna' => '(GMT+02:00) Vienna',
		'Europe/Warsaw' => '(GMT+02:00) Warsaw',
		'Europe/Zurich' => '(GMT+02:00) Zurich',
		'Africa/Cairo' => '(GMT+02:00) Cairo',
		'Africa/Johannesburg' => '(GMT+02:00) Johannesburg',
		'Africa/Maputo' => '(GMT+02:00) Maputo',
		'Africa/Tripoli' => '(GMT+02:00) Tripoli',
		'Asia/Amman' => '(GMT+03:00) Amman',
		'Asia/Beirut' => '(GMT+03:00) Beirut',
		'Asia/Damascus' => '(GMT+03:00) Damascus',
		'Asia/Gaza' => '(GMT+03:00) Gaza',
		'Asia/Jerusalem' => '(GMT+03:00) Jerusalem',
		'Asia/Nicosia' => '(GMT+03:00) Nicosia',
		'Europe/Athens' => '(GMT+03:00) Athens',
		'Europe/Bucharest' => '(GMT+03:00) Bucharest',
		'Europe/Chisinau' => '(GMT+03:00) Chisinau',
		'Europe/Helsinki' => '(GMT+03:00) Helsinki',
		'Europe/Istanbul' => '(GMT+03:00) Istanbul',
		'Europe/Kaliningrad' => '(GMT+02:00) Moscow-01 - Kaliningrad',
		'Europe/Kiev' => '(GMT+03:00) Kiev',
		'Europe/Riga' => '(GMT+03:00) Riga',
		'Europe/Sofia' => '(GMT+03:00) Sofia',
		'Europe/Tallinn' => '(GMT+03:00) Tallinn',
		'Europe/Vilnius' => '(GMT+03:00) Vilnius',
		'Africa/Khartoum' => '(GMT+03:00) Khartoum',
		'Africa/Nairobi' => '(GMT+03:00) Nairobi',
		'Antarctica/Syowa' => '(GMT+03:00) Syowa',
		'Asia/Baghdad' => '(GMT+03:00) Baghdad',
		'Asia/Qatar' => '(GMT+03:00) Qatar',
		'Asia/Riyadh' => '(GMT+03:00) Riyadh',
		'Europe/Minsk' => '(GMT+03:00) Minsk',
		'Europe/Moscow' => '(GMT+03:00) Moscow+00 - Moscow',
		'Asia/Tehran' => '(GMT+04:30) Tehran',
		'Asia/Baku' => '(GMT+04:00) Baku',
		'Asia/Dubai' => '(GMT+04:00) Dubai',
		'Asia/Tbilisi' => '(GMT+04:00) Tbilisi',
		'Asia/Yerevan' => '(GMT+04:00) Yerevan',
		'Europe/Samara' => '(GMT+04:00) Moscow+01 - Samara',
		'Indian/Mahe' => '(GMT+04:00) Mahe',
		'Indian/Mauritius' => '(GMT+04:00) Mauritius',
		'Indian/Reunion' => '(GMT+04:00) Reunion',
		'Asia/Kabul' => '(GMT+04:30) Kabul',
		'Antarctica/Mawson' => '(GMT+05:00) Mawson',
		'Asia/Aqtau' => '(GMT+05:00) Aqtau',
		'Asia/Aqtobe' => '(GMT+05:00) Aqtobe',
		'Asia/Ashgabat' => '(GMT+05:00) Ashgabat',
		'Asia/Dushanbe' => '(GMT+05:00) Dushanbe',
		'Asia/Karachi' => '(GMT+05:00) Karachi',
		'Asia/Tashkent' => '(GMT+05:00) Tashkent',
		'Asia/Yekaterinburg' => '(GMT+05:00) Moscow+02 - Yekaterinburg',
		'Indian/Kerguelen' => '(GMT+05:00) Kerguelen',
		'Indian/Maldives' => '(GMT+05:00) Maldives',
		'Asia/Calcutta' => '(GMT+05:30) India Standard Time',
		'Asia/Colombo' => '(GMT+05:30) Colombo',
		'Asia/Katmandu' => '(GMT+05:45) Katmandu',
		'Antarctica/Vostok' => '(GMT+06:00) Vostok',
		'Asia/Almaty' => '(GMT+06:00) Almaty',
		'Asia/Bishkek' => '(GMT+06:00) Bishkek',
		'Asia/Dhaka' => '(GMT+06:00) Dhaka',
		'Asia/Omsk' => '(GMT+06:00) Moscow+03 - Omsk, Novosibirsk',
		'Asia/Thimphu' => '(GMT+06:00) Thimphu',
		'Indian/Chagos' => '(GMT+06:00) Chagos',
		'Asia/Rangoon' => '(GMT+06:30) Rangoon',
		'Indian/Cocos' => '(GMT+06:30) Cocos',
		'Antarctica/Davis' => '(GMT+07:00) Davis',
		'Asia/Bangkok' => '(GMT+07:00) Bangkok',
		'Asia/Hovd' => '(GMT+08:00) Hovd',
		'Asia/Jakarta' => '(GMT+07:00) Jakarta',
		'Asia/Krasnoyarsk' => '(GMT+07:00) Moscow+04 - Krasnoyarsk',
		'Asia/Saigon' => '(GMT+07:00) Hanoi',
		'Indian/Christmas' => '(GMT+07:00) Christmas',
		'Antarctica/Casey' => '(GMT+08:00) Casey',
		'Asia/Brunei' => '(GMT+08:00) Brunei',
		'Asia/Choibalsan' => '(GMT+09:00) Choibalsan',
		'Asia/Hong_Kong' => '(GMT+08:00) Hong Kong',
		'Asia/Irkutsk' => '(GMT+08:00) Moscow+05 - Irkutsk',
		'Asia/Kuala_Lumpur' => '(GMT+08:00) Kuala Lumpur',
		'Asia/Macau' => '(GMT+08:00) Macau',
		'Asia/Makassar' => '(GMT+08:00) Makassar',
		'Asia/Manila' => '(GMT+08:00) Manila',
		'Asia/Shanghai' => '(GMT+08:00) China Time - Beijing',
		'Asia/Singapore' => '(GMT+08:00) Singapore',
		'Asia/Taipei' => '(GMT+08:00) Taipei',
		'Asia/Ulaanbaatar' => '(GMT+09:00) Ulaanbaatar',
		'Australia/Perth' => '(GMT+08:00) Western Time - Perth',
		'Asia/Pyongyang' => '(GMT+08:30) Pyongyang',
		'Asia/Dili' => '(GMT+09:00) Dili',
		'Asia/Jayapura' => '(GMT+09:00) Jayapura',
		'Asia/Seoul' => '(GMT+09:00) Seoul',
		'Asia/Tokyo' => '(GMT+09:00) Tokyo',
		'Asia/Yakutsk' => '(GMT+09:00) Moscow+06 - Yakutsk',
		'Pacific/Palau' => '(GMT+09:00) Palau',
		'Australia/Adelaide' => '(GMT+09:30) Central Time - Adelaide',
		'Australia/Darwin' => '(GMT+09:30) Central Time - Darwin',
		'Antarctica/DumontDUrville' => '(GMT+10:00) Dumont D\'Urville',
		'Asia/Magadan' => '(GMT+10:00) Moscow+07 - Magadan',
		'Asia/Vladivostok' => '(GMT+10:00) Moscow+07 - Yuzhno-Sakhalinsk',
		'Australia/Brisbane' => '(GMT+10:00) Eastern Time - Brisbane',
		'Australia/Hobart' => '(GMT+10:00) Eastern Time - Hobart',
		'Australia/Sydney' => '(GMT+10:00) Eastern Time - Melbourne, Sydney',
		'Pacific/Chuuk' => '(GMT+10:00) Truk',
		'Pacific/Guam' => '(GMT+10:00) Guam',
		'Pacific/Port_Moresby' => '(GMT+10:00) Port Moresby',
		'Pacific/Efate' => '(GMT+11:00) Efate',
		'Pacific/Guadalcanal' => '(GMT+11:00) Guadalcanal',
		'Pacific/Kosrae' => '(GMT+11:00) Kosrae',
		'Pacific/Norfolk' => '(GMT+11:00) Norfolk',
		'Pacific/Noumea' => '(GMT+11:00) Noumea',
		'Pacific/Pohnpei' => '(GMT+11:00) Ponape',
		'Asia/Kamchatka' => '(GMT+12:00) Moscow+09 - Petropavlovsk-Kamchatskiy',
		'Pacific/Auckland' => '(GMT+12:00) Auckland',
		'Pacific/Fiji' => '(GMT+12:00) Fiji',
		'Pacific/Funafuti' => '(GMT+12:00) Funafuti',
		'Pacific/Kwajalein' => '(GMT+12:00) Kwajalein',
		'Pacific/Majuro' => '(GMT+12:00) Majuro',
		'Pacific/Nauru' => '(GMT+12:00) Nauru',
		'Pacific/Tarawa' => '(GMT+12:00) Tarawa',
		'Pacific/Wake' => '(GMT+12:00) Wake',
		'Pacific/Wallis' => '(GMT+12:00) Wallis',
		'Pacific/Apia' => '(GMT+13:00) Apia',
		'Pacific/Enderbury' => '(GMT+13:00) Enderbury',
		'Pacific/Fakaofo' => '(GMT+13:00) Fakaofo',
		'Pacific/Tongatapu' => '(GMT+13:00) Tongatapu',
		'Pacific/Kiritimati' => '(GMT+14:00) Kiritimati'
	];

	/**
	 * @var	string
	 */
	public static $link_regex = '(((f|ht)tps?:\/\/.)|(www\.))([-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6})\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=!;]*)';

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
	 * Generates alphanumeric key.
	 *
	 * @param	integer $len Key length.
	 * @return	string Generated key.
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

	/**
	 * Creates image thumbnails.
	 *
	 * @param	string $image Image full path
	 * @param	array $thumbs Thumbnails list in format [width, height, path, mode]
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

		if ($imageType == IMAGETYPE_JPEG)
		{
			$exif = @exif_read_data($image);

			if(!empty($exif['Orientation']))
			{
				switch($exif['Orientation'])
				{
					case 8:
						$source = imagerotate($source, 90, 0);
						break;
					case 3:
						$source = imagerotate($source, 180, 0);
						break;
					case 6:
						$source = imagerotate($source, -90, 0);
						break;
				}
			}
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

	/**
	 * Checks if user device is mobile.
	 *
	 * @return boolean
	 */
	public static function isMobile()
	{
		$mobile = Zend_Controller_Front::getInstance()
			->getRequest()->getParam('mobile');
		return $mobile || (new Mobile_Detect)->isMobile();
	}
}
