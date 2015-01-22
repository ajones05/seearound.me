<?php

class Zend_View_Helper_LinkClickable extends Zend_View_Helper_Abstract
{
    public function linkClickable($text = null) 
	{
		$text = preg_replace("/(?<!http:\/\/)www\./","http://www.",$text);
    	return preg_replace('!(((f|ht)tp://)[-a-zA-Z?-??-?()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1" target="_blank">$1</a>', $text);
	}
}
?>