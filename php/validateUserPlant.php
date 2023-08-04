<?php
  require_once('orm/User.php');
	require_once('orm/Plant.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$code = custgetparam("code");
	$password = custgetparam("password");
  
  $user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$plant = Plant::findByCode($code);
		if(!is_object($plant)){
			die("false|Enter a valid survey location code.");
		}
    $site = $plant->getSite();
    if(is_object($user) && get_class($user) == "User"){
      if($site->validateUser($user, $password)){
        die("true|");
      }
      die("false|Invalid site password.");
    }
    die("false|Could not find plant's site.");
  }
  die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
