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
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

		$config = Zend_Registry::get('config_global');
		$model = new Application_Model_Conversation;

		$paginator = Zend_Paginator::factory(
			$model->select()
				->setIntegrityCheck(false)
				->from(array('c' => 'conversation'), array(
					'c.*',
					'cm1.body',
					'user_name' => 'u.Name',
					'user_image' => 'it.path',
					'is_read' => 'IFNULL(cm3.is_read,1)'
				))
				->where('(c.to_id=?', $user->id)
				->orWhere('c.from_id=?)', $user->id)
				->joinLeft(array('cm1' => 'conversation_message'), '(cm1.conversation_id=c.id AND ' .
					'cm1.is_first=1)', '')
				->joinLeft(array('cm3' => 'conversation_message'), '(cm3.conversation_id=c.id AND ' .
					'cm3.is_read=0 AND cm3.to_id=' . $user->id . ')', '')
				->joinLeft(array('u' => 'user_data'), 'u.id=c.from_id', '')
				->joinLeft(array('ui' => 'user_image'), 'ui.user_id=u.id', '')
				->joinLeft(array('it' => 'image_thumb'), '(it.image_id=IFNULL(ui.image_id,' .
					$config->user->default_image . ') AND ' .
					'it.thumb_width=320 AND it.thumb_height=320)', '')
				->group('c.id')
				->order('is_read ASC')
				->order('c.created_at DESC')
		);

		$paginator->setCurrentPageNumber($this->_request->getParam('page', 1));
		$paginator->setItemCountPerPage(14);

		$this->view->user = $user;
        $this->view->paginator = $paginator;
		$this->view->hideRight = true;

		$this->view->headScript()->appendFile(My_Layout::assetUrl('www/scripts/messageindex.js', $this->view));
	}

	/**
	 * View send messages action.
	 *
	 * @return void
	 */
	public function sendsAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
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

		$this->view->user = $user;
        $this->view->paginator = $paginator;
		$this->view->hideRight = true;

		$this->view->headScript()->appendFile(My_Layout::assetUrl('www/scripts/messageindex.js', $this->view));
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
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('ID cannot be blank');
			}

			$conversationModel = new Application_Model_Conversation;

			if (!$conversationModel->checkId($id, $conversation))
			{
				throw new RuntimeException('Incorrect conversation ID');
			}

			if ($conversation->from_id == $user->id &&
				$conversation->to_id == $user->id)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value');
			}

			$limit = $start ? 14 : 5;

			$config = Zend_Registry::get('config_global');
			$messageModel = new Application_Model_ConversationMessage;
			$messages = $messageModel->fetchAll($messageModel->select()
				->setIntegrityCheck(false)
				->from(array('cm' => 'conversation_message'), array(
					'cm.id',
					'cm.to_id',
					'cm.body',
					'cm.is_read',
					'cm.created_at',
					'user_name' => 'u.Name',
					'user_image' => 'it.path',
				))
				->where('cm.conversation_id=?', $conversation->id)
				->where('cm.is_first<>1')
				->joinLeft(array('u' => 'user_data'), 'u.id=cm.from_id', '')
				->joinLeft(array('ui' => 'user_image'), 'ui.user_id=u.id', '')
				->joinLeft(array('it' => 'image_thumb'), '(it.image_id=IFNULL(ui.image_id,' .
					$config->user->default_image . ') AND ' .
					'it.thumb_width=320 AND it.thumb_height=320)', '')
				->order('cm.created_at DESC')
				->limit($limit, $start)
			);

			$response = array('status' => 1);
			$messagesCount = $messages->count();
			$updateCondition = array();

			if ($conversation->to_id == $user->id)
			{
				$updateCondition[] = '(conversation_id=' . $conversation->id .
					' AND is_first=1)';
			}

			if ($messagesCount)
			{
				foreach ($messages as $message)
				{
					$response['reply'][] = array(
						'receiver_id' => $message->to_id,
						'receiver_read' => $message->is_read,
						'reply_text' => $message->body,
						'created' => date('F j \a\t h:ia', strtotime($message->created_at)),
						'sender' => array(
							'name' => $message->user_name,
							'image' => $this->view->baseUrl($message->user_image)
						)
					);

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
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
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
					)
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
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect ID value');
			}

			$conversationModel = new Application_Model_Conversation;

			if (!$conversationModel->checkId($id, $conversation))
			{
				throw new RuntimeException('Incorrect message ID');
			}

			if ($conversation->from_id != $user->id && $conversation->to_id != $user->id)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$body = $this->_request->getPost('message');

			if (!v::stringType()->validate($body))
			{
				throw new RuntimeException('Incorrect message body value');
			}

			$messageModel = new Application_Model_ConversationMessage;
			$message = $messageModel->save(array(
				'conversation_id' => $conversation->id,
				'from_id' => $user->id,
				'to_id' => $user->id == $conversation->from_id ? $conversation->to_id :
					$conversation->from_id,
				'body' => $body,
			));

			$response = array(
				'status' => 1,
				'message' => array(
					'receiver_id' => $message->to_id,
					'receiver_read' => 0,
					'reply_text' => $body,
					'created' => date('F j \a\t h:ia', strtotime($message->created_at)),
					'sender' => array(
						'name' => $user->Name,
						'image' => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
					)
				)
			);
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
}
