<?php



class MessageController extends My_Controller_Action_Herespy

{



    public function init()

    {

        $this->view->hideRight = true;

    }



    public function indexAction()

    {
        $messageTable = new Application_Model_Message();

        $newsFactory = new Application_Model_NewsFactory();

        $user_id = $this->auth['user_id'];

        $page = $this->getRequest()->getParam('page',1);

        $paginator = Zend_Paginator::factory($messageTable->getUserData(array('receiver_id' => $user_id),true));

        $paginator->setCurrentPageNumber($page);

        $paginator->setItemCountPerPage(14);

        $this->view->inbox = $this->view->paginator = $paginator;

        $user_data = $newsFactory->getUserData($user_id);

        $user_pro = $newsFactory->getUserProfileData($user_id);

		

        $address_data = $newsFactory->getUserAddress($user_id);

        $this->view->address_data = $address_data;

			

        $this->view->user_data = $user_data;

        $this->view->user_name = $user_data->Name;

       	$this->view->latitude = $this->auth['latitude'];

       	$this->view->longitude = $this->auth['longitude'];

        $this->view->userImage = $user_data->Profile_image;

        $this->view->user_pro = $user_pro; 
         
        $this->view->currentPage = 'Message';
        $senders = array();
        foreach($paginator as $inboxData){
            $sender = $newsFactory->getUserData($inboxData->sender_id);
            $senders[$inboxData->sender_id]=$sender->Profile_image;
        }
        $this->view->senders = $senders;

              

    }

    

    public function sendsAction()

    { 

        $messageTable = new Application_Model_Message();

        $newsFactory = new Application_Model_NewsFactory();

        $user_id = $this->auth['user_id']; 

        $page = $this->getRequest()->getParam('page',1); 

        $paginator = Zend_Paginator::factory($messageTable->getUserData(array('sender_id' => $user_id))); 

        $paginator->setCurrentPageNumber($page);

        $paginator->setItemCountPerPage(14);

        $this->view->inbox = $this->view->paginator = $paginator;

        $user_data = $newsFactory->getUserData($user_id);

        $user_pro = $newsFactory->getUserProfileData($user_id);



        $address_data = $newsFactory->getUserAddress($user_id);

        $this->view->address_data = $address_data;



        $this->view->user_data = $user_data;

        $this->view->user_name = $user_data->Name;

        $this->view->latitude = $this->auth['latitude'];

        $this->view->longitude = $this->auth['longitude'];

        $this->view->userImage = $user_data->Profile_image;

        $this->view->user_pro = $user_pro;  

    }

    

    public function viewedAction()

    {

        $response = new stdClass();
        

        if($this->getRequest()->isPost()) {

            $messageTable = new Application_Model_Message();

            $messageReplyTable = new Application_Model_MessageReply();
            
            $newsFactory = new Application_Model_NewsFactory();
            
            $rowId = $this->getRequest()->getPost('id', null);

            $result = $messageTable->viewed($rowId, $this->auth['user_id']);

            if(count($result) > 0) {

                $response->inboxData = $result->toArray();

                $response->replyData = $messageReplyTable->replyWithUserData(array("message_reply.message_id"=>$rowId))->toArray();

                $response->replyDataTotal = count($messageReplyTable->replyWithUserData(array("message_reply.message_id"=>$rowId), true));

                $response->success = "done";
                
                $receiver_id = $response->inboxData[receiver_id];
                $receiver_data = $newsFactory->getUserData($receiver_id);
                $receiver_image = $receiver_data->Profile_image;
                $user_image = $newsFactory->getUserData($this->auth['user_id'])->Profile_image;
                $response->receiver_image=$receiver_image;
                $response->user_image = $user_image;
                
                $replyData = $response->replyData;

                foreach($replyData as $key => $areply){
                    $rsender_id = $areply['sender_id'];

                    $rs_data = $newsFactory->getUserData($rsender_id);
                    $rs_image = $rs_data->Profile_image;

                    $replyData[$key]['sender_image']=$rs_image;
                }

                $response->replyData = $replyData;
            }else {

                $response->errors = "error";

            }

        }

        die(Zend_Json_Encoder::encode($response));

    }

    

    public function replyViewedAction()

    {

        $response = new stdClass();

        if($this->getRequest()->isPost()) {

            $messageReplyTable = new Application_Model_MessageReply();

            $rowId = $this->getRequest()->getPost('id', null);

            $response->result = $messageReplyTable->replyViewed($rowId, $this->auth['user_id']);

            

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

        /*

         * Creating objects of model class 

         */

        $response = new stdClass();

        $messageTable = new Application_Model_Message();

        $messageReplyTable = new Application_Model_MessageReply();

        if($this->request->isPost()) {

            $data = array(

                message_id  => $this->getRequest()->getPost('id', null),

                sender_id   => $this->auth['user_id'],

                receiver_id => $this->getRequest()->getPost('user_id', null),

                reply_text  => $this->getRequest()->getPost('message', null),

                created     => date('Y-m-d H:i:s')

            );

            if($messageRow = $messageReplyTable->createRow($data)) {

                $messageRow->save();

                $response->replyData = $messageReplyTable->replyWithUserData(array("message_reply.id"=>$messageRow->id))->toArray();

                $response->replyDataTotal = count($messageReplyTable->replyWithUserData(array("message_reply.message_id"=>$data['message_id']), true));

                $row = $messageTable->find($this->getRequest()->getPost('id', null))->current();

                if($this->auth['user_id'] == $row->sender_id) {

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

        

        /*

         * Identify request type 

         */

        if($this->request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));

        }

    }

    

    public function showAllReplyAction()

    {

        $messageReplyTable = new Application_Model_MessageReply();

        if($this->request->isPost()) {

            $response->replyData = $messageReplyTable->replyWithUserData(array("message_reply.message_id"=>$this->getRequest()->getPost('id', 0)), true)->toArray();

            $response->replyDataTotal = count($response->replyData);

        }

        

        /*

         * Identify request type 

         */

        if($this->request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));

        }

    }



}



