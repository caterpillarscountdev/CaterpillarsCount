<?php
	require_once("orm/User.php");
	require_once("submitToSciStarter.php");

	$email = trim(rawurldecode($_POST["email"]));

	$user = User::findByEmail($email);
	if(is_object($user) || get_class($user) == "User"){
		submitToSciStarter($user->getEmail(), "classification", null, date("Y-m-d") . "T" . date("H:i:s"), null, 1, null);
	}
?>
