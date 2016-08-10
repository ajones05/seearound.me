<?php
use Respect\Validation\Validator as v;

/**
 * Admins controller class.
 * Handles admins actions.
 */
class AdminsController extends Zend_Controller_Action
{
	/**
	 * Index action.
	 */
	public function indexAction()
	{
		$user = Application_Model_User::getAuth();

		if ($user == null || !$user->is_admin)
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

		$inviteModel = new Application_Model_Emailinvites;

		if ($this->_request->isPost())
		{
			try
			{
				$email = $this->_request->getPost('email');

				if (!v::email()->validate($email))
				{
					throw new RuntimeException('Incorrect email value: ' .
						var_export($email, true));
				}

				$status = $this->_request->getPost('status');

				if (!v::stringType()->oneOf(v::equals('approve'),v::equals('remove'))
					->validate($status))
				{
					throw new RuntimeException('Incorrect status value: ' .
						var_export($status, true));
				}

				$invite = $inviteModel->findByEmail($email);

				if ($invite == null)
				{
					throw new RuntimeException('No record found');
				}

				if ($status == 'approve')
				{
						My_Email::send(
							$invite->self_email,
							'seearound.me Invitation',
							[
								'template' => 'admin-invitation',
								'assign' => ['invite' => $invite]
							]
						);

					$inviteModel->update(['status' => 1], 'id=' . $invite->id);
				}
				else
				{
					$invite->delete();
				}

				$response = [
					'status' => 1,
					'total' => $inviteModel->getInvitesCount()
				];
			}
			catch (Exception $e)
			{
				$response = [
					'status' => 0,
					'message' => $e instanceof RuntimeException ? $e->getMessage() :
						'Internal Server Error'
				];
			}

			$this->_helper->json($response);
		}

		$page = $this->_request->getPost('page', 1);

		if (!v::intVal()->validate($page))
		{
			throw new RuntimeException('Incorrect page value: ' .
				var_export($page, true));
		}

		$paginator = Zend_Paginator::factory($inviteModel->select()
			->where('status=?', 0));
		$paginator->setCurrentPageNumber($page);
		$paginator->setItemCountPerPage(10);

		$this->view->paginator = $paginator;
		$this->view->hideRight = true;
	}
}
