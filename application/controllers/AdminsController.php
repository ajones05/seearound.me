<?php

class AdminsController extends Zend_Controller_Action
{
    public function indexAction()
    {
		$user = Application_Model_User::getAuth();

		if ($user == null || !$user->is_admin)
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

        $response = new stdClass();
        $emailInvites  = new Application_Model_Emailinvites();

        if ($this->_request->isPost()) {
           $email = $this->_request->getPost('email');
           $status = $this->_request->getPost('status');

			$select = $emailInvites->select()->where('self_email =?', $email);

			$settings = (new Application_Model_Setting)->findValuesByName([
				'email_fromName', 'email_fromAddress'
			]);

			if ($row = $emailInvites->fetchRow($select)) {
				if ($status == 'approve') {
					My_Email::send(
						$row->self_email,
						'seearound.me Invitation',
						array(
							'template' => 'admin-invitation',
							'assign' => array('invite' => $row),
							'settings' => $settings
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
