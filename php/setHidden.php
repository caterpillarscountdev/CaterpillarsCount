<?php
	require_once('orm/resources/Keychain.php');
	require_once('orm/User.php');
    require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$newValue = filter_var(custgetparam("newValue"), FILTER_VALIDATE_BOOLEAN);
  
  	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    		$user->setHidden($newValue);
    		die("true|" . ($user->getHidden() ? 'true' : 'false'));
  	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
