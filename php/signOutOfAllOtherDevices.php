<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$password = custgetparam("password");
	
	$user = User::findByEmail($email);
	if(is_object($user) && get_class($user) == "User"){
		$salt = $user->signOutOfAllOtherDevices($password);
		
		if($salt !== false){
			die("success" . $salt);
		}
		die("false|That is not the password for the " . $email . " account you are logged in with.");//incorrect password
	}
	else if(User::emailIsUnvalidated($email)){//check if email is unverified
		die("false|Check your email to verify your account. Check spam if needed!");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");//incorrect email
?>
