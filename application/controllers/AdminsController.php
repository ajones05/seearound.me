<?php

class AdminsController extends Zend_Controller_Action
{
    public function indexAction()
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user)) {
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        if ($user->is_admin == 'false') {
            $this->_redirect($this->view->baseUrl('/'));
        }

        $response = new stdClass();
        $emailInvites  = new Application_Model_Emailinvites();

        if ($this->_request->isPost()) {
           $email = $this->_request->getPost('email');
           $status = $this->_request->getPost('status');

			$select = $emailInvites->select()->where('self_email =?', $email);

			if ($row = $emailInvites->fetchRow($select)) {
				if ($status == 'approve') {
					My_Email::send(
						$row->self_email,
						'seearound.me Invitation',
						array(
							'template' => 'admin-invitation',
							'assign' => array('invite' => $row)
						)
					);

					$row->status = '1';
					$row->save();
					$response->approve = 'approved';
				} else if ($status == 'remove') {
					$row->delete();
					$response->remove = 'deleted';
				}

			   $response->totalRows = count($emailInvites->returnEmailInvites());
			} else {
				$response->error = 'No record found';
			}

			$this->_helper->json($response);
		}

		$paginator = Zend_Paginator::factory($emailInvites->returnEmailInvites());
		$paginator->setCurrentPageNumber($this->_request->getParam('page', 1));
		$paginator->setItemCountPerPage(10);

		$this->view->users = $this->view->paginator = $paginator;
		$this->view->hideRight = true;
	}
}
