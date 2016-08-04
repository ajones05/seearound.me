<?php
use Respect\Validation\Validator as v;

/**
 * Message controller class.
 */
class MessageController extends Zend_Controller_Action
{
	/**
	 * View messages action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$user = Application_Model_User::getAuth();

		if ($user == null)
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

		$page = $this->_request->getPost('page', 1);

		if (!v::intVal()->validate($page))
		{
			throw new RuntimeException('Incorrect page value: ' .
				var_export($page, true));
		}

		$conversationModel = new Application_Model_Conversation;
		$query = $conversationModel->select()->setIntegrityCheck(false)
			->from(['c' => 'conversation'], [
				'c.*',
				'cm1.body',
				'user_name' => 'u.Name',
				'is_read' => 'IFNULL(cm3.is_read,1)'
			])
			->where('(c.to_id=?', $user->id)
			->orWhere('c.from_id=?)', $user->id)
			->joinLeft(['cm1' => 'conversation_message'], '(cm1.conversation_id=c.id AND ' .
				'cm1.is_first=1)', '')
			->joinLeft(['cm3' => 'conversation_message'], '(cm3.conversation_id=c.id AND ' .
				'cm3.is_read=0 AND cm3.to_id=' . $user->id . ')', '')
			->joinLeft(['u' => 'user_data'], 'u.id=c.from_id', '')
			->group('c.id')
			->order(['is_read ASC', 'c.created_at DESC']);

		$paginator = Zend_Paginator::factory($query);
		$paginator->setCurrentPageNumber($page);
		$paginator->setItemCountPerPage(14);

		$this->view->userTimezone = Application_Model_User::getTimezone($user);
		$this->view->paginator = $paginator;
		$this->view->hideRight = true;

		$this->view->headScript()->appendFile(My_Layout::assetUrl('www/scripts/messageindex.js'));
	}

	/**
	 * View send messages action.
	 *
	 * @return void
	 */
	public function sendsAction()
	{
		$user = Application_Model_User::getAuth();

		if ($user == null)
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

		$model = new Application_Model_Conversation;
		$paginator = Zend_Paginator::factory(
			$model->select()
				->setIntegrityCheck(false)
				->from(array('c' => 'conversation'), array(
					'c.id',
					'c.subject',
					'c.created_at',
					'user_name' => 'u.Name',
					'is_read' => 'IFNULL(cm.is_read,1)'
				))
				->where('c.from_id=?', $user->id)
				->joinLeft(array('cm' => 'conversation_message'), '(cm.conversation_id=c.id AND ' .
					'cm.is_read=0 AND cm.from_id=' . $user->id . ')', '')
				->joinLeft(array('u' => 'user_data'), 'u.id=c.to_id', '')
				->group('c.id')
				->order('c.created_at DESC')
		);
		$paginator->setCurrentPageNumber($this->_request->getParam('page', 1));
		$paginator->setItemCountPerPage(14);

		$this->view->userTimezone = Application_Model_User::getTimezone($user);
		$this->view->paginator = $paginator;
		$this->view->hideRight = true;

		$this->view->headScript()->appendFile(My_Layout::assetUrl('www/scripts/messageindex.js'));
	}

	/**
	 * View reply messages action.
	 *
	 * @return void
	 */
	public function viewedAction()
	{
		try
		{
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect conversation ID value: ' .
					var_export($id, true));
			}

			$conversationModel = new Application_Model_Conversation;

			if (!$conversationModel->checkId($id, $conversation))
			{
				throw new RuntimeException('Incorrect conversation ID: ' .
					var_export($id, true));
			}

			if ($conversation->from_id != $user->id && $conversation->to_id != $user->id)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$limit = $start ? 14 : 5;

			$messageModel = new Application_Model_ConversationMessage;
			$query = $messageModel->select()->setIntegrityCheck(false)
				->from(['cm' => 'conversation_message'], [
					'cm.id',
					'cm.to_id',
					'cm.body',
					'cm.is_read',
					'cm.created_at',
					'user_name' => 'u.Name'
				])
				->where('cm.conversation_id=?', $conversation->id)
				->where('cm.is_first<>1')
				->joinLeft(['u' => 'user_data'], 'u.id=cm.from_id', '')
				->order('cm.created_at DESC')
				->limit($limit, $start);

			$messages = $messageModel->fetchAll($query);
			$messagesCount = $messages->count();

			$updateCondition = [];

			if ($conversation->to_id == $user->id)
			{
				$updateCondition[] = '(conversation_id=' . $conversation->id .
					' AND is_first=1)';
			}

			$response = ['status' => 1];

			if ($messagesCount)
			{
				$userTimezone = Application_Model_User::getTimezone($user);
				foreach ($messages as $message)
				{
					$response['reply'][] = [
						'receiver_id' => $message->to_id,
						'receiver_read' => $message->is_read,
						'reply_text' => $message->body,
						'created' => (new DateTime($message->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::OUTPUT),
						'sender' => [
							'name' => $message->user_name,
							'image' => $this->view->baseUrl(
								Application_Model_User::getThumb($message, '55x55',
									['alias' => 'u_']))
						]
					];

					if ($message->to_id == $user->id)
					{
						$updateCondition[] = 'id=' . $message->id;
					}
				}
			}

			if (count($updateCondition))
			{
				$messageModel->update(array('is_read' => 1),
					implode(' OR ', $updateCondition));
			}

			$response['total'] = $messagesCount < $limit ? $messagesCount :
				$messageModel->getReplyCount($conversation->id);
        }
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array(
					'message' => $e instanceof RuntimeException ?
						$e->getMessage() : 'Internal Server Error'
				)
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Send user message action.
	 *
	 * @return void
	 */
	public function sendAction()
	{
		try
		{
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value');
			}

			if (!Application_Model_User::checkId($user_id, $receiver))
			{
				throw new RuntimeException('Incorrect user ID');
			}

			$form = new Application_Form_Message;

			if (!$form->isValid($this->_request->getPost()))
			{
				throw new RuntimeException('Validate error');
			}

			$data = $form->getValues();

			$conversation = (new Application_Model_Conversation)->save(array(
				'from_id' => $user->id,
				'to_id' => $receiver->id,
				'subject' => $data['subject']
			));

			$message = (new Application_Model_ConversationMessage)->save(array(
				'conversation_id' => $conversation->id,
				'from_id' => $user->id,
				'to_id' => $receiver->id,
				'body' => $data['message'],
				'is_first' => 1
			));

			$settings = Application_Model_Setting::getInstance();

			My_Email::send(
				array($receiver->Name => $receiver->Email_id),
				$data['subject'],
				array(
					'template' => 'message-notification',
					'assign' => array(
						'sender' => $user,
						'receiver' => $receiver,
						'subject' => $data['subject'],
						'message' => $data['message']
					),
					'settings' => $settings
				)
			);

			$response = array('status' => 1);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array(
					'message' => $e instanceof RuntimeException ?
						$e->getMessage() : 'Internal Server Error'
				)
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Send reply message action.
	 *
	 * @return void
	 */
	public function replyAction()
	{
		try
		{
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($id, true));
			}

			$conversationModel = new Application_Model_Conversation;

			if (!$conversationModel->checkId($id, $conversation))
			{
				throw new RuntimeException('Incorrect message ID value: ' .
					var_export($id, true));
			}

			if ($conversation->from_id != $user->id &&
				$conversation->to_id != $user->id)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$body = $this->_request->getPost('message');

			if (!v::stringType()->length(1, 65535)->validate($body))
			{
				throw new RuntimeException('Incorrect body value: ' .
					var_export($body, true));
			}

			$messageModel = new Application_Model_ConversationMessage;
			$message = $messageModel->save(array(
				'conversation_id' => $conversation->id,
				'from_id' => $user->id,
				'to_id' => $user->id == $conversation->from_id ? $conversation->to_id :
					$conversation->from_id,
				'body' => $body,
			));

			$createdAt = (new DateTime($message->created_at))
				->setTimezone(Application_Model_User::getTimezone($user));

			$response = [
				'status' => 1,
				'message' => [
					'receiver_id' => $message->to_id,
					'receiver_read' => 0,
					'reply_text' => $body,
					'created' => $createdAt->format(My_Time::OUTPUT),
					'sender' => [
						'name' => $user->Name,
						'image' => $this->view->baseUrl(
							Application_Model_User::getThumb($user, '55x55'))
					]
				]
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_helper->json($response);
	}
}
