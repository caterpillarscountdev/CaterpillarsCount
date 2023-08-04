<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once("orm/User.php");
	require_once('orm/resources/Customlogging.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$firstName = custgetparam("firstName");
	$lastName = custgetparam("lastName");
	$email = custgetparam("email");
	$password = custgetparam("password");
	
	//custom_error_log("signUp.php init ");
	
	$newUser = User::create($firstName, $lastName, $email, $password);
	if(is_object($newUser) && get_class($newUser) == "User"){
		$userid = intval($newUser->getID());
		//custom_error_log("signUp.php created userid " . $userid);
		if (User::sendEmailVerificationCodeToUser($userid)) {
		//custom_error_log("signUp.php success");
		die("success");
		} else {
		  custom_error_log("signUp.php error");
		  die("error");	
		}
	}
	die((string)$newUser);
?>
