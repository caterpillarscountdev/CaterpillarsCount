<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	
	$email = custgetparam("email");
	$password = custgetparam("password");
	
	$user = User::findByEmail($email);
	if(is_object($user) && get_class($user) == "User"){
		$salt = $user->signIn($password);
		
		if($salt != false){
			die("success" . $salt);
		}
		die("Some of that info is incorrect.");//incorrect password
	}
	else if(User::emailIsUnvalidated($email)){//check if email is unverified
		die("Check your email to verify your account. Check spam if needed!");
	}
	die("Some of that info is incorrect.");//incorrect username
?>
