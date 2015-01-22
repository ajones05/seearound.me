<?php

class Zend_View_Helper_TimeConversion extends Zend_View_Helper_Abstract
{
    public function timeConversion($a = null) 
    {
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
	
}
?>
