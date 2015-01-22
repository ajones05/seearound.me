<?php

class Zend_View_Helper_DateConversion extends Zend_View_Helper_Abstract
{
    public function dateConversion($secountds = null) 
    {
        //get date by function
        if($secountds) {
            $data_ref = date('Y-m-d H:i:s', $secountds);
        }else {
            $data_ref = date('Y-m-d H:i:s');
        }
          
        // Get the current date
        $current_date = date('Y-m-d H:i:s');
        $diff = strtotime($current_date) - strtotime($data_ref);        
        $years   = floor($diff / (365*60*60*24)); 
        $months  = floor(($diff - $years * 365*60*60*24) / (30*60*60*24)); 
        $days    = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
        $hours   = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60)); 
        $minuts  = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60); 
        $seconds = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minuts*60)); 
        if($years > 0){
            if($years == '1') {
                return $years . ' Year';
            } else {
                return $years . ' Years';
            }
        }elseif( $months > 0) {
            if($years == '1') {
                return $months . ' Month';
            } else {
                return $months . ' Months';
            }
        }elseif( $days > 0) {
            if($days == '1') {
                return 'Yesterday';
            } else {
                return $days . ' Days';
            }
        } else {
            return 'Today';
        }
    }  
	
}
?>
