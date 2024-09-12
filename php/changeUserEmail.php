<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$newEmail = custgetparam("newEmail");
        $newFirstName = custgetparam("firstName");
        $newLastName = custgetparam("lastName");
	$email = custgetparam("email");
	
	$user = User::findByEmail($email);
	if(is_object($user) && get_class($user) == "User"){
          if($user->getFirstName() != $newFirstName) {
            $user->setFirstName($newFirstName);
          }
          if($user->getLastName() != $newLastName) {
            $user->setFirstName($newFirstName);
          }
          if($user->getEmail() != $newEmail) {
            $changed = $user->setEmail($newEmail);
            if(!$changed) {
              die("false|Your email could not be changed to " . $newEmail);
            }
          }
          die("true");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
