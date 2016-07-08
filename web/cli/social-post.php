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
	'server_httpHost'
]);
$config = Zend_Registry::get('config_global');
$facebookApi = My_Facebook::getInstance();

$postModel = new Application_Model_News;
$postSocialModel = new Application_Model_PostSocial;
$baseUrl = $settings['server_requestScheme'] . '://' .
	$settings['server_httpHost'];

$oaklandPost = $postModel->fetchRow(
	$postModel->publicSelect()
		->where('created_date >= DATE_SUB(NOW(), INTERVAL 2 HOUR)')
		->where('(a.city="Oakland" OR a.city="Emeryville" OR a.city="Piedmont")')
		->where('a.state="CA"')
		->where('a.country="US"')
		->joinLeft(['ps' => 'post_social'], 'ps.post_id=news.id', '')
		->where('ps.id IS NULL')
		->order([$postModel->postScore() . ' DESC', 'news.id DESC'])
);

if ($oaklandPost != null)
{
	$message = prepareMessageBody($oaklandPost, $baseUrl, '#Oakland');
	$facebookApi->post(
		'/' . $config->facebook->oakland->pageId . '/feed',
		['message' => $message],
		$config->facebook->oakland->accessToken
	);

	$twitterMessage = prepareMessageBody($oaklandPost, $baseUrl, '#Oakland', 108);
	postToTwitter($twitterMessage, 'oakland', $config);

	$postSocialModel->insert(['post_id' => $oaklandPost->id]);
	echo My_Cli::success($message);
}

$berkeleyPost = $postModel->fetchRow(
	$postModel->publicSelect()
		->where('created_date >= DATE_SUB(NOW(), INTERVAL 2 HOUR)')
		->where('(a.city="Berkeley")')
		->where('a.state="CA"')
		->where('a.country="US"')
		->joinLeft(['ps' => 'post_social'], 'ps.post_id=news.id', '')
		->where('ps.id IS NULL')
		->order([$postModel->postScore() . ' DESC', 'news.id DESC'])
);

if ($berkeleyPost != null)
{
	$message = prepareMessageBody($berkeleyPost, $baseUrl, '#Berkeley');
	$twitterMessage = prepareMessageBody($berkeleyPost, $baseUrl, '#Berkeley', 107);
	postToTwitter($twitterMessage, 'berkeley', $config);

	$postSocialModel->insert(['post_id' => $berkeleyPost->id]);
	echo My_Cli::success($message);
}

$sfPost = $postModel->fetchRow(
	$postModel->publicSelect()
		->where('created_date >= DATE_SUB(NOW(), INTERVAL 2 HOUR)')
		->where('a.city="SF"')
		->where('a.state="CA"')
		->where('a.country="US"')
		->joinLeft(['ps' => 'post_social'], 'ps.post_id=news.id', '')
		->where('ps.id IS NULL')
		->order([$postModel->postScore() . ' DESC', 'news.id DESC'])
);

if ($sfPost != null)
{
	$message = prepareMessageBody($sfPost, $baseUrl, '#SF');
	$twitterMessage = prepareMessageBody($sfPost, $baseUrl, '#SF', 113);
	postToTwitter($twitterMessage, 'sf', $config);

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

function postToTwitter($message, $app, $config)
{
	$client = new TwitterAPIExchange([
		'oauth_access_token' => $config->twitter->{$app}->token,
		'oauth_access_token_secret' => $config->twitter->{$app}->token_secret,
		'consumer_key' => $config->twitter->{$app}->api_key,
		'consumer_secret' => $config->twitter->{$app}->api_secret
	]);
	$response = $client->buildOauth('https://api.twitter.com/1.1/statuses/update.json', 'POST')
	  ->setPostfields(['status' => $message])
	  ->performRequest();
	return $response;
}
