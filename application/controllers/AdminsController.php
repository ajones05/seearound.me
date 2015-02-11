<?php

class AdminsController extends My_Controller_Action_Herespy
{
    
    public function init() {
        
    }
    
    public function indexAction()
    {
		$config = Zend_Registry::get('config_global');
        $userTable = new Application_Model_User();
        $userRow = $userTable->find($this->auth['user_id'])->current(); 
        if($userRow->is_admin == 'false') {
            $this->_redirect(BASE_PATH);
        } 
        $this->view->hideRight = true;
        $response = new stdClass();
        $emailInvites  = new Application_Model_Emailinvites();
        if($this->getRequest()->isPost()) {
           $email = $this->getRequest()->getPost('email');
           $status = $this->getRequest()->getPost('status');
           $select = $emailInvites->select()->where('self_email =?', $email);
           if($row = $emailInvites->fetchRow($select)) {
               if($status == 'approve') {
                    $url = $url = BASE_PATH."index/send-invitation/regType/email/q/".$row->code;
                    $this->to = $row->self_email;
                    $this->subject = "seearound.me Invitation";
                    $this->from = $config->email->from_email . ':' . $config->email->from_name;
                    $this->view->name = $row->self_email;

                    $message = "To join seearound.me, please click the link below!<br />".$url;

                    $this->view->message = "<p align='justify'>$message</p>";
                    $this->view->adminName = "Admin";
                    $this->view->response = "seearound.me";
                    $this->message = $this->view->action("index","general",array());
                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
                    $row->status = '1';
                    $row->save();
                    $response->approve = 'approved';
               } else if($status == 'remove') {
                   $row->delete();
                   $response->remove = 'deleted';
               }
               $response->totalRows = count($emailInvites->returnEmailInvites());
           } else {
               $response->error = 'No record found';
           }
        }
        $page = $this->getRequest()->getParam('page',1);
        $paginator = Zend_Paginator::factory($emailInvites->returnEmailInvites());
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);
        $this->view->users = $this->view->paginator = $paginator;
        if($this->getRequest()->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }
    }
    
}