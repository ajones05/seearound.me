<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

defined('ROOT_PATH') 
    || define('ROOT_PATH', dirname(dirname(__FILE__)));

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

$_SERVER['HTTP_HOST'] = Zend_Registry::get('config_global')->server->http_host;

$userModel = new Application_Model_User;
$postModel = new Application_Model_News;
$commentModel = new Application_Model_Comments;
$commentUserNotifyModel = new Application_Model_CommentUserNotify;

$limit = 100;

$postStart = 0;
$postQuery = $postModel->select();

do
{
	$posts = $postModel->fetchAll($postQuery->limit($limit, $postStart));

	if (!$posts->count())
	{
		break;
	}

	foreach ($posts as $post)
	{
		if ($post->image == null)
		{
			continue;
		}

		$file = ROOT_PATH . '/uploads/' . $post->image;
		$file320x320 = ROOT_PATH . '/tbnewsimages/' . $post->image;
		$file960x960 = ROOT_PATH . '/newsimages/' . $post->image;

		list($file_width, $file_height) = getimagesize($file);
		list($file320x320_width, $file320x320_height) = getimagesize($file320x320);
		list($file960x960_width, $file960x960_height) = getimagesize($file960x960);

		if ($file960x960_width < 960 && $file960x960_height < 960 && $file960x960_width < $file_width && $file960x960_height < $file_height)
		{
			echo "#" . $post->id . " - " . $post->image . " original (" . $file_width . "x" . $file_height .
				") 320x320 (" . $file320x320_width . "x" . $file320x320_height .
				") 960x960 (" . $file960x960_width . "x" . $file960x960_height . ")\n";
		}
	}

	$postStart += $limit;
}
while($posts->count() >= $limit);
