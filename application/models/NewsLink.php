<?php
use Embed\Embed;
use JonnyW\PhantomJs\Client as PhantomJsClient;

/**
 * This is the model class for table "news_link".
 */
class Application_Model_NewsLink extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 * @var string
	 */
	public static $imagePath = 'uploads';

	/**
	 * @var array
	 */
	public static $thumbPath = [
		'448x320' => 'thumb448x320'
	];

	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'news_link';

	/**
	 * @var	array
	 */
	protected $_dependentTables = [
		'Application_Model_News',
		'Application_Model_Image'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'News' => [
			'columns' => 'news_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
		],
		'Image' => [
			'columns' => 'image_id',
			'refTableClass' => 'Application_Model_Image',
			'refColumns' => 'id'
		]
	];

	/**
	 * Removes additional parameters from the link.
	 *
	 * @param	string $link
	 * @return	string
	 */
	public function trimLink($link)
	{
		return preg_replace(['/^https?:\/\//','/^www\./','/\/$/',
			'/[?&](utm_source|utm_medium|utm_term|utm_content|utm_campaign)=([^&])+/'],'',$link);
	}

	/**
	 * Parses links from text.
	 *
	 * @param string $text
	 * @return array|null Array on success, otherwise NULL
	 */
	public static function parseLinks($text)
	{
		return preg_match_all('/' . My_Regex::url() . '/ui',
			$text, $linkMatches) ? $linkMatches[0] : null;
	}

	/**
	 * Saves post link.
	 *
	 * @param string $post
	 * @return mixed If success array, otherwise null
	 */
	public function saveLink($post)
	{
		$matchLinks = preg_match_all('/' . My_Regex::url() . '/ui',
			$post['news'],  $matches);

		if ($matchLinks)
		{
			foreach ($matches[0] as $link)
			{
				$scheme = My_ArrayHelper::getProp(parse_url($link), 'scheme');

				if ($scheme == null)
				{
					$originalLink = $link;
					$link = 'http://' . $link;
				}

				$content = $this->safeGetContent($link);

				if ($content == null)
				{
					if ($scheme == null)
					{
						$link = 'https://' . $originalLink;
					}
					else
					{
						$link = $scheme == 'https' ? preg_replace('/^https/', 'http', $link) :
							preg_replace('/^http/', 'https', $link);
					}

					$content = $this->safeGetContent($link);
				}

				if ($content == null)
				{
					continue;
				}

				$errors = libxml_use_internal_errors(true);
				$dom = new \DOMDocument();
				$dom->loadHTML($content);
				libxml_use_internal_errors($errors);

				$linkData = [
					'news_id' => $post['id'],
					'link' => $link,
					'link_trim' => $this->trimLink($link)
				];

				$titleEl = $dom->getElementsByTagName('title');

				if ($titleEl->length)
				{
					$title = trim(strip_tags($titleEl->item(0)->nodeValue));

					if ($title !== '')
					{
						$linkData['title'] = $title;
					}
				}

				$xpath = new DOMXPath($dom);
				$descriptionEl = $xpath->query('//meta[@name="description"]/@content');

				if ($descriptionEl->length == 0)
				{
					$descriptionEl = $xpath
						->query('//meta[@property="og:description"]/@content');
				}

				if ($descriptionEl->length != 0)
				{
					$description = trim(strip_tags($descriptionEl->item(0)->nodeValue));

					if ($description !== '')
					{
						$linkData['description'] = $description;
					}
				}

				$authorEl = $xpath->query('//meta[@name="author"]/@content');

				if ($authorEl->length != 0)
				{
					$author = trim(strip_tags($authorEl->item(0)->nodeValue));

					if ($author !== '')
					{
						$linkData['author'] = $author;
					}
				}

				// TODO: <link rel="image_src" href="logo.gif" />

				$imageUrl = null;
				$ogImageEl = $xpath->query('//meta[@property="og:image"]/@content');

				if ($ogImageEl->length != 0)
				{
					$imageUrl = $ogImageEl->item(0)->nodeValue;
				}
				else
				{
					$imageEl = $xpath->query('//img');

					if ($imageEl->length)
					{
						$imageUrl = $imageEl->item(0)->getAttribute('src');
					}
				}

				if ($imageUrl != null)
				{
					$parseImageUrl = parse_url($imageUrl);

					if (empty($parseImageUrl['host']))
					{
						$parseUrl = parse_url($link);
						$imageUrl = My_ArrayHelper::getProp($parseUrl, 'scheme', 'http') . '://' .
							My_ArrayHelper::getProp($parseUrl, 'host') . '/' . trim($imageUrl, '/');
					}

					try
					{
						$ext = strtolower(My_ArrayHelper::getProp(pathinfo($imageUrl),
							'extension', 'tmp'));

						if (!in_array($ext, My_CommonUtils::$imagetype_extension))
						{
							if (preg_match('/^(' . implode('|',
								My_CommonUtils::$imagetype_extension) . ')/', $ext, $extMatches))
							{
								$ext = $extMatches[0];
							}
						}

						if ($ext != 'tmp' && !in_array($ext, My_CommonUtils::$imagetype_extension))
						{
							throw new Exception('Incorrect image extension: ' . $ext);
						}

						do
						{
							$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
							$fullPath = ROOT_PATH_WEB . '/uploads/' . $name;
						}
						while (file_exists($fullPath));

						if (!@copy($imageUrl, $fullPath))
						{
							throw new Exception("Download image error");
						}

						$imageType = exif_imagetype($fullPath);

						if (!isset(My_CommonUtils::$imagetype_extension[$imageType]))
						{
							throw new Exception('Incorrect image type: ' . $imageType);
						}

						if (My_CommonUtils::$imagetype_extension[$imageType] != $ext)
						{
							$name = preg_replace('/' . $ext . '$/', My_CommonUtils::$imagetype_extension[$imageType], $name);

							if (!rename($fullPath, ROOT_PATH_WEB . '/uploads/' . $name))
							{
								throw new Exception('Rename image error: ' . $fullPath);
							}
						}

						$image = (new Application_Model_Image)->save('uploads', $name, $thumbs, [
							[[448,320], 'thumb448x320', 2]
						]);

						$linkData['image_id'] = $image['id'];
						$linkData['image_name'] = $name;
					}
					catch (Exception $e)
					{
						if (!empty($fullPath))
						{
							@unlink($fullPath);
						}
					}
				}

				return $linkData+['id'=>$this->insert($linkData)];
			}
		}

		return null;
	}

	/**
	 * Returns page content.
	 *
	 * @param	string $link
	 * @return null|string
	 */
	protected function safeGetContent(&$link)
	{
		try
		{
			$client = PhantomJsClient::getInstance();
			$client->getEngine()->setPath('/usr/local/bin/phantomjs');
			$client->isLazy();

			$request = $client->getMessageFactory()->createRequest($link);
			$request->setTimeout(5000);
			$response = $client->getMessageFactory()->createResponse();
			$client->send($request, $response);

			$status = $response->getStatus();

			if ($status === 200 || $status === 301 || $status === 408)
			{
				if ($response->isRedirect())
				{
					$link = $response->getRedirectUrl();
				}

				return $response->getContent();
			}
		}
		catch (Exception $e)
		{
		}

		return null;
	}

	/**
	 * Change embed create link data to return null instead of exception.
	 *
	 * @param string $link
	 * @return null|Embed\Adapters\Webpage
	 */
	public function embedSafeCreate($link)
	{
		try
		{
			return Embed::create($link);
		}
		catch (Exception $e)
		{
		}

		return null;
	}

	/**
	 * Finds row by trimed link.
	 * @param	string	$link
	 * @return	mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findByLinkTrim($link)
	{
		$result = $this->fetchRow(
			$this->select()
				->setIntegrityCheck(false)
				->from(['l' => 'news_link'], 'l.*')
				->where('l.link_trim=?', $this->trimLink($link))
				->join(['p' => 'news'], 'p.id=l.news_id', '')
				->where('p.isdeleted=0')
		);
		return $result;
	}

	/**
	 * Returnds link thumbail path.
	 *
	 * @param mixed $row
	 * @param string $thumb Thumbnail dimensions WIDTHxHEIGHT
	 * @param array $options
	 * @return mixed String on success, otherwise NULL
	 */
	public static function getThumb($row, $thumb, array $options=[])
	{
		$imageField = My_ArrayHelper::getProp($options, 'alias') . 'image_name';

		if (empty($row[$imageField]))
		{
			return null;
		}

		return self::$thumbPath[$thumb] . '/' . $row[$imageField];
	}

	/**
	 * Returnds link image path.
	 *
	 * @param mixed $row
	 * @param array $options
	 * @return mixed String on success, otherwise NULL
	 */
	public static function getImage($row, array $options=[])
	{
		$imageField = My_ArrayHelper::getProp($options, 'alias') . 'image_name';

		if (empty($row[$imageField]))
		{
			return null;
		}

		return self::$imagePath . '/' . $row[$imageField];
	}
}
