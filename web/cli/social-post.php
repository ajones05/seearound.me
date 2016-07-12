<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

defined('ROOT_PATH')
    || define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));

defined('ROOT_PATH_WEB') ||
	define('ROOT_PATH_WEB', ROOT_PATH . '/web');

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(ROOT_PATH . '/application'));

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

require_once 'Zend/Application.php';

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap();

$settings =  (new Application_Model_Setting)->findValuesByName([
	'server_requestScheme',
	'server_httpHost',
	'fb_apiVersion',
	'fb_appId',
	'fb_appSecret',
	'fb_oaklandPageId',
	'fb_oaklandAccessToken',
	'twitter_oaklandToken',
	'twitter_oaklandTokenSecret',
	'twitter_oaklandApiKey',
	'twitter_oaklandApiSecret',
	'twitter_berkeleyToken',
	'twitter_berkeleyTokenSecret',
	'twitter_berkeleyApiKey',
	'twitter_berkeleyApiSecret',
	'twitter_sfToken',
	'twitter_sfTokenSecret',
	'twitter_sfApiKey',
	'twitter_sfApiSecret'
]);

$facebookApi = My_Facebook::getInstance(['_settings'=>$settings]);
$postModel = new Application_Model_News;
$postSocialModel = new Application_Model_PostSocial;
$baseUrl = $settings['server_requestScheme'] . '://' .
	$settings['server_httpHost'];

$oaklandPost = $postModel->fetchRow(
	$postModel->publicSelect()
		->joinLeft(['ps' => 'post_social'], 'ps.post_id=news.id', '')
		->where('news.created_date >= DATE_SUB(NOW(), INTERVAL 5 HOUR) AND ' .
			'(news.comment > 0 OR news.vote > 0) AND ' .
			'(a.city="Oakland" OR a.city="Emeryville" OR a.city="Piedmont") AND ' .
			'a.state="CA" AND ' .
			'a.country="US" AND ' .
			'ps.id IS NULL')
		->order([$postModel->postScore() . ' DESC', 'news.id DESC'])
);

if ($oaklandPost != null)
{
	$message = prepareMessageBody($oaklandPost, $baseUrl, '#Oakland');
	$facebookApi->post('/' . $settings['fb_oaklandPageId'] . '/feed',
		['message' => $message], $settings['fb_oaklandAccessToken']);

	$twitterMessage = prepareMessageBody($oaklandPost, $baseUrl, '#Oakland', 108);
	postToTwitter($twitterMessage, 'oakland', $settings);

	$postSocialModel->insert(['post_id' => $oaklandPost->id]);
	echo My_Cli::success($message);
}

$berkeleyPost = $postModel->fetchRow(
	$postModel->publicSelect()
		->joinLeft(['ps' => 'post_social'], 'ps.post_id=news.id', '')
		->where('created_date >= DATE_SUB(NOW(), INTERVAL 5 HOUR) AND ' .
			'(news.comment > 0 OR news.vote > 0) AND ' .
			'a.city="Berkeley" AND ' .
			'a.state="CA" AND ' .
			'a.country="US" AND ' .
			'ps.id IS NULL')
		->order([$postModel->postScore() . ' DESC', 'news.id DESC'])
);

if ($berkeleyPost != null)
{
	$message = prepareMessageBody($berkeleyPost, $baseUrl, '#Berkeley');
	$twitterMessage = prepareMessageBody($berkeleyPost, $baseUrl, '#Berkeley', 107);
	postToTwitter($twitterMessage, 'berkeley', $settings);

	$postSocialModel->insert(['post_id' => $berkeleyPost->id]);
	echo My_Cli::success($message);
}

$sfPost = $postModel->fetchRow(
	$postModel->publicSelect()
		->joinLeft(['ps' => 'post_social'], 'ps.post_id=news.id', '')
		->where('created_date >= DATE_SUB(NOW(), INTERVAL 5 HOUR) AND ' .
			'(news.comment > 0 OR news.vote > 0) AND ' .
			'a.city="SF" AND ' .
			'a.state="CA" AND ' .
			'a.country="US" AND ' .
			'ps.id IS NULL')
		->order([$postModel->postScore() . ' DESC', 'news.id DESC'])
);

if ($sfPost != null)
{
	$message = prepareMessageBody($sfPost, $baseUrl, '#SF');
	$twitterMessage = prepareMessageBody($sfPost, $baseUrl, '#SF', 113);
	postToTwitter($twitterMessage, 'sf', $settings);

	$postSocialModel->insert(['post_id' => $sfPost->id]);
	echo My_Cli::success($message);
}

function prepareMessageBody($post, $baseUrl, $hashTag, $limit=null)
{
	$body = trim(preg_replace('/' . My_CommonUtils::$link_regex . '/i', '',
		$post->news));
	$postUrl = $baseUrl . '/post/' . $post->id;

	if ($body === '')
	{
		return $post->owner_name . ' shared a link from ' .
			$hashTag . ': ' . $postUrl;
	}

	if ($limit != null)
	{
		$body = My_StringHelper::stringLimit($body, $limit, '...');
	}

	return $body . ' ' . $postUrl . ' ' . $hashTag;
}

function postToTwitter($message, $app, $settings)
{
	$client = new TwitterAPIExchange([
		'oauth_access_token' => $settings['twitter_' . $app . 'Token'],
		'oauth_access_token_secret' => $settings['twitter_' . $app . 'TokenSecret'],
		'consumer_key' => $settings['twitter_' . $app . 'ApiKey'],
		'consumer_secret' => $settings['twitter_' . $app . 'ApiSecret']
	]);
	$response = $client->buildOauth('https://api.twitter.com/1.1/statuses/update.json', 'POST')
	  ->setPostfields(['status' => $message])
	  ->performRequest();
	return $response;
}
