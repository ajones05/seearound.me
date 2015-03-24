<?php

require_once ROOT_PATH . '/vendor/autoload.php';

class IndexController extends My_Controller_Action_Abstract {



    public function init()

    {
    
       if(count(Zend_Auth::getInstance()->getIdentity()) > 0) {

           $this->_redirect(BASE_PATH."home");

       }

       $this->credit = 5;

    }



    public function indexAction() {
		$config = Zend_Registry::get('config_global');
		$bootstrap = $this->getInvokeArg('bootstrap');
		$userAgent = $bootstrap->getResource('useragent');
		$userAgent->getDevice();

		if ($userAgent->getBrowserType() === 'mobile')
		{
			$this->view->layout()->setLayout('mobile');
			$this->_helper->viewRenderer->setViewSuffix('mobile.html');

			return true;
		}

        $this->view->layout()->setLayout('login');

        $response = new stdClass();

        $request = $this->getRequest();

        $userTable = new Application_Model_User();

        $emailInvites = new Application_Model_Emailinvites();

        $newsFactory = new Application_Model_NewsFactory();



        if (!$this->request->isXmlHttpRequest()) {

            if ($this->_request->getCookie('emailLogin') && $this->_request->getCookie('passwordLogin')) {

                $this->_redirect(BASE_PATH . "index/login/check/yes");
            }
        }

        $errors = array();

        $data = array();

        if ($this->_request->isPost()) {
          
            $userTable->validateData($request, $data, $errors);

            if (empty($errors)) {

                /*  $options = [
                  'cost' => 11,
                  ];
                  $passwordFromPost = $data['Password'];
                  $hash = password_hash($passwordFromPost, PASSWORD_BCRYPT, $options); */

                $data['Password'] = hash('sha256', $data['Password']);
                // $data['Password']  = md5($data['Password']);

                $data['address']  = $data['Location'];

                $data['latitude']  = $request->getParam('RLatitude');

                $data['longitude'] = $request->getParam('RLongitude');

                $data['Conf_code'] = $newsFactory->generateCode();
                $data['regType'] = 'herespy';


                if ($row = $newsFactory->registration($data)) {

                    $this->view->activate_url = BASE_PATH . "index/reg-confirm/id/" . $row->id . "/q/" . $row->Conf_code;
                    // Code to sending mail to reciever

                    $this->to = $row->Email_id;

                    $this->subject = "SeeAround.me Registration";

                    $this->from = $config->email->from_email . ':' . $config->email->from_name;

                    $firstLoginResponseData = $this->firstLoginAction($data);

                    $this->message = $this->view->action("confirmation", "general", array());

                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                    //@mail($to, $subject, $message, $headers);


                    $this->_redirect(BASE_PATH . "home/index");
                    //$this->_redirect(BASE_PATH."index/reg-success/dq/".$row->id);
                } else {

                    $this->view->errors = "errors";
                }
            } else {

                $this->view->errors = $errors;
            }
        }

        if ($this->request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));
        }
    }
    
   
   
    public function firstLoginAction($data) {

        $data = array('email' => $data['Email_id'], 'pass' => $data['Password']);

        $newsFactory = new Application_Model_NewsFactory();

        $loginStatus = new Application_Model_Loginstatus();

        $inviteStatus = new Application_Model_Invitestatus();

        $returnvalue = $newsFactory->loginDetail($data);

        if ((count($returnvalue) > 0)) {

            $response = new stdClass();
            $auth = Zend_Auth::getInstance();

            $loginRow = $loginStatus->setData(array(user_id => $returnvalue->id, login_time => date('Y-m-d H:i:s'), ip_address => $_SERVER['REMOTE_ADDR']));

            $response->error1 = $returnvalue->Status . " " . count($returnvalue);

            $authData['user_id'] = $returnvalue->id;

            $authData['login_id'] = $loginRow->id;

            $authData['is_fb_login'] = false;

            $authData['user_name'] = $returnvalue->Name;

            $authData['user_email'] = $returnvalue->Email_id;

            $authData['latitude'] = $returnvalue->latitude;

            $authData['longitude'] = $returnvalue->longitude;

            $authData['pro_image'] = $returnvalue->Profile_image;

            $authData['address'] = $returnvalue->address;

            $auth->getStorage()->write($authData);

            $response->error = 0;
            $response->redirect = BASE_PATH . "home";
            return $response;
        }
    } 

   public function profileAction(){ 
   
        // action body

   }
    
    
  public function wsRegistrationAction() {
	  $config = Zend_Registry::get('config_global');
        $response = new stdClass();
        $request  = $this->getRequest();
        $userTable    = new Application_Model_User();
        $emailInvites = new Application_Model_Emailinvites();
        $newsFactory  = new Application_Model_NewsFactory();
        $loginStatus = new Application_Model_Loginstatus();
        $inviteStatus = new Application_Model_Invitestatus();
        

          $data = array();
         
           $data['Name'] = $_REQUEST['Name'];
           $data['Email_id'] = $_REQUEST['Email_id'];
           $data['Password'] = md5($_REQUEST['Password']);
           $data['Location'] = $_REQUEST['Location'];
           $data['address'] = $_REQUEST['address'];
           $data['latitude'] = $_REQUEST['latitude'];
           $data['longitude'] = $_REQUEST['longitude'];
           $data['Conf_code'] = '';
           $data['regType'] = 'herespy';
           $data['Status'] = 'active'; 

         
         if($userTable->isUserEmailExist(trim($data['Email_id']))){
                 $emailAlreadyExist = new stdClass();
                 $emailAlreadyExist->status = "SUCCESS";
                 $emailAlreadyExist->message= "User with this Email already exists";
                 $emailAlreadyExist->result = "No Result";
                 echo(json_encode($emailAlreadyExist)); exit;
         }

          if($row = $newsFactory->mobileRegistration($data)){
               $this->to      = $row->Email_id;
               $this->subject = "seearound.me new Registration";
               $this->from = $config->email->from_email . ':' . $config->email->from_name;
               $this->message = 'Thank you for registring with us.';
               $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
          
               $successMessage = array('Registration Successfull');
               $successMessage = json_encode($successMessage);
               if(isset($successMessage)){
                     $data1   = array();
                     $data1['email'] =  $data['Email_id'];
                     $data1['pass']  =  $data['Password'];
                     $getUserId = $newsFactory->getUserId($data1);
                      
                     /*If login succesful then create a token */
                      $token = md5(uniqid($username,true));
                       try{ 
                         $tokenUpdation = $newsFactory->updateToken($token,$getUserId['id']);
                       } catch(Exception $e) {
                         print_r($e);
                       }
                        
                     /*End Token creation */
                      $returnvalue = array();
                      $returnvalue = $newsFactory->loginDetail($data1);
                      if((count($returnvalue) > 0) && ($returnvalue->Status == "active")) {
                      $loginRow = $loginStatus->setData(array(user_id=>$returnvalue->id, login_time=>date('Y-m-d H:i:s'), ip_address=>$_SERVER['REMOTE_ADDR'])); 
                      $returnvalue  = $returnvalue->toArray();
                      $returnvalue['login_id'] =  $loginRow->id;
                     // die(Zend_Json_Encoder::encode($returnvalue));
                      $response = new stdClass();
                      $response->status  = "SUCCESS";
                      $response->message = "Registration Successfull";
                      $response->result  = $returnvalue; 
                      echo(json_encode($response)); exit;
                    
                    } else {
                       /* $failureMessage = array('Not Authenticated');
                          $failureMessage = json_encode($failureMessage);
                          print_r($failureMessage); exit; */
                        $failureMessage = new stdClass();
                        $failureMessage->status = "FAILED";
                        $failureMessage->message= "User Not Authenticated";
                        $failureMessage->result = $returnvalue;
                        echo(json_encode($failureMessage)); exit;
                    }

                }
           } else {
              /* $failureMessage = array('Registration Not Successfull');
                 $failureMessage = json_encode($failureMessage);
                 print_r($failureMessage); exit; */
                 $failureMessage = new stdClass();
                 $failureMessage->status = "FAILED";
                 $failureMessage->message= "Registration Not Successfull";
                 $failureMessage->result = "No Result";
                 echo(json_encode($failureMessage)); exit;
           }
     }

     public function loginAction($typeArray = null) {

        $this->view->layout()->setLayout('login');

        $response = new stdClass();

        $newsFactory = new Application_Model_NewsFactory();

        $auth = Zend_Auth::getInstance();

           if ($this->_getParam('check') == "yes") {

                $data = array('email' => $this->_request->getCookie('emailLogin'), 'pass' => hash('sha256', $this->_request->getCookie('passwordLogin')));
            } else {

                $data = array('email' => $this->_getParam('email'), 'pass' => hash('sha256', $this->_getParam('pass')));
            }

        $loginStatus = new Application_Model_Loginstatus();

        $inviteStatus = new Application_Model_Invitestatus();

        $returnvalue = $newsFactory->loginDetail($data);

        if ((count($returnvalue) > 0) && ($returnvalue->Status == "active")) {

            $loginRow = $loginStatus->setData(array(user_id => $returnvalue->id, login_time => date('Y-m-d H:i:s'), ip_address => $_SERVER['REMOTE_ADDR']));

            $response->error1 = $returnvalue->Status . " " . count($returnvalue);

            $authData['user_id'] = $returnvalue->id;

            $authData['login_id'] = $loginRow->id;

            $authData['is_fb_login'] = false;

            $authData['user_name'] = $returnvalue->Name;

            $authData['user_email'] = $returnvalue->Email_id;

            $authData['latitude'] = $returnvalue->latitude;

            $authData['longitude'] = $returnvalue->longitude;

            $authData['pro_image'] = $returnvalue->Profile_image;

            $authData['address'] = $returnvalue->address;

            $auth->getStorage()->write($authData);

            $response->error = 0;
            

            if (date('D') == "Mon") {

                $loginRows = $loginStatus->sevenDaysOldData($returnvalue->id);

                $inviteCount = floor((count($loginRows)) / ($this->credit));

                if ($inviteStatusRow = $inviteStatus->getData(array('user_id' => $returnvalue->id))) {

                    if (floor(((strtotime(date('Y-m-d H:i:s'))) - (strtotime($inviteStatusRow->updated))) / (24 * 60 * 60)) >= 7) {

                        $inviteStatusRow->invite_count = ($inviteStatusRow->invite_count + $inviteCount);

                        $inviteStatusRow->updated = date('Y-m-d H:i:s');

                        $inviteStatusRow->save();
                    }
                }
            }

            if ($this->_getParam('remember', null)) {

                setcookie("emailLogin", $this->_getParam('email'), time() + 7 * 24 * 60 * 60, '/');

                setcookie("passwordLogin", $this->_getParam('pass'), time() + 7 * 24 * 60 * 60, '/');
            }

                if ($returnUrl != "") {

                    $response->redirect = $returnUrl;
                } else {

                    $response->redirect = BASE_PATH . "home";
                }
        } elseif (count($returnvalue) > 0 && $returnvalue->Status == "inactive") {

            $response->redirect = BASE_PATH . "index/reg-success?id=$returnvalue->id&q=$returnvalue->Conf_code&type=2";

            $response->error = 0;
        } else {

            $response->error = 1;
        }

        if ($this->_request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));
        } else {

            $this->_redirect($response->redirect);
        }
    }

	/**
	 * Facebook login action.
	 *
	 * @return void
	 */
	public function fbLoginAction()
	{
		$config = Zend_Registry::get('config_global');

		Facebook\FacebookSession::setDefaultApplication($config->facebook->app->id, $config->facebook->app->secret);

		$session = (new Facebook\FacebookJavaScriptLoginHelper())->getSession();

		if (!$session)
		{
			throw new Exception('Login error.');
		}

		$me = (new Facebook\FacebookRequest(
		  $session, 'GET', '/me'
		))->execute()->getGraphObject(Facebook\GraphUser::className());

		$email = $me->getEmail();

		if (!$email)
		{
			throw new Exception('Email not activated');
		}

		$user_model = new Application_Model_User;

		$network_id = $me->getId();

		$user = $user_model->findByNetworkId($network_id);

		if (!$user)
		{
			$user = $user_model->findByEmail($email);

			if ($user)
			{
				$user_model->update(
					array('Network_id' => $network_id),
					$user_model->getAdapter()->quoteInto('id =?', $user->id)
				);
			}
			else
			{
				$user = $user_model->createRow(array(
					'Network_id' => $network_id,
					'Name' => $me->getName(),
					'Email_id' => $email,
					'Status' => 'active',
					'Creation_date'=> new Zend_Db_Expr('NOW()'),
					'Update_date' => new Zend_Db_Expr('NOW()')
				));

				$me_picture = (new Facebook\FacebookRequest(
					$session, 'GET', '/me/picture', array('type' => 'square', 'redirect' => false)
				))->execute()->getGraphObject();

				$picture = $me_picture->getProperty('url');

				if ($picture != null)
				{
					$user->Profile_image = $me_picture->getProperty('url');
				}

				$user->save();
				
				Application_Model_Profile::getInstance()->insert(array(
					'user_id' => $user->id,
					'Gender' => ucfirst($me->getGender())
				));

				$geolocation = My_Ip::geolocation();

				Application_Model_Address::getInstance()->insert(array(
					'user_id' => $user->id,
					'latitude' => $geolocation[0],
					'longitude' => $geolocation[1]
				));

				Application_Model_Invitestatus::getInstance()->insert(array(
					'user_id' => $user->id,
					'created' => new Zend_Db_Expr('NOW()'),
					'updated' => new Zend_Db_Expr('NOW()')
				));

				$users = Application_Model_Fbtempusers::getInstance()->findAllByNetworkId($network_id);

				if (count($users))
				{
					$users_model = new Application_Model_Friends;

					foreach($users as $tmp_user)
					{
						$users_model->insert(array(
							'sender_id' => $tmp_user->sender_id,
							'reciever_id' => $user->id,
							'cdate' => new Zend_Db_Expr('NOW()'),
							'udate' => new Zend_Db_Expr('NOW()')
						));

						$tmp_user->delete();
					}
				}
			}
		}

		$status_model = new Application_Model_Loginstatus;

		$loginRow = $status_model->setData(array(
			'user_id' => $user->id,
			'login_time' => new Zend_Db_Expr('NOW()'),
			'ip_address' => $_SERVER['REMOTE_ADDR']
		));

		$auth = Zend_Auth::getInstance();

		if ($auth->hasIdentity())
		{
			$auth->clearIdentity();
		}

		$auth->getStorage()->write(array(
			'user_id' => $user->id,
			'login_id' => $loginRow->id,
			'is_fb_login' => true,
			'user_name' => $user->Name,
			'user_email' => $user->Email_id,
			'latitude' => $user->lat(),
			'longitude' => $user->lng(),
			'pro_image' => $user->Profile_image,
			'address' => $user->address(),
			'network_id' => $user->Network_id
		));

		if (date('D') == "Mon")
		{
			$loginRows = $status_model->sevenDaysOldData($user->id);
			$inviteCount = floor(count($loginRows) / $this->credit);
			$inviteStatusRow = Application_Model_Invitestatus::getInstance()->getData(array('user_id' => $user->id));

			if ($inviteStatusRow && floor((strtotime(date('Y-m-d H:i:s')) - strtotime($inviteStatusRow->updated)) / (24 * 60 * 60)) >= 7)
			{
				$inviteStatusRow->invite_count = $inviteStatusRow->invite_count + $inviteCount;
				$inviteStatusRow->updated = new Zend_Db_Expr('NOW()');
				$inviteStatusRow->save();
			}
		}

		$this->_redirect(BASE_PATH . 'home');
	}

	/**
	 * TODO: move to mobile controller
	 */
	public function wsfbLoginAction(){
        $response = new stdClass();
        if($_REQUEST) {
            $newsFactory = new Application_Model_NewsFactory();
            $loginStatus = new Application_Model_Loginstatus();
            $inviteStatus = new Application_Model_Invitestatus();
            
            $id = $_REQUEST['id'];
            $name = $_REQUEST['name'];
            $email = $_REQUEST['email'];
            $picture = $_REQUEST['picture'];
            $gender = $_REQUEST['gender'];
            $dob = date('Y-m-d H:i:s', strtotime($_REQUEST['dob'])); 
          
            $data = array(
                'Network_id' => $id,
                'Name' => $name,
                'Email_id' => $email,
                'Gender' => $gender,
                'Status' => 'active',
                'Birth_date' => $dob,
                'Profile_image' => $picture,
                'Creation_date'=> date('Y-m-d H:i:s'),
                'Update_date' => date('Y-m-d H:i:s')
            );
            
            $reponseSuccesssFull = false;
            if($row = $newsFactory->fbLogin($data)) {
                 $authData = array();
                 $reponseSuccesssFull = true;
                 $row = $newsFactory->getUser(array("user_data.id" => $row->id));
                 $loginRow = $loginStatus->setData(array(user_id=>$row->id, login_time=>date('Y-m-d H:i:s'), ip_address=>$_SERVER['REMOTE_ADDR']));
                 $authData = array(
                     'is_fb_login' => true,
                     'Activities' => $row->Activities,
                     'Birth_Date' => $row->Birth_date,
                     'Conf_code' => $row->Conf_code,
                     'Creation_date' => $row->Creation_date,
                     'Email_id' => $row->Email_id,
                     'Gender' => $row->Gender,
                     'Name' => $row->Name,
                     'Network_id' => $row->Network_id,
                     'Old_email' => $row->Old_email,
                     'Password' => (isset($row->Password))?$row->Password:'',
                     'Profile_image' => $row->Profile_image,
                     'Status' => $row->Status,
                     'Update_date' => $row->Update_date,
                     'User_id' => $row->User_id,
                     'address' => (isset($row->address))?$row->address:'',
                     'id' =>  $row->id,
                     'latitude' => (isset($row->latitude))?trim($row->latitude):'',
                     'login_id' => $loginRow->id,
                     'longitude' => (isset($row->longitude))?trim($row->longitude):''
                );
                
                /*
                * Calculation for the invites counts for thw login user   
                */
                if(date('D') == "Mon") {
                    $loginRows = $loginStatus->sevenDaysOldData($row->id);
                    $inviteCount = floor((count($loginRows))/($this->credit));
                    if($inviteStatusRow = $inviteStatus->getData(array('user_id'=>$row->id))) {
                        if(floor(((strtotime(date('Y-m-d H:i:s')))-(strtotime($inviteStatusRow->updated)))/(24*60*60)) >= 7) {
                         $inviteStatusRow->invite_count = ($inviteStatusRow->invite_count+$inviteCount);
                         $inviteStatusRow->updated = date('Y-m-d H:i:s');
                         $inviteStatusRow->save();
                        }
                    }  
                }
            
                $response = new stdClass();
                if(isset($reponseSuccesssFull)){
                      $response->status  = "SUCCESS";
                      $response->message = "AUTHENTICATED";
                      $response->result  = $authData;
                       die(Zend_Json_Encoder::encode($response));
                 } else {
                       $response->status  = "FAILED";  
                       $response->message = "Posts rendring failed";
                       $response->result  = $authData; 
                      die(Zend_Json_Encoder::encode($response));
                 }
             }
         }
    }

    public function registerAction()

    {

        // action body

    }

    

    public function regSuccessAction()

    {

        $this->view->layout()->setLayout('login');

    }

    

    public function regConfirmAction()

    {

        $this->view->layout()->setLayout('login');

        if($this->getRequest()->isPost() || $this->getRequest()->isGet()) {

            $newsFactory = new Application_Model_NewsFactory();

            $id = $this->getRequest()->getParam('id', null);

            $code = $this->getRequest()->getParam('q', null);

            if($row = $newsFactory->confirmEmail($id, $code)) {

                $this->view->success = 'Email confirm success';

            }else {

                $this->view->eroors ="Inactive link";

            }

        }	

    }

    

    public function resendAction() 

    {
		$config = Zend_Registry::get('config_global');

        if($this->getRequest()->isPost()) {

            $response = new stdClass();

            $id = $this->getRequest()->getPost('id', null);

            $newsFactory = new Application_Model_NewsFactory();

            if($id) {

                if($row = $newsFactory->resend($id)) {

                    $url = BASE_PATH."index/reg-confirm?id=".$row->id."&q=".$row->Conf_code;

                    $this->to = $row->Email_id;

                    $this->subject = "Re-send activation link";

                    $this->from = $config->email->from_email . ':' . $config->email->from_name;

                    $message = "Hi ".$row['Name']."\n\n\n\n Your new activation link is : <a href='$url'>$url</a>\n\n\n\n Admin\nwww.seearound.me";

                    $this->view->name = $row->Name;

                    $this->view->message = "<p align='justify'>Your new activation link is : $url</p>";

                    $this->view->adminName = "Admin";

                    $this->view->response = "seearound.me";

                    $this->message = $this->view->action("index","general",array());

                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                    //@mail($to, $subject, $message, $headers);

                    $response->success = "ok";

                }else {

                    $response->errors = "error";

                }		

            } 

        }

        die(Zend_Json_Encoder::encode($response));

    }

    

    public function forgotAction()

    {
		$config = Zend_Registry::get('config_global');

        $this->view->layout()->setLayout('login');

        $tableUser = new Application_Model_User;

        $newsFactory = new Application_Model_NewsFactory();

        if($this->_request->isPost()) {

            if($email = $this->_request->getPost("email", null)) {

                if($row = $tableUser->getUsers(array("Email_id" => $email))) {

                    $row->Status    = "inactive";

                    $row->Conf_code = $newsFactory->generateCode();

                    $row->save();

                    $this->to   = $row->Email_id;

                    $this->from = $config->email->from_email . ':' . $config->email->from_name;

                    $this->view->forgot_url  = BASE_PATH."index/change-password/pc/yes/em/".urlencode($row->Email_id)."/cd/".urlencode($row->Conf_code);

                    $this->subject = "Forgot Password";

                    $this->view->name      = $row->Name;

                    $this->view->adminName = "Admin";

                    $this->view->response  = "seearound.me";

                    $this->message         = $this->view->action("forgot-password", "general", array());

                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                    //@mail($to, $subject, $message, $headers); 

                    $this->view->forgotsuccess = "done";

                } else {

                    $this->view->forgoterror = "Sorry! No account found with that email address."; 

                } 

            } else {

                $this->view->forgoterror = "Please enter email id."; 

            }

        }

    }

    

    public function changePasswordAction() 

    {

        $this->view->layout()->setLayout('login');

        if(isset($this->auth['user_id'])) {

            $this->_redirect(BASE_PATH);    

        }

        $erorrs = array();

        $this->view->email = $email = $this->request->getParam("em", null);

        $this->view->code = $code = $this->request->getParam("cd", null);

        if($this->_request->isPost()) { 

            $tableUser = new Application_Model_User;

            $this->view->email = $email = $this->request->getPost("email", null);

            $this->view->code = $code   = $this->request->getPost("code", null);

            $password = $this->request->getPost("password", null);

            $repassword = $this->request->getPost("re-password", null);

            //echo strlen($password), strlen($repassword); exit;

            if($password == "") { 

                $this->view->pass = "Palese enter new password";

            }else if($password != $repassword) {  

                $this->view->pass = "New password and re-password not matched";

            }else { 

                $select = $tableUser->select()

                    ->where('Email_id =?', $email)

                    ->where('Conf_code =?', $code);

                if($row = $tableUser->fetchRow($select)) {

                    $insData = array(

                        'Password'  => md5($password),

                        'Conf_code' => new Zend_Db_Expr("NULL"),

                        'Status'    => "active"

                    );

                    $row->setFromArray($insData);

                    $row->save();

                    $this->view->success = "Done";

                }else { 

                   $this->view->inactive = "This is an inactive link"; 

                }    

            }

        }

    }

        

    public function sendInvitationAction()

    {

        $this->view->layout()->setLayout('login');

        $request = $this->getRequest();

        $userTable = new Application_Model_User();

        $newsFactory = new Application_Model_NewsFactory();

        $emailInvites = new Application_Model_Emailinvites();

        $errors = array();

        $data = array();

        $this->view->country = $newsFactory->countriesList();

        $this->view->states = $newsFactory->stateList();    

        

        if($this->_request->isPost()) {

            $usertable->validateData($request, $data, $errors); 

            if(empty($errors)) {

                $email = $data['Email_id'];

                $code = $newsFactory->generateCode();

                $invitationData = array(

                    'sender_id'     => new Zend_Db_Expr("NULL"),

                    'receiver_email'=>  new Zend_Db_Expr("NULL"),

                    'code'          => $code,

                    'self_email'    => $email,

                    'created'       => date('Y-m-d H:i:s')

                );	

                $is_Saved = $emailInvites->saveInvitationInfo(array('self_email'=>$email),$invitationData);

                if($is_Saved){

                    // Code to sending mail to reciever

               

                    $invitation_message = "invitation sent";

                    $response->success = $invitation_message;

                } else{

                    $invitation_message = "An invitation has been sent already on this email."; 

                    $response->errors = $invitation_message;

                }

            } else {

                //$this->view->errors = $errors;

                if(($errors['Email_id']=="This information is required.")){

                    $errors['Email_id'] ="Invalid email entered.";

                }

                $invitation_message = $errors['Email_id'];    

                $response->errors = $invitation_message;

            }		

        }

        

        if($this->getRequest()->isGet() && $this->_request->getParam('q') != '' && $this->_request->getParam('regType') != '') {

            if($emailRow = $emailInvites->getData(array('code'=> $this->_request->getParam('q')))) {

                $this->view->regEmail = ($emailRow->receiver_email)?($emailRow->receiver_email):($emailRow->self_email);

            }                      

        } else {

            if($this->_request->getParam('regCode')) {

                

            } else {

                $this->_redirect(BASE_PATH);

                $this->_redirect(BASE_PATH);

            }

        }

        

    }

    

    function sendinvitesSuccessAction()

    {

        $this->view->success = true;

        $this->view->layout()->setLayout('login');

    }



    

}
