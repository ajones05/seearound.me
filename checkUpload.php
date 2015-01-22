<?php
include_once('database.inc');
if (isset($_POST)) {        
    $base = $_REQUEST['encodedImage'];
    if(isset($base) && $base != '') {   
        $binary = base64_decode($base);
        header('Content-Type: bitmap; charset=utf-8');
        $path = realpath('.') . "/newsimages/" . date('YmdHs') . ".jpeg";
        $file = fopen($path, 'wb');
        fwrite($file, $binary);
        //echo $path;
        fclose($file);
    } else{
        $path = realpath('.') . "/uploaded/preview.png";
    }
    
    
}


?> 
