<?php

class Application_Model_NewsFactory {
    
    public function getUser($data = array()){
        $userTable = new Application_Model_User;
        if(count($data) > 0) {
            $select = $userTable->select()->setIntegrityCheck(false)
                    ->from("user_data")
                    ->joinLeft("user_profile", "user_profile.user_id = user_data.id", array("public_profile", "Activities", "Looking_for", "Gender"))
                    ->joinLeft("address", "address.user_id = user_data.id" ,array("address", "latitude", "longitude"));
            foreach($data as $index => $value) {
                $select->where($index." =?", $value);
            }
        } 
        return $userTable->fetchRow($select);

    }

    public function totalNewsData(){
        $newsFactory = new Application_Model_NewsFactory();
        $news        = new Application_Model_News();
        $select      = $news->select()->order('created_date DESC');
        $data        = $news->fetchAll($select);
        $i=0; 
        $rowData = array();
        foreach($data as $row){
                $rowData[$i]['time'] = $newsFactory->calculate_time($row['updated_date']);
                $rowData[$i]['news'] = $row['news'];
                $rowData[$i]['id']   = $row['id'];
                $i++;
        }
        return $rowData;
    }
    
    public function generateCode(){
        mt_srand();
        $hash = md5(mt_rand(0, time()));
        return substr($hash, mt_rand(0, 20), 10);
    }

    public function getLastRow(){
        $newsFactory = new Application_Model_NewsFactory();
        $news = new Application_Model_News();
        $select = $news->select()->order('created_date DESC')->limit(1, 10);
        $data = $news->fetchRow($select);
        return $data->id;
    }

    public function addNews($userId,$res, $lat, $lng, $address, $name=null, $type=null, $tmp=null, $size=null){
         $totalLikeCounts = 0;
         $createdDate  = StrToTime (date("Y-m-d H:i:s"));
         $currentDate  = StrToTime (date("Y-m-d H:i:s"));
         $timeDiffernce = ($currentDate-$createdDate);
         $timeDiffernce = $timeDiffernce/3600;
         $numerator =  ($totalLikeCounts+1);
         $demonator = pow(($timeDiffernce+2),1.2);
         $score  =  $numerator/$demonator;   
         $score = number_format($score,5,'.',''); 
         //or $score = 0.43528;
       
        $news = new Application_Model_News();
        //echo json_encode(realpath('.')); exit;
        $path = realpath('.')."/newsimages/";
        $orignalImagePath = realpath('.')."/www/upload/"; 
        $valid_formats = array("jpg", "png", "gif", "bmp", "jpeg","JPG");

        if(strlen($name)) {			
           // list($txt, $ext) = explode(".", $name);
            $txt_1 = explode(".", $name);
            $countLength = count($txt_1);
            $txt = $txt_1[0];
            $ext = $txt_1[$countLength-1];
            /* $name = pathinfo($name, PATHINFO_EXTENSION);
            list($txt, $ext) = $name; */
            if(in_array($ext,$valid_formats)) {
                $actual_image_name = time().substr(str_replace(" ", "_", $txt), 5).".".$ext;
                if(move_uploaded_file($tmp, $orignalImagePath.$actual_image_name)) {
                    $source = $orignalImagePath.$actual_image_name;
                    $destinationThumbnail960 = realpath('.')."/newsimages/".$actual_image_name;
                    /*Orignal*/ //$destinationThumbnail320 = realpath('.')."/tbnnewsimages/".$actual_image_name;                                                        
                    $destinationThumbnail320 = realpath('.')."/tbnewsimages/".$actual_image_name;                                                        
                    $myThumbnail = new My_Thumbnail($source);
                    
                    $myThumbnail->resize(960,960);
                    //$myThumbnail->resize(320,320);
                    $myThumbnail->save($destinationThumbnail960, 60);

                    $myThumbnail = new My_Thumbnail($source);
                    $myThumbnail->resize(320,320);
                    $myThumbnail->save($destinationThumbnail320, 60);

                     if (file_exists($source)) {
                        unlink($source);
                    } 

                    $datas = array(	
                        'user_id' => $userId,
                        'news' => $res,
                        'images' => $actual_image_name,
                        'created_date' => date('Y-m-d H:i'),
                        'updated_date' => date('Y-m-d H:i'),
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'Address' => $address,
                        'score'   => $score
                    );                            
                }
            }       
        } else {
            $datas = array(	
                'user_id' => $userId,
                'news' => $res,
                'created_date' => date('Y-m-d H:i'),
                'updated_date' => date('Y-m-d H:i'),
                'latitude' => $lat,
                'longitude' => $lng,
                'Address' => $address,
                'score'   => $score
            );
        }
        // end for images upload code 
        $row = $news->createRow($datas);
        $row->save();
        return $row->id;
    }
    
    
    
    public function addmobileNews_321($userId,$res, $lat, $lng, $address, $base){
         $totalLikeCounts = 0;
         $createdDate  = StrToTime (date("Y-m-d H:i:s"));
         $currentDate  = StrToTime (date("Y-m-d H:i:s"));
         $timeDiffernce = ($currentDate-$createdDate);
         $timeDiffernce = $timeDiffernce/3600;
         $numerator =  ($totalLikeCounts+1);
         $demonator = pow(($timeDiffernce+2),1.2);
         $score  =  $numerator/$demonator;   
         $score = number_format($score,5,'.',''); 
         $news = new Application_Model_News();
         $orignalImagePath = realpath('.')."/www/upload/"; 
        if(isset($base) && $base != '') {			
                $imageBinary = base64_decode($base);
                header('Content-Type: bitmap; charset=utf-8');
                $path = realpath('.') . "/tbnewsimages/" . date('YmdHs') . ".jpeg";
                $pathExploded = explode("/", $path);
                $countLength = count($pathExploded);
                $actual_image_name = $pathExploded[$countLength-1];
                
                $file = fopen($path, 'wb');
                fwrite($file, $imageBinary);
                fclose($file);  
                
           /*   $source = $path;
                $pathExploded = explode("/", $path);
                $countLength = count($pathExploded);
                $actual_image_name = $pathExploded[$countLength-1];
                $destinationThumbnail960 = realpath('.')."/newsimages/".$actual_image_name;
                $destinationThumbnail320 = realpath('.')."/tbnewsimages/".$actual_image_name;                                                        
                $myThumbnail = new My_Thumbnail($source);
                $myThumbnail->resize(960,960);
                $myThumbnail->save($destinationThumbnail960, 60);
                $myThumbnail = new My_Thumbnail($source);
                $myThumbnail->resize(320,320);
                $myThumbnail->save($destinationThumbnail320, 60);*/
                
                /*    $source = $orignalImagePath.$actual_image_name;
                    $destinationThumbnail960 = realpath('.')."/newsimages/".$actual_image_name;
                                                                        
                    $destinationThumbnail320 = realpath('.')."/tbnewsimages/".$actual_image_name;                                                        
                    $myThumbnail = new My_Thumbnail($source);
                    
                    $myThumbnail->resize(960,960);
                   
                    $myThumbnail->save($destinationThumbnail960, 60);

                    $myThumbnail = new My_Thumbnail($source);
                    $myThumbnail->resize(320,320);
                    $myThumbnail->save($destinationThumbnail320, 60);

                if (file_exists($source)) {
                    unlink($source);
                }  */
                
                $datas = array(	
                    'user_id' => $userId,
                    'news' => $res,
                    'images' => $actual_image_name,
                    'created_date' => date('Y-m-d H:i'),
                    'updated_date' => date('Y-m-d H:i'),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'Address' => $address,
                    'score'   => $score
                );                            
           } else {
             $datas = array (	
                'user_id' => $userId,
                'news' => $res,
                'created_date' => date('Y-m-d H:i'),
                'updated_date' => date('Y-m-d H:i'),
                'latitude' => $lat,
                'longitude' => $lng,
                'Address' => $address,
                'score'   => $score
            );
        }
        // end for images upload code 
        $row = $news->createRow($datas);
        $row->save();
        return $row->id;
    }
    
    
 public function addmobileNews($userId,$res, $lat, $lng, $address, $base){
         $totalLikeCounts = 0;
         $createdDate  = StrToTime (date("Y-m-d H:i:s"));
         $currentDate  = StrToTime (date("Y-m-d H:i:s"));
         $timeDiffernce = ($currentDate-$createdDate);
         $timeDiffernce = $timeDiffernce/3600;
         $numerator =  ($totalLikeCounts+1);
         $demonator = pow(($timeDiffernce+2),1.2);
         $score  =  $numerator/$demonator;   
         $score = number_format($score,5,'.',''); 
         $news = new Application_Model_News();
         //$path = realpath('.')."/newsimages/";
         $orignalImagePath = realpath('.')."/www/upload/";
        if(isset($base) && $base != '') {			
                $imageBinary = base64_decode($base);
                header('Content-Type: bitmap; charset=utf-8');
                $path = realpath('.') . "/newsimages/" . date('YmdHs') . ".png";
                $pathExploded = explode("/", $path);
                $countLength = count($pathExploded);
                $actual_image_name = $pathExploded[$countLength-1];
                $source = $orignalImagePath.$actual_image_name;
                $file = fopen($source, 'wb');
                fwrite($file, $imageBinary);
                fclose($file); 
                $destinationThumbnail960 = realpath('.')."/newsimages/".$actual_image_name;
                $destinationThumbnail320 = realpath('.')."/tbnewsimages/".$actual_image_name;                                                        
                $myThumbnail = new My_Thumbnail($source);
                $myThumbnail->resize(960,960);
                $myThumbnail->save($destinationThumbnail960, 60);
                $myThumbnail = new My_Thumbnail($source);
                $myThumbnail->resize(320,320);
                $myThumbnail->save($destinationThumbnail320, 60);

                if (file_exists($source)) {
                    unlink($source);
                }  
                
                $datas = array(	
                    'user_id' => $userId,
                    'news' => $res,
                    'images' => $actual_image_name,
                    'created_date' => date('Y-m-d H:i'),
                    'updated_date' => date('Y-m-d H:i'),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'Address' => $address,
                    'score'   => $score
                );                            
           } else {
             $datas = array (	
                'user_id' => $userId,
                'news' => $res,
                'created_date' => date('Y-m-d H:i'),
                'updated_date' => date('Y-m-d H:i'),
                'latitude' => $lat,
                'longitude' => $lng,
                'Address' => $address,
                'score'   => $score
            );
        }
        $row = $news->createRow($datas);
        $row->save();
        return $row->id;
    }
    
    
   //For testing whether image is uploading or not on upload >> CODE MAY BE DELETED, DOES NOT DEPEND ON ANY ENTITY     
    public function addmobilebck($base){
         $news = new Application_Model_News();
         $orignalImagePath = realpath('.')."/www/upload/"; 
          if(isset($base) && $base != '') {			
                $imageBinary = base64_decode($base);
                header('Content-Type: bitmap; charset=utf-8');
                $path = realpath('.') . "/newsimages/" . date('YmdHs') . ".jpeg";
                $file = fopen($path, 'wb');
                fwrite($file, $imageBinary);
                fclose($file);  
        }
    }
    


    function optimizeImage($source, $destination, $rwidth, $rheight){
        ini_set('memory_limit', '-1');
        list( $source_image_width, $source_image_height, $source_image_type ) = getimagesize($source);

        switch ( $source_image_type )
        {
        case IMAGETYPE_GIF:
            $source_gd_image = imagecreatefromgif( $source );
            break;

        case IMAGETYPE_JPEG:
            $source_gd_image = imagecreatefromjpeg( $source );
            break;

        case IMAGETYPE_PNG:
            $source_gd_image = imagecreatefrompng( $source );
            break;
        }

        $thumbnail_image_path = $destination;
        $thumbnail_image_width = $rwidth;
        $thumbnail_image_height = $rheight;
        if($source_image_width > $source_image_height) {
            $source_aspect_ratio = $source_image_width / $source_image_height;
            $thumbnail_aspect_ratio = $thumbnail_image_width / $thumbnail_image_height;
        } else { 
            $source_aspect_ratio =  $source_image_height / $source_image_width;
            $thumbnail_aspect_ratio = $thumbnail_image_height / $thumbnail_image_width ;
        }
        if ( $source_image_width <= $thumbnail_image_width && $source_image_height <= $thumbnail_image_height )
        {
            $thumbnail_image_width = $source_image_width;
            $thumbnail_image_height = $source_image_height;
        }
        elseif ($thumbnail_aspect_ratio > $source_aspect_ratio )
        {
            $thumbnail_image_width = ( int ) ( $thumbnail_image_height * $source_aspect_ratio);
        }
        else
        {
            $thumbnail_image_height = ( int ) ( $thumbnail_image_width * $source_aspect_ratio);
        }

        $thumbnail_gd_image = imagecreatetruecolor( $thumbnail_image_width, $thumbnail_image_height );
        imagecopyresampled( $thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, $thumbnail_image_width, $thumbnail_image_height, $source_image_width, $source_image_height );
        imagejpeg( $thumbnail_gd_image, $thumbnail_image_path, 90 );
        //imagedestroy( $source_gd_image );
        imagedestroy( $thumbnail_gd_image );
    }

    public function userlatlong($userId){
        $user = new Application_Model_User();
        $select = $user->select()->setIntegrityCheck(false)
                        ->from('user_data')
        ->joinLeft('address','address.user_id = user_data.id')
        ->where('user_data.id = ?', $userId);
        $data = $user->fetchRow($select);
        return $data;
    }

    function calculate_time($a){
        $b = time();
        $c = strtotime($a);
        $d = $b-$c;
        $minute = 60;
        $hour = $minute * 60;
        $day = $hour * 24;
        $week = $day * 7;

            if($d < 2) return "right now";
            if($d < $minute) return floor($d) . " seconds ago";
            if($d < $minute * 2) return "about 1 minute ago";
            if($d < $hour) return floor($d / $minute) . " minutes ago";
            if($d < $hour * 2) return "about 1 hour ago";
            if($d < $day) return floor($d / $hour) . " hours ago";
            if($d > $day && $d < $day * 2) return "yesterday";
            if($d < $day * 365) return floor($d / $day) . " days ago";
        return "over a year ago";

    }

    function stateList(){
        $states = new Application_Model_States();
        $sel = $states->select();
        $stateList = $states->fetchAll($sel);
        return $stateList;
    }

    function countriesList(){
        $country = new Application_Model_Countries();
        $sel = $country->select()->where('id =?', 1)->orWhere('id =?', 2);
        $counList = $country->fetchAll($sel);
        return $counList;
    }

    function registration($data){
        $userTable = new Application_Model_User();
        $addressTable = new Application_Model_Address();
        $inviteStausTable = new Application_Model_Invitestatus();
        $emailinvitesTable = new Application_Model_Emailinvites();
        $friendTable = new Application_Model_Friends();
        $userData = array(
                'Name'          => $data['Name'], 
                'Email_id'      => $data['Email_id'],
                'Password'      => $data['Password'],
                'Creation_date' => date('Y-m-d H:i'),
                'Update_date'   => date('Y-m-d H:i'),
                'Conf_code'     => $data['Conf_code']
        );
        $row = $userTable->createRow($userData);
        $row->save();
        if(!$frrows = $inviteStausTable->getData(array(user_id=>$row->id))) {
            $inviteStausTable->createRow(array(user_id=>$row->id, created => date('Y-m-d H:i:s'), updated => date('Y-m-d H:i:s')))->save();
        }
            
        
        /*
         * Making friend if invitation send by email 
         */
        if(isset($data['regType']) && $data['regType'] == 'email') {
            $inviteEmailRow = $emailinvitesTable->getData(array('code' => $data['regCode']));
            if($inviteEmailRow) {
                $frInsData = array(
                    'sender_id'   => $inviteEmailRow->sender_id,
                    'reciever_id' => $row->id,
                    'status'      => '1',
                    'source'      => 'email',
                    'cdate'       => date('Y-m-d H:i:s'),
                    'udate'       => date('Y-m-d H:i:s')
                );
                if($inviteEmailRow->sender_id) {
                    $friendTable->createRow($frInsData)->save(); 
                }
                $inviteEmailRow->delete();
            }
        }
        
        $addressData = array(
            'user_id'   => $row->id,
            'State_id'  => $data["State_id"],
            'address'   => $data["address"],
            'latitude'  => $data["latitude"],
            'longitude' => $data["longitude"]
        );
        $add = $addressTable->createRow($addressData);
        $add->save();
        return $row;
    }
    
    public function mobileRegistration($data){
        $userTable         = new Application_Model_User();
        $addressTable      = new Application_Model_Address();
        $inviteStausTable  = new Application_Model_Invitestatus();
        $emailinvitesTable = new Application_Model_Emailinvites();
        $friendTable       = new Application_Model_Friends();
          
        $userData = array(
                'Name'          => $data['Name'], 
                'Email_id'      => $data['Email_id'],
                'Password'      => $data['Password'],
                'Creation_date' => date('Y-m-d H:i'),
                'Update_date'   => date('Y-m-d H:i'),
                'Status'        => $data['Status'],
                'Conf_code'     => $data['Conf_code']
          );
        try{
            $row = $userTable->createRow($userData);
            $row->save();
   
        } catch(Exception $e) {
           echo "<pre>"; print_r($e); exit;
        }
                 
        if(!$frrows = $inviteStausTable->getData(array(user_id=>$row->id))) {
            $inviteStausTable->createRow(array(user_id=>$row->id, created => date('Y-m-d H:i:s'), updated => date('Y-m-d H:i:s')))->save();
        }
              
        /*
         * Making friend if invitation send by email 
         */
        if(isset($data['regType']) && $data['regType'] == 'email') {
            $inviteEmailRow = $emailinvitesTable->getData(array('code' => $data['regCode']));
            if($inviteEmailRow) {
                $frInsData = array(
                    'sender_id'   => $inviteEmailRow->sender_id,
                    'reciever_id' => $row->id,
                    'status'      => '1',
                    'source'      => 'email',
                    'cdate'       => date('Y-m-d H:i:s'),
                    'udate'       => date('Y-m-d H:i:s')
                );
                if($inviteEmailRow->sender_id) {
                    $friendTable->createRow($frInsData)->save(); 
                }
                $inviteEmailRow->delete();
            }
        }
        
        $addressData = array(
            'user_id'   => $row->id,
            'State_id'  => $data["State_id"],
            'address'   => $data["address"],
            'latitude'  => $data["latitude"],
            'longitude' => $data["longitude"]
        );
         //echo "<pre>"; print_r($addressData); exit;
        $add = $addressTable->createRow($addressData);
        $add->save();
        return $row;
   }

   
    function confirmEmail($id, $code){
        $userTable = new Application_Model_User();
        $select = $userTable->select()
                ->where('id =?', $id)
                ->where('Conf_code =?', $code);
        if($row = $userTable->fetchRow($select)) {
                $row->setFromArray(array('Status'=>'active', 'Conf_code'=>''));
                $row->save();
                return $row;
        }
    }

    public function resend($id){
        $userTable = new Application_Model_User();
        $select = $userTable->select()
                ->where('id =?', $id);
        if($row = $userTable->fetchRow($select)) {
            $code = $this->generateCode();
            $row->setFromArray(array('Conf_code'=> $code));
            $row->save();
            return $row;
        }		
    }

    function checkEmailId($email=null){
        $userTable = new Application_Model_User();
        $select = $userTable->select()
                ->where('Email_id =?', $email);
        if($row = $userTable->fetchRow($select)) {
            return $row;
        }
    }

    function loginDetail($data,$type=null){
        
        $user = new Application_Model_User();
        if($type=='tocheck'){
        
            $select = $user->select()->setIntegrityCheck(false)
            ->from('user_data')
            ->joinLeft('address','address.user_id = user_data.id',array('address', 'latitude','longitude'))
            ->where('user_data.Email_id =?', $data['email'])
            ->order(array('user_data.Email_id DESC LIMIT 1'))
            ->limit(1);
          $data = $user->fetchRow($select);
        
        } else {
          
           $select = $user->select()->setIntegrityCheck(false)
            ->from('user_data')
            ->joinLeft('address','address.user_id = user_data.id',array('address', 'latitude','longitude'))
            ->where('user_data.Email_id =?', $data['email'])
            ->where('user_data.Password =?', $data['pass']);
         
           $data = $user->fetchRow($select);
            
        }    
        return $data;
    }
    
       
   function wsloginDetail($data){
        $user = new Application_Model_User();
        $select = $user->select()->setIntegrityCheck(false)
            ->from('user_data')
            ->joinLeft('address','address.user_id = user_data.id',array('address', 'latitude','longitude'))
            ->joinLeft('user_profile','user_profile.user_id = user_data.id',array('Activities', 'Gender'))
            ->where('user_data.Email_id =?', $data['email'])->where('user_data.Password =?', $data['pass']);
        $data = $user->fetchRow($select);
        return $data;
   }
    
    
   function getUserId($data){
     $user = new Application_Model_User();
        $select = $user->select()->setIntegrityCheck(false)
            ->from('user_data',array('id'))
            ->where('user_data.Email_id =?', $data['email'])->where('user_data.Password =?', $data['pass']);
        $data = $user->fetchRow($select);
        return $data;
   }
    
   function updateToken($token,$userId){
      $user = new Application_Model_User();  
      $data = array('Token' => $token);
      $where['id = ?'] = $userId;
      $user->update($data, $where);
     
    }

    function fbLogin($data){
        $userTable = new Application_Model_User();
        $inviteStausTable = new Application_Model_Invitestatus();
        $tableFbTable = new Application_Model_Fbtempusers();
        $tableFriends = new Application_Model_Friends();
        $profileTable = new Application_Model_Profile();
        $select = $userTable->select()->setIntegrityCheck(false)
                ->from('user_data')
                ->joinLeft('address','address.user_id = user_data.id',array('latitude','longitude'))
                ->where('Network_id =?', $data['Network_id']); //echo $select; exit;
        if($row = $userTable->fetchRow($select)) {
            return $row;
        } else {
            $select = $userTable->select()->setIntegrityCheck(false)
                    ->from('user_data')
                    ->join('address','address.user_id = user_data.id',array('latitude','longitude'))
                    ->where('Email_id =?', $data['Email_id']);
            if($row = $userTable->fetchRow($select)) {
                $where = $userTable->getAdapter()->quoteInto('id = ?', $row->id);
                $userTable->update(array('Network_id' => $data['Network_id']),$where);
                return $row;
            } else {
                $row = $userTable->createRow($data);
                if($row) {
                    $row->save();
                    $inviteStausTable->createRow(array(user_id=>$row->id, created => date('Y-m-d H:i:s'), updated => date('Y-m-d H:i:s')))->save();
                    $row1 = $profileTable->createRow(array('user_id'=>$row->id, 'Gender'=>ucfirst($data['Gender'])));
                    $row1->save();

                    //code for fruends request notification
                    if($row->Network_id != "") {
                        $select = $tableFbTable->select()
                                ->where('reciever_nw_id =?', $row->Network_id);
                        if($frows = $tableFbTable->fetchAll($select)) {
                            foreach($frows as $frow) {
                                $friendData = array(
                                    'sender_id' => $frow->sender_id,
                                    'reciever_id' => $row->id,
                                    'cdate' => date('Y-m-d H:i:s'),
                                    'udate' => date('Y-m-d H:i:s')
                                );
                                $friendRow = $tableFriends->createRow($friendData);
                                $friendRow->save();
                                $frow->delete();
                            }
                        }
                    }
                }
                return $row;
            }
        }	
    }

    function addComments($comments, $newsId, $userId){
        $commentTable = new Application_Model_Comments();
        $data = array(
                        'news_id' 		=> $newsId,
                        'user_id'		=> $userId,
                        'comment' 		=> $comments,
                        'created_at' 	=> date('Y-m-d H:i'), 
                        'updated_at' 	=> date('Y-m-d H:i'),
                        'isdeleted'		=> 0
                );
        $row = $commentTable->createRow($data);
        $row->save();
        return $row->id; 
    }

	/**
	 *
	 *
	 * @param	integer	$news_id
	 * @param	integer	$total
	 *
	 * @return	array
	 */
	public function getCommentByNewsId($news_id, $total)
	{
        $commentTable = new Application_Model_Comments();

        $select = $commentTable->select()
				->setIntegrityCheck(false)
                ->from('comments', 'comments.*')
                ->where('comments.news_id = ?', $news_id)
                ->where('comments.isdeleted = ?', '0')
				->joinLeft('user_data', 'comments.user_id = user_data.id', '')
				->where('user_data.id IS NOT NULL')
                ->order('comments.created_at');

        if ($total > 2)
		{
			$select->limit(2, $total - 2);
		}

        return $commentTable->fetchAll($select);
    }

    function viewTotalComments($newsId, $offsetValue = null,$userId=null){
        
        $newsFactory = new Application_Model_NewsFactory();
        $commentTable = new Application_Model_Comments();
      
        $commenttotal = $newsFactory->countComments($newsId);
      
        $sel = $commentTable->select()->setIntegrityCheck(false)
         ->from('comments', array('*'))
         ->join('user_data','user_data.id = comments.user_id',array('user_name' =>'user_data.Name','Profile_image'))
         ->where('comments.news_id = ?', $newsId)
         ->where('comments.isdeleted = ?', '0');
       
        if($offsetValue<0){
          $sel->where('comments.user_id = ?', $userId); 
        }
       
        $sel->order('comments.created_at ASC');
       
        $is = ($offsetValue<0)? $sel->limit(1,0):$sel->limit(10, $offsetValue);
           
        $fetch = $commentTable->fetchAll($sel);
     
        $commArray = array();
        
        if(count($fetch)) {
                $i = 0;
                foreach($fetch as $row) {
                        $commArray[$i]['id'] 		= $row['id'];
                        $commArray[$i]['news_id'] 	= $row['news_id'];
                        $commArray[$i]['comment'] 	= $row['comment'];
                        $commArray[$i]['user_name']	= $row['user_name'];
                        $commArray[$i]['user_id']	= $row['user_id'];
                        $commArray[$i]['Profile_image'] = $row['Profile_image'];
                        $commArray[$i]['commTime']	= $row['created_at'];//$newsFactory->calculate_time($row['created_at']);
                        $commArray[$i]['totalComments']	= $commenttotal;
                        $i++;
                }
        }
        
        return $commArray;
    }
    

    function countComments($newsId){
        $commentTable = new Application_Model_Comments();
        $sel = $commentTable->select()->setIntegrityCheck(false)
                ->from('comments', array('*', 'count(*) as total'))
                ->join('user_data','user_data.id = comments.user_id',array('user_name' =>'user_data.Name'))
                ->where('comments.news_id = ?', $newsId)
                ->where('comments.isdeleted = ?', '0')
                ->order('comments.created_at');
        $fetch = $commentTable->fetchRow($sel);
        return $fetch->total;
    }

    function getUserData($user_id){
        $userTable = new Application_Model_User();
        $select = $userTable->select()->where('id =?', $user_id); 
        $fetch = $userTable->fetchRow($select);
        return $fetch;
    }

    function getUserAddress($user_id){
        $addressTable = new Application_Model_Address();
        $select = $addressTable->select()->where('user_id = ?', $user_id);
        $fetch = $addressTable->fetchRow($select);
        return $fetch;
    }

    function getUserProfileData($user_id){
        $profile = new Application_Model_Profile();
        $select = $profile->select()->where('user_id = ?', $user_id);
        $fetch = $profile->fetchRow($select);
        return $fetch;
    }

    function searchUsers($data = array(), $like = false){
        $userTable = new Application_Model_User;
        $select = $userTable->select()->setIntegrityCheck(false)
                ->from('user_data')
                ->joinLeft('address', 'user_data.id = address.user_id');
        if(count($data) > 0) {
            foreach ($data as $index => $value) {
                if($like) {
                    $select->where($index." LIKE ?", '%'.$value.'%');
                }else {
                    $select->where($index." =?", $value);
                }
            }
        } 
        $select->order("Name");
        return $userTable->fetchAll($select);
    }

    function imageUpload($name,$size,$tmp,$user_id){ 
        $path = realpath('.')."/www/upload/";
        $valid_formats = array("jpg", "png", "gif", "bmp","JPG");
        $response = '';
        if(strlen($name)) {
            list($txt, $ext) = explode(".", $name);
            if(in_array($ext,$valid_formats)) {				
                $actual_image_name = time().substr(str_replace(" ", "_", $txt), 5).".".$ext; 
                if(move_uploaded_file($tmp, $path.$actual_image_name)) {
                    $source = $path.$actual_image_name;
                    $destinationThumbnail320 = realpath('.')."/uploads/".$actual_image_name;                                                        
                    $myThumbnail = new My_Thumbnail($source);
                    $myThumbnail->resize(320,320);
                    $myThumbnail->save($destinationThumbnail320, 60);

                    if (file_exists($source)) {
                        unlink($source);
                    }

                    $userTable = new Application_Model_User();
                    $data = array('Profile_image' => $actual_image_name);
                    $select = $userTable->select()
                                        ->where('id = ?', $user_id);
                    $userRow = $userTable->fetchRow($select);    
                    if($userRow) {
                        $userRow->setFromArray($data);
                        $userRow->save();
                    }
                    $response = "uploads/".$actual_image_name."?parm=".time();
                } 
            } else {
                $response = "www/images/img-prof40x40.jpg?param=Invalid file format..";	
            }
        } else {
            $response = "www/images/img-prof40x40.jpg?param=Please select image..!";
        }
        return $response;
    }
    
    
    function wsimageUpload($name,$size,$tmp,$user_id){ 
        $path = realpath('.')."/www/upload/";
        $valid_formats = array("jpg", "png", "gif", "bmp","JPG","jpeg","JPEG");
        $response = '';
        if(strlen($name)) {
             list($txt, $ext) = explode(".", $name);
            if(in_array($ext,$valid_formats)) {				
                $actual_image_name = time().$txt.".".$ext; 
                if(move_uploaded_file($tmp, $path.$actual_image_name)) {
                    $source = $path.$actual_image_name;
                    $destinationThumbnail320 = realpath('.')."/uploads/".$actual_image_name;                                                        
                    $myThumbnail = new My_Thumbnail($source);
                    $myThumbnail->resize(320,320);
                    $myThumbnail->save($destinationThumbnail320, 60);

                    if (file_exists($source)) {
                        unlink($source);
                    }

                    $userTable = new Application_Model_User();
                    $data = array('Profile_image' => $actual_image_name);
                    $select = $userTable->select()
                                        ->where('id = ?', $user_id);
                    $userRow = $userTable->fetchRow($select);    
                    if($userRow) {
                        $userRow->setFromArray($data);
                        $userRow->save();
                    }
                    $response = "uploads/".$actual_image_name."?parm=".time();
                } 
            } else {
                $response = "www/images/img-prof40x40.jpg?param=Invalid file format..";	
            }
        } else {
            $response = "www/images/img-prof40x40.jpg?param=Please select image..!";
        }
        return $response;
    }

    function imageUploadWithCrowl($name,$size,$tmp,$user_id,$lat,$lng,$address){
        $path = realpath('.')."/public/www/newsimages/";
        $valid_formats = array("jpg", "png", "gif", "bmp");
        $response = '';
        if(strlen($name)) {			
            list($txt, $ext) = explode(".", $name);
            if(in_array($ext,$valid_formats)) {				
                if($size<(2028*2048)) {					
                    $actual_image_name = time().substr(str_replace(" ", "_", $txt), 5).".".$ext;
                    if(move_uploaded_file($tmp, $path.$actual_image_name)) {
                        $newsTable = new Application_Model_News();
                        $data = array(
                            'user_id' => $user_id,
                            'images' => BASE_PATH."public/www/newsimages/".$actual_image_name,
                            'latitude' => $lat,
                            'longitude' => $lng,
                            'Address' => $address,
                            'created_date' => date('Y-m-d H:i:s'),
                            'updated_date' => date('Y-m-d H:i:s')
                        );

                        $newsRow = $newsTable->createRow($data);    
                        if($newsRow) {
                            $newsRow->save();
                        }
                        //$where = $userTable->getAdapter()->quoteInto('id = ?', $user_id);
                        //$sa = $userTable->update($data,$where);
                        $response = urlencode(BASE_PATH."public/www/newsimages/".$actual_image_name."?parm=".time());
                    } else { 
                        $response = "failed";
                    }
                } else { 
                    $response = "Image file size max 1 MB";					
                }
            } else {
                    $response = "Invalid file format..";	
            }
        } else {
                $response = "Please select image..!";
        }
        return $response;
    }

    function getLatestPost($user_id){
       $newsFactory = new Application_Model_NewsFactory();
        $news = new Application_Model_News();
        $select = $news->select()->order('id DESC')
                      ->where('user_id=?',$user_id);
        $data = $news->fetchAll($select);
        return $data;
    }


}

 

?>