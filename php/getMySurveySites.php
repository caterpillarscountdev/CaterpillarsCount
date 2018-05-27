<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$sites = $user->getSites();
		$sitesArray = array();
		for($i = 0; $i < count($sites); $i++){
			$sitesArray[$i] = array(
				"id" => $sites[$i]->getID(),
				"name" => $sites[$i]->getName(),
				"region" => $sites[$i]->getRegion(),
			);
		}
		die("true|" . json_encode($sitesArray));
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
