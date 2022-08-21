<?php
  require_once('orm/User.php');
  require_once "orm/Survey.php";

  $email = $_GET["email"];
	$salt = $_GET["salt"];
  
  $user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    die(Survey::getTest($user));
  }
?>
