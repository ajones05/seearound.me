<?php
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

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
$_SERVER['HTTP_HOST'] = Zend_Registry::get('config_global')->server->http_host;

$userModel = new Application_Model_User;
$postModel = new Application_Model_News;
$commentModel = new Application_Model_Comments;
$commentUserNotifyModel = new Application_Model_CommentUserNotify;

$limit = 100;

$postStart = 0;
$postQuery = $postModel->publicSelect()->setIntegrityCheck(false)
	->from($postModel, 'news.*')
	->joinLeft('comments', 'news.id = comments.news_id', '')
	->where('comments.isdeleted =?', 0)
	->where('comments.notify =?', 0)
	->group('news.id')
	->order('comments.created_at ASC');

do
{
	$posts = $postModel->fetchAll($postQuery->limit($limit, $postStart));

	if (!$posts->count())
	{
		break;
	}

	foreach ($posts as $post)
	{
		$userStart = 0;
		$userQuery = $userModel->select()->setIntegrityCheck(false)
			->from($userModel, 'user_data.*')
			->joinLeft('comments', 'user_data.id = comments.user_id', '')
			->where('(comments.isdeleted =?', 0)
			->where('comments.news_id =?)', $post->id)
			->orWhere('user_data.id =?', $post->user_id)
			->group('user_data.id')
			->order('comments.created_at ASC');

		do
		{
			$users = $userModel->fetchAll($userQuery->limit($limit, $userStart));

			if (!$users->count())
			{
				break;
			}

			foreach ($users as $user)
			{
				$commentQuery = $commentModel->publicSelect()->setIntegrityCheck(false)
					->from($commentModel, 'comments.*')
					->where('comments.notify =?', 0)
					->where('comments.news_id =?', $post->id)
					->where('comments.id > IFNULL((SELECT MAX(id) FROM comments c ' .
						'WHERE c.news_id = ' . $post->id . ' AND c.user_id = ' . $user->id . ' AND c.isdeleted = 0), 0)')
					->order('comments.created_at DESC');

				$comments = $commentModel->fetchAll($commentQuery->limit(15));

				if ($comments->count())
				{
					My_Email::send(
						array($user->Name => $user->Email_id),
						$user->id == $post->user_id ?
							'SeeAroundme comment on your post' :
							'SeeAroundme comment on a post you commented on',
						array(
							'template' => 'comment-notify-comment',
							'assign' => array(
								'post' => $post,
								'user' => $user,
								'comments' => $comments
							)
						)
					);

					foreach ($comments as $comment)
					{
						$commentUserNotifyModel->insert(array(
							'comment_id' => $comment->id,
							'user_id' => $user->id
						));
					}

					echo $user->Name . "<" . $user->Email_id . "> - " . $comments->count() . "\n";
				}
			}

			$userStart += $limit;
		}
		while($users->count() >= $limit);

		$commentModel->update(array('notify' => 1), array('notify=?' => 0, 'news_id=?' => $post->id));
	}

	$postStart += $limit;
}
while($posts->count() >= $limit);
