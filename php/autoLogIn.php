<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once("orm/User.php");
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$user = User::findBySignInKey(custgetparam("email"), custgetparam("salt"));
	if(is_object($user) && get_class($user) == "User"){
		die("true");
	}
	die("false");
?>
