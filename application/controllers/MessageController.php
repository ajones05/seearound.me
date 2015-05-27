<?php

class MessageController extends Zend_Controller_Action
{
	public function init()
	{
		$this->view->hideRight = true;
	}

	public function indexAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        $messageTable = new Application_Model_Message;

        $paginator = Zend_Paginator::factory($messageTable->getUserData(array('receiver_id' => $user->id),true));
        $paginator->setCurrentPageNumber($this->_request->getParam('page', 1));
        $paginator->setItemCountPerPage(14);

		$this->view->user = $user;
        $this->view->paginator = $paginator;
		$this->view->currentPage = 'Message';

		$this->view->headScript()->appendFile($this->view->baseUrl('www/scripts/messageindex.js?' .
			Zend_Registry::get('config_global')->mediaversion));
	}

	public function sendsAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$this->view->user = $user;

        $messageTable = new Application_Model_Message;

        $paginator = Zend_Paginator::factory($messageTable->getUserData(array('sender_id' => $user->id)));
        $paginator->setCurrentPageNumber($this->_request->getParam('page', 1));
        $paginator->setItemCountPerPage(14);

        $this->view->paginator = $paginator;
		$this->view->currentPage = 'Message';

		$this->view->headScript()->appendFile($this->view->baseUrl('www/scripts/messageindex.js?' .
			Zend_Registry::get('config_global')->mediaversion));
	}

	public function viewedAction()
	{
        try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

            $messageTable = new Application_Model_Message;
            $messageReplyTable = new Application_Model_MessageReply;
            $newsFactory = new Application_Model_NewsFactory;

			// TODO: validate
            $rowId = $this->_request->getPost('id', 131);
            $result = $messageTable->viewed($rowId, $user->id);

			if (!count($result))
			{
				// TODO: error details
				throw new RuntimeException('...', -1);
			}

			$response = array(
				"inboxData" => $result->toArray(),
				"replyData" => $messageReplyTable->replyWithUserData(array("message_reply.message_id"=>$rowId))->toArray(),
				"replyDataTotal" => count($messageReplyTable->replyWithUserData(array("message_reply.message_id"=>$rowId), true)),
				"user_image" => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
				"success" => "done"
			);

			foreach ($response["replyData"] as &$row)
			{
				$sender = Application_Model_User::findById($row['sender_id']);
				$row['sender_image'] = $sender->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'));
			}
        }
		catch (Exception $e)
		{
			// TODO: error details
			$response = array("errors" => "error");
		}

        $this->_helper->json($response);
    }

    public function replyViewedAction()
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        $response = new stdClass();

        if($this->getRequest()->isPost()) {

            $messageReplyTable = new Application_Model_MessageReply();

            $rowId = $this->getRequest()->getPost('id', null);

            $response->result = $messageReplyTable->replyViewed($rowId, $user->id);

            

        }

        die(Zend_Json_Encoder::encode($response));

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
			
			$subject = $this->_request->getPost('subject');

			if (My_Validate::emptyString($subject))
			{
				throw new RuntimeException('Incorrect subject value', -1);
			}
			
			$message = $this->_request->getPost('message');

			if (My_Validate::emptyString($message))
			{
				throw new RuntimeException('Incorrect message value', -1);
			}

			(new Application_Model_Message)->insert(array(
				'sender_id' => $user->id,
				'receiver_id' => $receiver->id,
				'subject' => $subject,
				'message' => $message,
				'created' => new Zend_Db_Expr('NOW()'),
				'updated' => new Zend_Db_Expr('NOW()'),
				'is_deleted' => 'false',
				'is_valid' => 'true',
				'sender_read' => 'true',
				'reciever_read' => 'false',
			));

			My_Email::send(
				array($receiver->Name => $receiver->Email_id),
				$subject,
				array(
					'template' => 'message-notification',
					'assign' => array(
						'sender' => $user,
						'receiver' => $receiver,
						'subject' => $subject,
						'message' => $message
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

    public function replyAction()
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        /*

         * Creating objects of model class 

         */

        $response = new stdClass();

        $messageTable = new Application_Model_Message();

        $messageReplyTable = new Application_Model_MessageReply();

        if($this->_request->isPost()) {

            $data = array(
                "message_id" => $this->_request->getPost('id', null),
                "sender_id" => $user->id,
                "receiver_id" => $this->_request->getPost('user_id', null),
                "reply_text" => $this->_request->getPost('message', null),
                "created" => date('Y-m-d H:i:s')
            );

            if($messageRow = $messageReplyTable->createRow($data)) {

                $messageRow->save();

                $response->replyData = $messageReplyTable->replyWithUserData(array("message_reply.id"=>$messageRow->id))->toArray();

                $response->replyDataTotal = count($messageReplyTable->replyWithUserData(array("message_reply.message_id"=>$data['message_id']), true));

                $row = $messageTable->find($this->getRequest()->getPost('id', null))->current();

                if($user->id == $row->sender_id) {

                    $row->reciever_read = 'false';

                } else {

                    $row->sender_read = 'false';

                }

                $row->reply_to = $row->sender_id;

                $row->updated = date('Y-m-d H:i:s');

                $row->save();

            } else {

                $response->errors = "Sorry! reply can not be send.";

            }

        }

        if($this->_request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));

        }
    }

	public function showAllReplyAction()
	{

        $messageReplyTable = new Application_Model_MessageReply();

        if($this->_request->isPost()) {

            $response->replyData = $messageReplyTable->replyWithUserData(array("message_reply.message_id"=>$this->getRequest()->getPost('id', 0)), true)->toArray();

            $response->replyDataTotal = count($response->replyData);

        }

        if($this->_request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));

        }
	}
}
