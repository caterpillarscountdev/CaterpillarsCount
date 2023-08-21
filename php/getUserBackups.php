<?php
	require_once('orm/User.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
  
  	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User" && (User::isSuperUser($user->getEmail()))){
    		$files = array_values(scandir("../" . getenv("USER_BACKUPS")));
    		for($i = (count($files) - 1); $i >= 0; $i--){
			if(strpos($files[$i], ".csv") === false){
				unset($files[$i]);
			}
			else{
				$files[$i] = "../" . getenv("USER_BACKUPS") . "/" . $files[$i];
			}
    		}
    		die("true|" . json_encode(array_values($files)));
  	}
  	die("false|Please log in with a developer account before accessing this page.");
?>
