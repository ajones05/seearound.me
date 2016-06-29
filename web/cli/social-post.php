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

$postModel = new Application_Model_News;
$postSocialModel = new Application_Model_PostSocial;

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
	$postSocialModel->insert(['post_id' => $oaklandPost->id]);
	echo My_Cli::success(prepareContent($oaklandPost, '#Oakland'));
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
	$postSocialModel->insert(['post_id' => $berkeleyPost->id]);
	echo My_Cli::success(prepareContent($berkeleyPost, '#Berkeley'));
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
	$postSocialModel->insert(['post_id' => $sfPost->id]);
	echo My_Cli::success(prepareContent($sfPost, '#sf'));
}

function prepareContent($post, $hashtag)
{
	return My_StringHelper::stringLimit($hashtag . ' ' . $post->news, 117, '...') .
		' http://www.seearound.me/post/' . $post->id;
}
