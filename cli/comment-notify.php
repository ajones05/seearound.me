<?php
defined('ROOT_PATH')
    || define('ROOT_PATH', dirname(dirname(__FILE__)));

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

$settings =  Application_Model_Setting::getInstance();
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

$baseUrl = $settings['server_requestScheme'] . '://' .
	$settings['server_httpHost'] . '/';

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
			->from(['u' => 'user_data'], 'u.*')
			->joinLeft(['c' => 'comments'], 'u.id=c.user_id', '')
			->where('((c.isdeleted=0 AND c.news_id=?)', $post['id'])
			->orWhere('u.id=?)', $post['user_id'])
			->joinLeft(['cs' => 'comment_subscription'], '(cs.user_id=u.id AND '.
				'(cs.post_id IS NULL OR cs.post_id=' . $post['id'] . '))', '')
			->where('cs.id IS NULL')
			->group('u.id')
			->order('c.created_at ASC');

		do
		{
			$users = $userModel->fetchAll($userQuery->limit($limit, $userStart));

			if (!$users->count())
			{
				break;
			}

			foreach ($users as $user)
			{
				$commentQuery = $commentModel->publicSelect()
					->where('c.notify=0 AND ' .
						'c.news_id=' . $post->id . ' AND ' .
						'(c.id > IFNULL((SELECT MAX(id) FROM comments c1 WHERE ' .
							'c1.news_id = ' . $post->id . ' AND ' .
							'c1.user_id = ' . $user->id . ' AND ' .
							'c1.isdeleted = 0), 0))')
					->join(['u' => 'user_data'], 'u.id=c.user_id',
						['owner_name' => 'Name'])
					->order('c.created_at DESC');

				$comments = $commentModel->fetchAll($commentQuery->limit(15));

				if ($comments->count())
				{
					$commentUsers = [];

					foreach ($comments as $comment)
					{
						if (!in_array($comment['user_id'], $commentUsers))
						{
							$commentUsers[] = $comment['user_id'];
						}
					}

					$userIdEncoded = base64_encode($user['id']);

					My_Email::send(
						[$user->Name => $user->Email_id],
						$user->id == $post->user_id ?
							'SeeAroundme comment on your post' :
							'SeeAroundme comment on a post you commented on',
						[
							'template' => 'comment-notify-comment',
							'assign' => [
								'post' => $post,
								'user' => $user,
								'users' => $commentUsers,
								'comments' => $comments,
								'unsubscribe' => [[
									'Unsubscribe me from future notifications about this post',
									$baseUrl . 'unsubscribe-post-comments/' .
										$userIdEncoded . '/' . base64_encode($post['id'])
								],[
									'Unsubscribe me from all comment notifications',
									$baseUrl . 'unsubscribe-post-comments/' . $userIdEncoded
								]],
								'opts' => ['baseUrl' => $baseUrl]
							],
							'settings' => $settings
						]
					);

					foreach ($comments as $comment)
					{
						$commentUserNotifyModel->insert([
							'comment_id' => $comment->id,
							'user_id' => $user->id
						]);
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
