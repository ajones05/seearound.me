<?php
 if($_SERVER['SERVER_NAME'] == 'localhost')
    $con = mysql_connect('localhost','root','');
else
   $con = mysql_connect('localhost','ajones05_pro','ajones$12');   

if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db('ajones05_herespy', $con); 
