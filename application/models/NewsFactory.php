<?php

class Application_Model_NewsFactory {
    public function generateCode(){
        mt_srand();
        $hash = md5(mt_rand(0, time()));
        return substr($hash, mt_rand(0, 20), 10);
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
}
