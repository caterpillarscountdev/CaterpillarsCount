<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	
	$email = $_GET["email"];
	$password = $_GET["password"];
	
	$user = User::findByEmail($email);
	if(is_object($user) && get_class($user) == "User"){
		$salt = $user->signOutOfAllOtherDevices($password);
		
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
