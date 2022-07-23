<?php
	require_once("orm/User.php");
	require_once("orm/Site.php");

	$email = $_GET["email"];
	$salt = $_GET["salt"];
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    		$site = Site::findByID(255);
		if(is_object($site) && get_class($site) == "Site"){
      			die($site->isAuthority($user) ? "IS_AUTHORITY" : "NOT_AUTHORITY");
    		}
    		die("NOT A SITE");
  	}
	die("NOT A USER");
?>
