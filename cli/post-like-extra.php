<?php
defined('ROOT_PATH')
    || define('ROOT_PATH', dirname(dirname(__FILE__)));

defined('ROOT_PATH_WEB') ||
	define('ROOT_PATH_WEB', ROOT_PATH . '/web');

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(ROOT_PATH . '/application'));

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ?
    getenv('APPLICATION_ENV') : 'production'));

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

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

$voteModel = new Application_Model_Voting;
$postModel = new Application_Model_News;

$query = $postModel->select()->setIntegrityCheck(false)
	->from('news', ['news.id', 'news.vote'])
	->where('news.isdeleted=0')
	->join(['pl' => 'votings'], 'pl.news_id=news.id', '')
	->where('pl.user_id IS NOT NULL AND '.
		'pl.created_at>DATE_SUB(NOW(), INTERVAL 2 HOUR)')
	->group('news.id');

$limit = 100;
$start = 0;

do
{
	$posts = $postModel->fetchAll($query->limit($limit, $start));

	if (($count = $posts->count()) == 0)
	{
		break;
	}

	foreach ($posts as $post)
	{
		$voteCount = rand(2, 5);

		for ($i = 1; $i <= $voteCount; $i++)
		{
			$voteModel->insert([
				'vote' => 1,
				'bot_id' => mt_rand(0, Application_Model_Voting::$botNamesCount),
				'news_id' => $post->id,
				'updated_at' => new Zend_Db_Expr('NOW()'),
				'active' => 1
			]);

			$post->vote++;
		}

		$postModel->update(['vote' => $post->vote], 'id=' . $post->id);

		echo "POST #" . $post->id . " added " . $voteCount . " extra bot vote(s)\n";
	}

	$start += $limit;
}
while ($count >= $limit);
