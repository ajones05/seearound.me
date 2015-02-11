<?php

class ContactsController extends My_Controller_Action_Herespy
{

    public function init()
    {
        
    }

    public function indexAction()
    {
        if($this->auth['latitude'] == "" && $this->auth['longitude']=="") {
            $this->_redirect(BASE_PATH.'home/edit-profile');
        }
        $inviteStatus = new Application_Model_Invitestatus();
        if($inviteStatus = $inviteStatus->getData(array(user_id=>$this->auth['user_id']))) {
            if($inviteStatus->invite_count <= 0) {
                $this->_redirect(BASE_PATH.'contacts/friends-list');
            } else {
                $this->view->inviteStatus = $inviteStatus;
            }
        }
        $this->view->hideRight = true;
    }
    
    public function invitesAction() 
    {
		$config = Zend_Registry::get('config_global');
        $this->view->hideRight = true;
        $inviteStatus = new Application_Model_Invitestatus();
        $newsFactory = new Application_Model_NewsFactory();
        $emailInvites = new Application_Model_Emailinvites();
        $userTable = new Application_Model_User();
        if($inviteStatusData = $inviteStatus->getData(array(user_id=>$this->auth['user_id']))) {
            if($inviteStatusData->invite_count <= 0) {
                $this->_redirect(BASE_PATH.'contacts/friends-list');
            } else {
                $this->view->inviteStatus = $inviteStatusData;
            }
        }
        if($this->request->isPost()) {
            $emails = $this->request->getPost("emails", null);
            $emailMessage = $this->request->getPost("messageText", null);
            $emails = explode(",", $emails);
            $total = (($inviteStatusData->invite_count) >= count($emails))?(count($emails)):($inviteStatusData->invite_count); 
            $name = $this->auth['user_name']; 
            $alreadyUser = 0;
            for ($i=1; $i <= $total; $i++) {
                if($userRow = $userTable->getUsers(array('Email_id' => trim($emails[$i-1])))) {
                    /*
                     * Email to invited user 
                     */
                    $url = BASE_PATH."home/profile/user/".$this->auth['user_id'];
                    $this->to = $emails[$i-1];
                    $this->from = $this->auth['user_email'].':'.$this->auth['user_name'];
                    $this->subject = "seearound.me connect request";
                    $message = $this->auth['user_name']." wants to connect with you on seearound.me. Please visit the link below to view ".$this->auth['user_name']." public profile.<br><a href='$url'>$url</a>";
                    $this->view->name = $userRow->Name;
                    $this->view->message = "<p align='justify'>$message</p>";
                    $this->view->adminPart = "no";
                    $this->view->adminName = "Admin";
                    $this->view->response = "seearound.me";
                    $this->message = $this->view->action("index","general",array());
                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
                    /*
                     * Email to login user 
                     */
                    $url = BASE_PATH."home/profile/user/".$userRow->id;
                    $this->to = $this->auth['user_email'];
					$this->from = $config->email->from_email . ':' . $config->email->from_name;
                    $this->subject = "User already registered";
                    $message = $emails[$i-1]." is already register with seearound.me  Please visit the link below to view ".$userRow->Name." public profile.<br><a href='$url'>$url</a>";
                    $this->view->name = $this->auth['user_name'];
                    $this->view->message = "<p align='justify'>$message</p>";
                    $this->view->adminPart = "no";
                    $this->view->adminName = "Admin";
                    $this->view->response = "seearound.me";
                    $this->message = $this->view->action("index","general",array());
                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
                    $alreadyUser++;
                } else { 
                    $data = array(
                        sender_id => $this->auth['user_id'],
                        receiver_email => trim($emails[$i-1]),
                        code => $newsFactory->generateCode(),
                        created => date('y-m-d H:i:s')
                    ); 
                    $url = BASE_PATH."index/send-invitation/regType/email/q/".$data['code'];
                    $row[] = $emailInvites->createRow($data)->save();
                    $this->to = $emails[$i-1];
                    $this->from = $this->auth['user_email'].':'.$this->auth['user_name'];
                    $this->subject = "seearound.me join request";
                    $message = $emailMessage."<br><br>Please visit the link below to join $name.<br><a href='$url'>$url</a>"; //echo $message; exit;
                    $this->view->name = $emails[$i-1];
                    $this->view->message = "<p align='justify'>$message</p>";
                    $this->view->adminPart = "no";
                    $this->view->adminName = "Admin";
                    $this->view->response = "seearound.me";
                    $this->message = $this->view->action("index","general",array());
                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
                    $inviteStatusData->invite_count = $inviteStatusData->invite_count-1;
                    $inviteStatusData->save();  
                }               
                
            }
            $this->view->inviteStatus = $inviteStatus->getData(array(user_id=>$this->auth['user_id']));
            $this->view->success = count($row);
            $this->view->already = $alreadyUser;
        }
        
    }

    public function checkfbstatusAction() 
    {
        $response = new stdClass();
        $tableUser = new Application_Model_User;
        $tableFriends = new Application_Model_Friends;
        $tableFbFriends = new Application_Model_Fbtempusers;
        $tableAddress = new Application_Model_Address;
        if($this->_request->isPost()) {
            $nwId = $this->_request->getPost("network_id", null);
            $data = array(
                "Network_id" => $nwId
            );
            $resultRow = $tableUser->getUsers($data);
            if(count($resultRow) > 0) {
                $select = $tableFbFriends->select()
                    ->where("reciever_nw_id =?", $resultRow->Network_id)
                    ->where("sender_id =?", $this->auth['user_id']);
                if($fbFriebds = $tableFbFriends->fetchRow($select)) {
                    $response->data = $fbFriebds->toArray();
                    $response->count = count($fbFriebds);
                    $response->type = "facebook";
                } else {
                    $select = $tableAddress->select()
                            ->where("user_id =?", $resultRow->id);
                    if($addressRow = $tableAddress->fetchRow($select)) {
                        $response->address = $addressRow->toArray();
                    }
                    $select = $tableFriends->select()
                        ->where("friends.sender_id = ".$this->auth['user_id']." AND friends.reciever_id = ".$resultRow->id)
                        ->orWhere("friends.sender_id = ".$resultRow->id." AND friends.reciever_id = ".$this->auth['user_id']);
                    if($friends = $tableFriends->fetchRow($select)) {                        
                        $response->data = $friends->toArray();
                        $response->count = count($fbFriebds);
                        $response->type = "herespy";
                    } else {
                        $response->data = $resultRow->toArray();
                        $response->count = 0;
                        $response->type = "follow";
                    }
                }
            }else {
                $select = $tableFbFriends->select()
                    ->where("reciever_nw_id =?", $nwId)
                    ->where("sender_id =?", $this->auth['user_id']);
                if($fbFriebds = $tableFbFriends->fetchRow($select)) {
                    $response->data = $fbFriebds->toArray();
                    $response->count = count($fbFriebds);
                    $response->type = "facebook";
                } else {
                    $response->count = 0;
                    $response->type = "blank";
                }
                
            }
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->response = $response;
        }
    }
    
    public function inviteAction() 
    { 
        $response = new stdClass();
        $tableUser = new Application_Model_User(); 
        $tableFbFriends = new Application_Model_Fbtempusers(); 
        $inviteStatus = new Application_Model_Invitestatus();
        if($this->_request->isPost()) {
            if($inviteRow = $inviteStatus->getData(array(user_id => $this->auth['user_id']))) {
                if($inviteRow->invite_count > 0) {
                    $data = array(
                        "sender_id" => $this->auth['user_id'],
                        "reciever_nw_id" => $this->_request->getPost("network_id", null),
                        "full_name" => $this->_request->getPost("name", null)
                    );
                    $resultData = $tableFbFriends->invite($data);
                    if(count($resultData) > 0) {
                        $response->data = $resultData;
                    }
                    $inviteRow->invite_count = $inviteRow->invite_count-1;
                    $inviteRow->save();
                } else {
                    $response->errors = "Sorry! you can not send this invitation";
                }
            } else {
                $response->errors = "Sorry! you can not send this invitation";
            }
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->data = $resultRows;
        }
    }
    
    public function followAction() 
    {
        $response = new stdClass();
        $tableUser = new Application_Model_User;
        $tableFriends = new Application_Model_Friends;
        if($this->_request->isPost()) {
            $nwId = $this->_request->getPost("network_id", null);
            $reciewerRow = $tableUser->getUsers(array("Network_id"=>$nwId));
            $mailValues = $tableUser->recordForEmail($this->auth['user_id'], $reciewerRow->id);
            $url = BASE_PATH."home/profile/user/".$this->auth['user_id'];
            $this->to = $mailValues->recieverEmail;
            $this->from = $this->auth['user_email'].':'.$this->auth['user_name'];
            $this->view->name = $mailValues->recieverName;
            $this->subject = "seearound.me connect request";
            $data = array(
                "reciever_id" => $reciewerRow->id,
                "sender_id" => $this->auth['user_id'],
                "source" => "connect",
                "cdate" => date("Y-m-d H:i:s"),
                "udate" => date("Y-m-d H:i:s")
            );
            $resultData = $tableFriends->invite($data);
            if(count($resultData) > 0) {
                $resultData['reciever_nw_id'] = $nwId;
            }
            $response->data = $resultData;
            $message = $this->auth['user_name']." wants to connect with you on seearound.me. Please visit the link below to view ".$this->auth['user_name']." public profile.<br><a href='$url'>$url</a>";
            
            $this->view->message = "<p align='justify'>$message</p>";
            $this->view->adminPart = "no";
            $this->view->adminName = "Admin";
            $this->view->response = "seearound.me";
            $this->message = $this->view->action("index","general",array());
            $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->data = $resultRows;
        }
    }
    
    public function friendsListAction()
    {  
        $this->view->friendListExist =true;
        if($this->auth['latitude'] == "" && $this->auth['longitude']=="") {
            $this->_redirect(BASE_PATH.'home/edit-profile');
        }
        $response = new stdClass();
        $tableUser = new Application_Model_User();
        $tableFriends = new Application_Model_Friends();
        $inviteStatus = new Application_Model_Invitestatus();
        $limit = 5;
        $page = $this->request->getParam("page", 0);
        $offset = $page*$limit;
        if(isset($this->auth['user_id']) && $this->auth['user_id'] != "") {
            $frlist = $tableFriends->getTotalFriends($this->auth['user_id'], $limit, $offset);
            $more = $tableFriends->getTotalFriends($this->auth['user_id'], $limit, $offset+$limit);
            $this->view->inviteStatus = $inviteStatus->getData(array(user_id=>$this->auth['user_id']));
        } 
        if($this->request->isXmlHttpRequest()) {
            $response->frlist = $frlist->toArray();
            $response->more = count($more);
            $response->page = $page+1;
            die(Zend_Json_Encoder::encode($response));
        } else {
            $this->view->frlist = $frlist;
            $this->view->more = count($more);
            $this->view->page = $page+1;
        }
        
    }
    
    public function makeFriendAction() 
    {
        $sendMail = true;
        $response = new stdClass();
        $tableFriends = new Application_Model_Friends;
        $tableUser = new Application_Model_User;
        if($this->_request->isPost()) {
            $sender = $this->_request->getPost("sender", 0);
            $reciever = $this->_request->getPost("reciever", 0);
            $action = $this->_request->getPost("action", null); 
            $mailValues = $tableUser->recordForEmail($sender, $reciever);
            $select = $tableFriends->select()
                ->where("sender_id =".$sender." AND reciever_id =".$reciever)
                ->orWhere("sender_id =".$reciever." AND reciever_id =".$sender);
            if($row = $tableFriends->fetchRow($select)) {
                if($action == "friend"){
                    $row->status = 1;
                    $row->udate = date('Y-m-d H:i:s');
                    $row->save();
                    $response->done = "yes";
                    $response->type = "friend";
                    $response->data = $row->toArray();
                    $this->to = $mailValues->senderEmail;
                    $this->from = $mailValues->recieverEmail.':'.$mailValues->recieverName;
                    $this->subject = "Friend approval";
                    $message = "$mailValues->recieverName has confirmend your friend request on seearound.me.";
                    $this->view->name = $mailValues->senderName;
                    $this->view->message = "<p align='justify'>$message</p>";
                    $this->view->adminPart = "no";
                    $this->view->adminName = "Admin";
                    $this->view->response = "seearound.me";
                    $this->message = $this->view->action("index","general",array());
                }else if($action == "unfriend") {
                    $row->status = 2;
                    $row->udate = date('Y-m-d H:i:s');
                    $row->save();
                    $response->done = "yes";
                    $response->type = "unfriend";
                    $response->data = $row->toArray();
                    $sendMail = false;
                }else if($action == "delete") {
                    $row->delete();
                    $response->done = "yes";
                    $response->type = "delete";
                    $mailValues = $tableUser->recordForEmail($sender, $reciever);
                }
            } else {
                $data = array(
                    'sender_id'   => $sender,
                    'reciever_id' => $reciever,
                    "source"      => "herespy",
                    'cdate'       => date('Y-m-d H:i:s'),
                    'udate'       => date('Y-m-d H:i:s')
                ); 
                $row = $tableFriends->createRow($data);
                $row->save();
                $response->data = $row->toArray();
                $response->done = "yes";
                $response->type = "pending";
                $this->subject = "Friend invitation";
                $this->to = $mailValues->recieverEmail;
                $this->from = $mailValues->senderEmail.':'.$mailValues->senderName;
                $this->view->senderName = $mailValues->senderName;
                $this->view->name = $mailValues->recieverName;
                $this->view->adminPart = "no";
                $this->view->adminName = "Admin";
                $this->view->response = "seearound.me";
                $this->message = $this->view->action("friend-invitation","general",array());
            }
        }
        if($sendMail) {
            $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->response = $response;
        }        
    }
    
    public function makeFriendFbAction() 
    {
        $response = new stdClass();
        $tableUser = new Application_Model_User;
        $tableFriends = new Application_Model_Friends;
        $tableFbFriends = new Application_Model_Fbtempusers;
        if($this->_request->isPost()) {
            $sender = $this->_request->getPost("sender", 0);
            $reciever = $this->_request->getPost("reciever", 0);
            $action = $this->_request->getPost("action", null);
            $select = $tableFbFriends->select()
                ->where("sender_id =?", $sender)
                ->where("reciever_nw_id =?", $reciever);
            if($row = $tableFbFriends->fetchRow($select)) { 
                $select = $tableUser->select()
                    ->where("Network_id =?", $row->reciever_nw_id);
                if($userRow = $tableUser->fetchRow($select)) {
                    if($action == "friend"){
                        $db = $tableUser->getAdapter();
                        $db->beginTransaction();
                        try{
                            $select = $tableFriends->select()
                                ->where("sender_id = ".$row->sender_id." AND reciever_id = ".$userRow->id)
                                ->orWhere("sender_id = ".$userRow->id." AND reciever_id = ".$row->sender_id);
                            $friendRows = $tableFriends->fetchAll($select);
                            if(count($friendRows) > 0) { 
                                foreach($friendRows as $friendRow) {
                                    $friendRow->status = 1;
                                    $friendRow->udate = date("Y-m-d H:i:s");
                                    $friendRow->save();
                                }
                                $row->delete();
                            }else { 
                                $data = array(
                                    "sender_id" => $row->sender_id,
                                    "reciever_id" => $userRow->id,
                                    "status" => 1,
                                    "source" => "facebook",
                                    "cdate" => date("Y-m-d H:i:s"),
                                    "udate" => date("Y-m-d H:i:s")
                                );
                                $friendRows = $tableFriends->createRow($data);
                                $friendRows->save();
                                $row->delete();
                            }
                            $response->done = "yes";
                            $response->type = "friend";
                            $response->data = $friendRows->toArray();
                            $db->commit();
                            $db->closeConnection();
                        } catch (Exception $e) {
                            die($e);
                        }
                    }else if($action == "unfriend") {
                        $userRow->status = 2;
                        $userRow->save();
                        $response->done = "yes";
                        $response->type = "unfriend";
                        $response->data = $row->toArray();
                    }else if($action == "delete") {
                        if(count($userRow) > 0) {
                            $userRow->delete();
                        }
                        if(count($row) > 0) {
                            $row->delete();
                        }
                        $response->done = "yes";
                        $response->type = "delete";
                    }
                }
            }
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->response = $response;
        }   
    }
    
    public function friendsNotificationAction()
    {   
        /*
         * Making instance of models class 
         */
        $response = new stdClass();
        $tableFriends = new Application_Model_Friends();
        $messageTable = new Application_Model_Message();
        if($this->request->isPost()) {  
            $data = array(
                'reciever_id' => $this->auth['user_id'],
                'status' => '0'
            );
            $friendRow = $tableFriends->getFriends($data, true);
            $msgRow    = $messageTable->getNoteMessage($this->auth['user_id']);
            if($friendRow) {
                $friendRow = $friendRow->toArray();
            }
            if($msgRow) {
                $msgRow = $msgRow->toArray();
            }
            $response->data = $friendRow;
            $response->total = count($friendRow);
            $response->msg = $msgRow;
            $response->msgTotal = count($msgRow);
            $response->totalFriends = count( $tableFriends->getTotalFriends($this->auth['user_id']));
        }
        die(Zend_Json_Encoder::encode($response));
    }
    
    public function requestsAction() 
    {
        $response = new stdClass();
        $tableFriends = new Application_Model_Friends;
        if($this->request->isPost()) {
            $friendRows = $tableFriends->frendsList($this->auth['user_id'], true);
            $friendRow = $tableFriends->frendsList($this->auth['user_id'], true, false, 5);
            if($friendRow) {
                $friendRow = $friendRow->toArray();
            }
            $response->data = $friendRow;
            $response->total = count($friendRows);
        }
        die(Zend_Json_Encoder::encode($response));
    }
    
    public function allRequestsAction() 
    {
        $tableFriends = new Application_Model_Friends;
        $this->view->data = $friendRows = $tableFriends->frendsList($this->auth['user_id'], true);
        $this->view->total = count($friendRows);
    }
    
    public function allowAction() {
        $response = new stdClass();
        $tableFriends = new Application_Model_Friends;
        $tableUser = new Application_Model_User;
        if($this->request->isPost()) {
            $select = $tableFriends->select()
                ->where('id =?',$this->_request->getPost('id', 0));
            if($row = $tableFriends->fetchRow($select)) {
                $row->status = '1';
                $row->udate = date('Y-m-d H:i:s');
                $row->save();
                $response->data = $row->toArray();
            }
            $data = array(
                'reciever_id' => $this->auth['user_id'],
                'status' => '0'
            );
            
            $mailValues = $tableUser->recordForEmail($row->sender_id, $row->reciever_id);
            $this->to = $mailValues->senderEmail;
            $this->from = $mailValues->recieverEmail.':'.$mailValues->recieverName;
            $this->subject = "Friend request confirmed";
            $message = "$mailValues->recieverName has confirmed your firend request on seearound.me.";
            $this->view->name = $mailValues->senderName;
            $this->view->message = "<p align='justify'>$message</p>";
            $this->view->adminPart = "no";
            $this->view->adminName = "Admin";
            $this->view->response = "seearound.me";
            $this->message = $this->view->action("index","general",array());
            $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
            
            $friendRow = $tableFriends->getFriends($data, true);
            $response->total = count($friendRow);
            $data = array(
                'reciever_id' => $this->auth['user_id'],
                'status' => '1'
            );
            $friendRow = $tableFriends->getFriends($data, true);
            $response->totalFriends = count($tableFriends->getTotalFriends($this->auth['user_id']));
        }
        die(Zend_Json_Encoder::encode($response));
    }
    
    public function denyAction() {
        $response = new stdClass();
        $tableFriends = new Application_Model_Friends;
        $tableUser = new Application_Model_User;
        if($this->request->isPost()) {
            $select = $tableFriends->select()
                ->where('id =?',$this->_request->getPost('id', 0));
            if($row = $tableFriends->fetchRow($select)) {
                $mailValues = $tableUser->recordForEmail($row->sender_id, $row->reciever_id);
                $this->to = $mailValues->senderEmail;
                $this->from = $mailValues->recieverEmail.':'.$mailValues->recieverName;
                $this->subject = "Friend request denyed";
                $message = "$mailValues->recieverName has deny your firend request on seearound.me.";
                $this->view->name = $mailValues->senderName;
                $this->view->message = "<p align='justify'>$message</p>";
                $this->view->adminPart = "no";
                $this->view->adminName = "Admin";
                $this->view->response = "seearound.me";
                $this->message = $this->view->action("index","general",array());
                $this->sendEmail($this->to, $this->from, $this->subject, $this->message); 
                $row->delete();
                $response->data = "yes";
            }
            $data = array(
                'reciever_id' => $this->auth['user_id'],
                'status' => '0'
            );
            
            $friendRow = $tableFriends->getFriends($data, true);
            $response->total = count($friendRow);
        }
        die(Zend_Json_Encoder::encode($response));
    }
    
    public function deleteAction() 
    {
        $response = new stdClass();
        $tableFriends = new Application_Model_Friends;
        $tableUser = new Application_Model_User;
        if($this->_request->isPost()) {
            $id = $this->_request->getPost('user', 0);
            $select = $tableFriends->select()
                    ->where('id =?', $id);
            if($row = $tableFriends->fetchRow($select)) {
                if($row->sender_id == $this->auth['user_id']) {
                    $response->success = "done";                    
                    $row->delete();
                } elseif($row->reciever_id == $this->auth['user_id']) {
                    $response->success = "done";                    
                    $mailValues = $tableUser->recordForEmail($row->sender_id, $row->reciever_id); 
                    $row->delete();
                } else {
                    $response->errors = "Sorry! you can not delete this friend.";
                }
            } else {
                $response->errors = "Unable to delete this friends.";
            }
        }
        die(Zend_Json_Encoder::encode($response));
    }
    
    public function searchAction() {
        $response = new stdClass();
        $newsFactory = new Application_Model_NewsFactory;
        $data = array();
        $resultRows = array();
        if($this->_request->isPost() || $this->_request->isGet()) {
            $searchKey = $this->request->getParam('search', null);
            if($searchKey != null) {
                if(strpos($searchKey, "@")){
                    $data['Email_id'] = $searchKey;
                }else {
                    $data['Name'] = $searchKey;
                }
                $resultRows = $newsFactory->searchUsers($data, true);
                if($this->request->isXmlHttpRequest()) {
                    $response->success = $resultRows->toArray();
                    die(Zend_Json_Encoder::encode($response));
                }else {
                    $this->view->suceess = $resultRows->toArray();
                }
            }else {
                if($this->request->isXmlHttpRequest()) {
                    $response->error = $resultRows;
                    die(Zend_Json_Encoder::encode($response));
                }else {
                    $this->view->error = $resultRows;
                }
            }
        }
    }   
    
}

