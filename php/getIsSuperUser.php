<?php
	require_once('orm/User.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User" && User::isSuperUser($user)){
    die("true");
  }
  die("false");
?>
