<?php



class IndexController extends My_Controller_Action_Abstract {



    public function init()

    {
    
       if(count(Zend_Auth::getInstance()->getIdentity()) > 0) {

           $this->_redirect(BASE_PATH."home");

       }

       $this->credit = 5;

    }



    public function indexAction() {
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

                    $this->from = 'admin@herespy.com:Admin';

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
        /* $data = array();   
            $data['Name'] = $this->_request->getParam('Name');
            $data['Email_id'] = $this->_request->getParam('Email_id');
            $data['Password'] = md5($this->_request->getParam('Password'));
            $data['Location'] = $this->_request->getParam('Location');
            $data['address'] = $this->_request->getParam('address');
            $data['latitude'] = $this->_request->getParam('latitude');
            $data['longitude'] = $this->_request->getParam('longitude');
            $data['Conf_code'] = '';
            $data['regType'] = 'herespy';
            $data['Status'] = 'active'; */
         
        /*   $data = array();   
            $data['Name'] = 'Sakshi';
            $data['Email_id'] = 'sakshi@gmail.com';
            $data['Password'] = md5(123);
            $data['Location'] = 'Greater Noida UP';
            $data['address'] = 'Gretaer Noida UP';
            $data['latitude'] = 28.4654539;
            $data['longitude'] = 77.51101930000004;
            $data['Conf_code'] = '';
            $data['regType'] = 'herespy';
            $data['Status'] = 'active'; */
        
          if($row = $newsFactory->mobileRegistration($data)){
               $this->to      = $row->Email_id;
               $this->subject = "Here Spy new Registration";
               $this->from    = 'admin@herespy.com:Admin';  
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



   public function wsLoginAction() {
        $response = new stdClass();
        $request  = $this->getRequest();
        $newsFactory = new Application_Model_NewsFactory();
        $loginStatus = new Application_Model_Loginstatus();
        $inviteStatus = new Application_Model_Invitestatus();
        $username = $_REQUEST['email'];
        $password = md5($_REQUEST['password']); 
        $data   = array();
        $data['email'] =  $username;
        $data['pass']  = $password;
         $getUserId = $newsFactory->getUserId($data);
        /*If login succesful then create a token */
          $token = md5(uniqid($username,true));
           try{ 
             $tokenUpdation = $newsFactory->updateToken($token,$getUserId['id']);
           } catch(Exception $e) {
             print_r($e);
           }
            
        /*End Token creation */
      
        $returnvalue = array();
        $returnvalue = $newsFactory->wsloginDetail($data);
    
         if((count($returnvalue) > 0) && ($returnvalue->Status == "active")) {
             $loginRow = $loginStatus->setData(array(user_id=>$returnvalue->id, login_time=>date('Y-m-d H:i:s'), ip_address=>$_SERVER['REMOTE_ADDR'])); 
            
              /** Calculation for the invites counts for the login user **/
            if(date('D') == "Mon") {
                $loginRows = $loginStatus->sevenDaysOldData($returnvalue->id);
                $inviteCount = floor((count($loginRows))/($this->credit));
                if($inviteStatusRow = $inviteStatus->getData(array('user_id'=>$returnvalue->id))) {
                    if(floor(((strtotime(date('Y-m-d H:i:s')))-(strtotime($inviteStatusRow->updated)))/(24*60*60)) >= 7) {
                        $inviteStatusRow->invite_count = ($inviteStatusRow->invite_count+$inviteCount);
                        $inviteStatusRow->updated = date('Y-m-d H:i:s');
                        $inviteStatusRow->save();
                    }
                } 
             }
             $returnvalue  = $returnvalue->toArray();
             $returnvalue['login_id'] =  $loginRow->id;
             $response->status  = "SUCCESS";
             $response->message = "AUTHENTICATED";
             $response->result  = $returnvalue;
             die(Zend_Json_Encoder::encode($response));
        
        } else {
             $returnvalue = '';
             $response->status  = "FAILED";  
             $response->message = "NOT AUTHENTICATED";
             $response->result  = $returnvalue; 
             die(Zend_Json_Encoder::encode($response));
        }
    }



    public function loginAction123() {
     
        $this->view->layout()->setLayout('login');

        $response = new stdClass();

        $auth = Zend_Auth::getInstance();

        if($this->_getParam('check') == "yes") {

            $data = array('email' => $this->_request->getCookie('emailLogin'), 'pass' => md5($this->_request->getCookie('passwordLogin')));

        }else {

            $data = array('email' => $this->_getParam('email'), 'pass' => md5($this->_getParam('pass')));

        }

     

        $newsFactory = new Application_Model_NewsFactory();

        $loginStatus = new Application_Model_Loginstatus();

        $inviteStatus = new Application_Model_Invitestatus();

        $returnvalue = $newsFactory->loginDetail($data);
        // echo "<pre>"; print_r($returnvalue); exit;
        if((count($returnvalue) > 0) && ($returnvalue->Status == "active")) {

            /*

             * Setting login time in to the login status table

             */

            $loginRow = $loginStatus->setData(array(user_id=>$returnvalue->id, login_time=>date('Y-m-d H:i:s'), ip_address=>$_SERVER['REMOTE_ADDR']));

            

            /*

             * Setting user authantication values in to the auth 

             */

            $response->error1 = $returnvalue->Status." ".count($returnvalue);

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

            

            /*

             * Calculation for the invites counts for the login user   

             */

            if(date('D') == "Mon") {

                $loginRows = $loginStatus->sevenDaysOldData($returnvalue->id);

                $inviteCount = floor((count($loginRows))/($this->credit));

                if($inviteStatusRow = $inviteStatus->getData(array('user_id'=>$returnvalue->id))) {

                    if(floor(((strtotime(date('Y-m-d H:i:s')))-(strtotime($inviteStatusRow->updated)))/(24*60*60)) >= 7) {

                        $inviteStatusRow->invite_count = ($inviteStatusRow->invite_count+$inviteCount);

                        $inviteStatusRow->updated = date('Y-m-d H:i:s');

                        $inviteStatusRow->save();

                    }

                } 

            }

            

            /*

             * Setting user loging details in to cookies 

             */

            if($this->_getParam('remember', null)) {

                setcookie("emailLogin", $this->_getParam('email'), time() +7*24*60*60, '/');

                setcookie("passwordLogin", $this->_getParam('pass'), time() +7*24*60*60, '/');

            }

            if($returnvalue->latitude != "" && $returnvalue->longitude != '') {

                if($returnUrl != "") {

                    $response->redirect = $returnUrl;

                } else {

                    $response->redirect = BASE_PATH."home";

                }

            }else {

                $response->redirect = BASE_PATH."home/edit-profile";

            }



        }elseif(count($returnvalue)> 0 && $returnvalue->Status =="inactive"){

            $response->redirect = BASE_PATH."index/reg-success?id=$returnvalue->id&q=$returnvalue->Conf_code&type=2";

            $response->error = 0;

        }else {

            $response->error = 1;

        }

        if($this->_request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));

        }else {

            $this->_redirect($response->redirect);

        }

    }
    
    
    
     public function loginAction($typeArray = null) {

        $this->view->layout()->setLayout('login');

        $response = new stdClass();

        $newsFactory = new Application_Model_NewsFactory();

        $auth = Zend_Auth::getInstance();

        $data = array('email' => $this->_getParam('email'));
        
        $returnvalueForMD5Passwords = $newsFactory->loginDetail($data,'tocheck');

     if(isset($returnvalueForMD5Passwords->id)){   
        if ($returnvalueForMD5Passwords->id > 243) {


            if ($this->_getParam('check') == "yes") {

                $data = array('email' => $this->_request->getCookie('emailLogin'), 'pass' => hash('sha256', $this->_request->getCookie('passwordLogin')));
            } else {

                $data = array('email' => $this->_getParam('email'), 'pass' => hash('sha256', $this->_getParam('pass')));
            }
        } else {

            if ($this->_getParam('check') == "yes") {

                $data = array('email' => $this->_request->getCookie('emailLogin'), 'pass' => md5($this->_request->getCookie('passwordLogin')));
            } else {

                $data = array('email' => $this->_getParam('email'), 'pass' => md5($this->_getParam('pass')));
            }
        }
        
      } else {
       
           if ($this->_getParam('check') == "yes") {

                $data = array('email' => $this->_request->getCookie('emailLogin'), 'pass' => hash('sha256', $this->_request->getCookie('passwordLogin')));
            } else {

                $data = array('email' => $this->_getParam('email'), 'pass' => hash('sha256', $this->_getParam('pass')));
            }
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

            if ($returnvalue->latitude != "" && $returnvalue->longitude != '') {

                if ($returnUrl != "") {

                    $response->redirect = $returnUrl;
                } else {

                    $response->redirect = BASE_PATH . "home";
                }
            } else {

                $response->redirect = BASE_PATH . "home/edit-profile";
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
    
    
    
   
    public function fbLoginAction(){
        $response = new stdClass();
        if($this->getRequest()->isPost()) {
            $auth = Zend_Auth::getInstance();
            $newsFactory = new Application_Model_NewsFactory();
            $loginStatus = new Application_Model_Loginstatus();
            $inviteStatus = new Application_Model_Invitestatus();
            $id = $this->getRequest()->getPost('id', null);
            $name = $this->getRequest()->getPost('name', null);
            $email = $this->getRequest()->getPost('email', null);
            $picture = $this->getRequest()->getPost('picture', null);
            $gender = $this->getRequest()->getPost('gender', null);
            $dob = date('Y-m-d H:i:s', strtotime($this->getRequest()->getPost('dob', null)));
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

            if($row = $newsFactory->fbLogin($data)) {
                $row = $newsFactory->getUser(array("user_data.id" => $row->id));
                $loginRow = $loginStatus->setData(array(user_id=>$row->id, login_time=>date('Y-m-d H:i:s'), ip_address=>$_SERVER['REMOTE_ADDR']));
                $authData['user_id'] = $row->id;
                $authData['login_id'] = $loginRow->id;
                $authData['is_fb_login'] = ture;
                $authData['user_name'] = $row->Name;
                $authData['user_email'] = $row->Email_id;
                $authData['latitude'] = (isset($row->latitude))?$row->latitude:'';
                $authData['longitude'] = (isset($row->longitude))?$row->longitude:'';
                $authData['pro_image'] = $row->Profile_image;
                $authData['address'] = (isset($row->address))?$row->address:'';
                $authData['network_id'] = $row->Network_id;
                if($auth->hasIdentity()) {
                   $auth->clearIdentity();
                 }
                $auth->getStorage()->write($authData);	
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
            }
            $response->error = 0;
            if((isset($row->longitude) && $row->longitude!="" )&& (isset($row->latitude)&& $row->latitude!="" )) {
                $response->redirect = BASE_PATH."home";
            }else {
                $response->redirect = BASE_PATH."home/edit-profile";
            }
        }
        die(Zend_Json_Encoder::encode($response));
  }
  

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

        if($this->getRequest()->isPost()) {

            $response = new stdClass();

            $id = $this->getRequest()->getPost('id', null);

            $newsFactory = new Application_Model_NewsFactory();

            if($id) {

                if($row = $newsFactory->resend($id)) {

                    $url = BASE_PATH."index/reg-confirm?id=".$row->id."&q=".$row->Conf_code;

                    $this->to = $row->Email_id;

                    $this->subject = "Re-send activation link";

                    $this->from = 'admin@herespy.com';

                    $message = "Hi ".$row['Name']."\n\n\n\n Your new activation link is : <a href='$url'>$url</a>\n\n\n\n Admin\nwww.herespy.com";

                    $this->view->name = $row->Name;

                    $this->view->message = "<p align='justify'>Your new activation link is : $url</p>";

                    $this->view->adminName = "Admin";

                    $this->view->response = "Here Spy";

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

                    $this->from = "admin@herespy.com";

                    $this->view->forgot_url  = BASE_PATH."index/change-password/pc/yes/em/".urlencode($row->Email_id)."/cd/".urlencode($row->Conf_code);

                    $this->subject = "Forgot Password";

                    $this->view->name      = $row->Name;

                    $this->view->adminName = "Admin";

                    $this->view->response  = "Here Spy";

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