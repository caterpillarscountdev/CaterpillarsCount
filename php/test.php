<?php
  require_once('orm/User.php');
  require_once "orm/Survey.php";
  require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
  $email = custgetparam("email");
	$salt = custgetparam("salt");
  
  $user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    die(Survey::getTest($user));
  }
?>
