<?php



class MessageController extends My_Controller_Action_Herespy

{



    public function init()

    {

        $this->view->hideRight = true;

    }



    public function indexAction()

    {

        if($this->auth['latitude'] == "" && $this->auth['longitude']=="") {

            $this->_redirect(BASE_PATH.'home/edit-profile');

        } 

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

    

    public function sendAction() { 

        $response = new stdClass();

        $data = array();

        $errors = array();

        if($this->request->isPost()) {

            $messageTable = new Application_Model_Message(); 

            $newsFactory = new Application_Model_NewsFactory();

            $messageTable->validateData($this->getRequest(), $data, $errors); 

            if(empty($errors)) { 

            	$data['user']['sender_id'] = $this->auth['user_id'];

                $data['user']['receiver_id'] = $this->getRequest()->getPost('user_id', null);

                $data['user']['created'] = date('Y-m-d H:i:s');

                $data['user']['updated'] = date('Y-m-d H:i:s');

                $data['user']['is_read'] = 'false';

                $data['user']['is_deleted'] = 'false';

                $data['user']['is_valid'] = 'true';

                $user_data = $newsFactory->getUserData($data['user']['receiver_id']);

                // Code to sending mail to reciever

                

                $this->view->name = $user_data->Name;

                $this->view->message = "<p align='justify'> ".$this->auth['user_name']." has sent you a message on HereSpy.<br><br><b>Subject:</b> " .$data['user']['subject']."<br><br><b>Message:</b> " .$data['user']['message']."<br><br>Please log in to HereSpy to reply to this message:".BASE_PATH." Please do not reply to this email</p>";

                $this->view->adminName = "Admin";

                $this->view->response = "Here Spy";

                

                $this->to = $user_data->Email_id;

                $this->from = 'noreply@herespy.com:HerespyMessage';

                $this->subject = $data['user']['subject'];  

                $this->message = $this->view->action("index","general",array());

                $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                //save data in to data base 

                $result = $messageTable->saveData($data['user']);

                if($result) {

                    $response->success = $result->toArray();    

                }

            }else {

                $response->errors = $errors;

            }

                

        }

        die(Zend_Json_Encoder::encode($response));

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



