<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once("orm/User.php");
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	if (empty($email)) {
		die("No email specified"); 
	}
	$user = User::findByEmail($email);
	
	if(is_object($user) && get_class($user) == "User"){
		if($user->recoverPassword()){
			die("true");
		}
	}
	else if(User::emailIsUnvalidated($email)){//check if email is unverified
		die("Check your email to verify your account before recovering your password. Check spam if needed!");
	}
	die("That email is not attached to a Caterpillars Count! account!");
?>
