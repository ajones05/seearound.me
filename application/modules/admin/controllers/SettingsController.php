<?php
use Respect\Validation\Validator as v;

/**
 * Admin settings controller class.
 */
class Admin_SettingsController extends Zend_Controller_Action
{
	/**
	 * Initialize object
	 *
	 * @return void
	 */
	public function init()
	{
		$this->user = Application_Model_User::getAuth();

		if ($this->user == null)
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

		if (empty($this->user['is_admin']))
		{
			$this->_redirect('/');
		}

		$this->view->user = $this->user;
		$this->view->layoutOpts = [
			'search' => [
				'action' => 'admin/settings',
				'placeholder' => 'Search Settings...'
			]
		];

		$this->view->layout()->setLayout('bootstrap');
	}

	/**
	 * List settings action
	 *
	 * @return void
	 */
  public function indexAction()
  {
		$page = $this->_request->getParam('page', 1);

		if (!v::intVal()->min(1)->validate($page))
		{
			throw new RuntimeException('Incorrect page value: ' .
				var_export($page, true));
		}
		
		$keywords = $this->_request->getParam('keywords');

		if (!v::optional(v::stringType())->validate($keywords))
		{
			throw new RuntimeException('Incorrect keywords value: ' .
				var_export($keywords, true));
		}

		$model = new Admin_Model_Setting;
		$query = $model->select();

		if (trim($keywords) !== '')
		{
			$query->where('(name LIKE ?', '%' . $keywords .'%');
			$query->orWhere('description LIKE ?', '%' . $keywords .'%');
			$query->orWhere('value LIKE ?)', '%' . $keywords .'%');
		}

		$paginator = Zend_Paginator::factory($query);
		$paginator->setCurrentPageNumber($page);
		$paginator->setItemCountPerPage(20);

    $this->view->paginator = $paginator;
    $this->view->pages = $paginator->getPages();
		$this->view->layoutOpts['search']['keywords'] = $keywords;
  }

	/**
	 * Edit setting action
	 *
	 * @return void
	 */
  public function editAction()
  {
		$id = $this->_request->getParam('id');

		if (!v::optional(v::intVal())->validate($id))
		{
			throw new RuntimeException('Setting ID cannot be blank');
		}

		$settingModel = new Admin_Model_Setting;
		$settingForm = new Admin_Form_Setting;

		if ($id != null)
		{
			$setting = $settingModel->findById($id);

			if ($setting === null)
			{
				throw new RuntimeException('Incorrect setting ID');
			}

			$this->view->setting = $setting;
		}
		else
		{
			$settingForm->getElement('name')->addValidator(
				new Zend_Validate_Db_NoRecordExists('setting', 'name'));
		}

		if ($this->_request->isPost())
		{
			$data = $this->_request->getPost();

			if ($settingForm->isValid($data))
			{
				$saveData = [
					'name' => $data['name'],
					'value' => $data['value'],
					'description' => $data['description'],
					'updated_at' => new Zend_Db_Expr('NOW()')
				];

				if ($id == null)
				{
					$saveData['created_at'] = new Zend_Db_Expr('NOW()');
					$settingModel->insert($saveData);
				}
				else
				{
					$settingModel->update($saveData, 'id=' . $id);
				}

				Zend_Registry::get('cache')->remove('settings');
				$this->_redirect('admin/settings');
			}
		}
		elseif ($id != null)
		{
			$settingForm->setDefaults([
				'name' => $setting->name,
				'value' => $setting->value,
				'description' => $setting->description
			]);
		}

		$this->view->form = $settingForm;
		$this->view->headScript('file', My_Layout::assetUrl(
				'bower_components/jquery-validation/dist/jquery.validate.min.js'));
  }

	/**
	 * Delete setting action
	 *
	 * @return void
	 */
  public function deleteAction()
  {
		$id = $this->_request->getParam('id');

		if (!v::intVal()->validate($id))
		{
			throw new RuntimeException('Setting ID cannot be blank');
		}

		$setting = (new Admin_Model_Setting)->findById($id);

		if ($setting === null)
		{
			throw new RuntimeException('Incorrect setting ID');
		}

		$setting->delete();

		Zend_Registry::get('cache')->remove('settings');

		$this->_redirect('admin/settings');
  }
}
