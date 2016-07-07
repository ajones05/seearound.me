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

$config = Zend_Registry::get('config_global');
$facebookApi = My_Facebook::getInstance();

$postModel = new Application_Model_News;
$postSocialModel = new Application_Model_PostSocial;
$baseUrl = $config->server->request_scheme . '://' .
	$config->server->http_host;

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
	$messagePrefix = $baseUrl . '/post/' . $oaklandPost->id . ' #Oakland';

	$twitterMessage = My_StringHelper::stringLimit($oaklandPost->news, 108, '...') .
		' ' . $messagePrefix;
	postToTwitter($twitterMessage, 'oakland', $config);

	$message = $oaklandPost->news . ' ' . $messagePrefix;
	$facebookApi->post(
		'/' . $config->facebook->oakland->pageId . '/feed',
		['message' => $message],
		$config->facebook->oakland->accessToken
	);

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
	$message = My_StringHelper::stringLimit($berkeleyPost->news, 107, '...') .
		' ' . $baseUrl . '/post/' . $berkeleyPost->id . ' #Berkeley';
	postToTwitter($message, 'berkeley', $config);
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
	$message = My_StringHelper::stringLimit($sfPost->news, 113, '...') .
		' ' . $baseUrl . '/post/' . $sfPost->id . ' #SF';
	postToTwitter($message, 'sf', $config);
	$postSocialModel->insert(['post_id' => $sfPost->id]);
	echo My_Cli::success($message);
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
