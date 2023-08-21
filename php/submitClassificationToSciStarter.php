<?php
	require_once("orm/User.php");
	require_once("submitToSciStarter.php");
    require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = trim(rawurldecode(custgetparam("email")));
    if (empty($email)) {
		die('false|No email specified');  
	}
	$user = User::findByEmail($email);
	if(is_object($user) || get_class($user) == "User"){
		submitToSciStarter(null, 0, $user->getEmail(), "classification", null, date("Y-m-d") . "T" . date("H:i:s"), null, 1, null);
	}
?>
