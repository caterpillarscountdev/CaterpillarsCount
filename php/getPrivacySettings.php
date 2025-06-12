<?php
	require_once('orm/resources/Keychain.php');
	require_once('orm/User.php');
    require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
  
  	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$privacySettings = array(
                  "firstName" => $user->getFirstName(),
                  "lastName" => $user->getLastName(),
                  "Hidden" => $user->getHidden(),
                  "iNaturalistObserverID" => $user->getINaturalistObserverID(),
                  "iNaturalistAccountName" => $user->getINaturalistAccountName(),
		);
    		die("true|" . json_encode($privacySettings));
  	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
