<?php
	require_once('orm/User.php');
  
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$score = intval($_GET["score"]);
  
  	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		if($user->submitVirtualSurveyScore($score)){
			die("true|");
        	}
        	die("false|We could not submit that score.");
  	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
