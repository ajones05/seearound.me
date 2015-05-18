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

    public function generateCode(){
        mt_srand();
        $hash = md5(mt_rand(0, time()));
        return substr($hash, mt_rand(0, 20), 10);
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

	// TODO: move to Application_Model_User model

	public function loginDetail($data)
	{
		$user = new Application_Model_User;

		$select = $user->select()->setIntegrityCheck(false)
			->from('user_data')
			->joinLeft('address','address.user_id = user_data.id',array('address', 'latitude','longitude'))
			->joinLeft('user_profile','user_profile.user_id = user_data.id',array('Activities', 'Gender'))
			->where('user_data.Email_id =?', $data['email'])
			->where('user_data.Password =?', $data['pass']);

		$data = $user->fetchRow($select);
 
		return $data;
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

    function viewTotalComments($newsId, $offsetValue = null,$userId=null){
        
        $newsFactory = new Application_Model_NewsFactory();
        $commentTable = new Application_Model_Comments();
      
        $commenttotal = $commentTable->getCountByNewsId($newsId);
      
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
                        $commArray[$i]['commTime']	= $row['created_at'];
                        $commArray[$i]['totalComments']	= $commenttotal;
                        $i++;
                }
        }
        
        return $commArray;
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
}
