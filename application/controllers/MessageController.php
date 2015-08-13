<?php

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
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$paginator = Zend_Paginator::factory(
			(new Application_Model_Message)->publicSelect()
				->where('(receiver_id =?', $user->id)
				->orWhere('reply_to =?)', $user->id)
				->order(implode(',', array(
					'IF(reciever_read = "false" AND receiver_id = ' . $user->id . ', 1, 0) DESC',
					'created DESC'
				)))
		);
		$paginator->setCurrentPageNumber($this->_request->getParam('page', 1));
		$paginator->setItemCountPerPage(14);

		$this->view->user = $user;
        $this->view->paginator = $paginator;
		$this->view->hideRight = true;

		$this->view->headScript()->appendFile($this->view->baseUrl('www/scripts/messageindex.js?' .
			Zend_Registry::get('config_global')->mediaversion));
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
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$paginator = Zend_Paginator::factory(
			(new Application_Model_Message)->publicSelect()
				->where('sender_id =?', $user->id)
				->order('updated DESC')
		);
		$paginator->setCurrentPageNumber($this->_request->getParam('page', 1));
		$paginator->setItemCountPerPage(14);

		$this->view->user = $user;
        $this->view->paginator = $paginator;
		$this->view->hideRight = true;

		$this->view->headScript()->appendFile($this->view->baseUrl('www/scripts/messageindex.js?' .
			Zend_Registry::get('config_global')->mediaversion));
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
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			if (!(new Application_Model_Message)->checkId($this->_request->getPost('id'), $message) ||
				$message->receiver_id != $user->id && $message->sender_id != $user->id)
			{
				throw new RuntimeException('Incorrect message ID', -1);
			}

			$model = new Application_Model_MessageReply;

			$response = array(
				"status" => 1,
				"total" => $model->getCountByMessageId($message->id)
			);

			$start = $this->_request->getPost('start', 0);
			$reply_messages = $model->findAllByMessageId($message->id, $start ? 14 : 5, $start);

			if (count($reply_messages))
			{
				foreach ($reply_messages as $reply_message)
				{
					$sender = $reply_message->findDependentRowset('Application_Model_User', 'ReplySender')->current();

					$response["reply"][] = array(
						'receiver_id' => $reply_message->receiver_id,
						'receiver_read' => $reply_message->receiver_read,
						'reply_text' => $reply_message->reply_text,
						'created' => date('F j \a\t h:ia', strtotime($reply_message->created)),
						'sender' => array(
							'name' => $sender->Name,
							'image' => $sender->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
						)
					);
				}
			}

			if (!$start)
			{
				if ($message->receiver_id == $user->id)
				{
					$message->reciever_read = 'true';
				}
				else
				{
					$message->sender_read = 'true';
				}

				$message->save();

				(new Application_Model_MessageReply)->update(
					array('receiver_read' => 'true'),
					implode(' AND ', array(
						'message_id = ' . $message->id,
						'receiver_id = ' . $user->id,
						'receiver_read = "false"'
					))
				);

				$response["message"] = array(
					"sender_id" => $message->sender_id,
					"receiver_id" => $message->receiver_id
				);
			}
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

			if (!$auth || !Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $receiver))
			{
				throw new RuntimeException('Incorrect receiver ID', -1);
			}

			$form = new Application_Form_Message;

			if (!$form->isValid($this->_request->getPost()))
			{
				throw new RuntimeException('Validate error', -1);
			}

			$data = $form->getValues();
			$data['sender_id'] = $user->id;
			$data['receiver_id'] = $receiver->id;

			$message = (new Application_Model_Message)->save($data);

			My_Email::send(
				array($receiver->Name => $receiver->Email_id),
				$message->subject,
				array(
					'template' => 'message-notification',
					'assign' => array(
						'sender' => $user,
						'receiver' => $receiver,
						'subject' => $message->subject,
						'message' => $message->message
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
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			if (!(new Application_Model_Message)->checkId($this->_request->getPost('id'), $message) ||
				$message->receiver_id != $user->id && $message->sender_id != $user->id)
			{
				throw new RuntimeException('Incorrect message ID', -1);
			}

			$reply_message = (new Application_Model_MessageReply)->createRow(array(
				"message_id" => $message->id,
				"sender_id" => $user->id,
				"receiver_id" => $user->id == $message->sender_id ? $message->receiver_id : $message->sender_id,
				"reply_text" => $this->_request->getPost('message', null),
				'receiver_read' => 'false',
				"created" => date('Y-m-d H:i:s')
			));

			$reply_message->save();

			if ($user->id == $message->sender_id)
			{
				$message->reciever_read = 'false';
			}
			else
			{
				$message->sender_read = 'false';
			}

			$message->reply_to = $message->sender_id;
			$message->updated = date('Y-m-d H:i:s');
			$message->save();

			$response = array(
				'status' => 1,
				'message' => array(
					'receiver_id' => $reply_message->receiver_id,
					'receiver_read' => $reply_message->receiver_read,
					'reply_text' => $reply_message->reply_text,
					'created' => $reply_message->created,
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
