<?php
use Respect\Validation\Validator as v;

/**
 * User controller class.
 * Handles user actions.
 */
class UserController extends Zend_Controller_Action
{
	/**
	 * Posts list action.
	 *
	 * @return void
	 */
	public function autocompleteAction()
	{
		try
		{
			$userModel = new Application_Model_User;
			$user = $userModel->getAuth();

			if ($user == null || !$user->is_admin)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$keywords = $this->_request->getPost('keywords');

			if (!v::stringType()->length(2)->validate($keywords))
			{
				throw new Exception('Incorrect keywords value');
			}

			$result = $userModel->fetchAll(
				$userModel->publicSelect()
					->where('(u.id LIKE ?', '%' . $keywords . '%')
					->orWhere('u.Name LIKE ?', '%' . $keywords . '%')
					->orWhere('u.Email_id LIKE ?', '%' . $keywords . '%')
					->orWhere('CONCAT(u.Name," <",u.Email_id,"> (",u.id,")") LIKE ?)',
						'%' . $keywords . '%')
					->limit(20)
					->group('u.id')
			);

			$response = ['status' => 1];

			if ($result != null)
			{
				foreach ($result as $user)
				{
					$response['data'][] = [
						'label' => $user->Name . ' <' . $user->Email_id .
							'> (' . $user->id . ')',
						'value' => $user->id
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_helper->json($response);
	}
}
