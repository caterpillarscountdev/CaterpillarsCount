<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Plant.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$code = custgetparam("code");
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$plant = Plant::findByCode($code);
		if(!is_object($plant) || $plant->getCircle() < 1){
			die("no plant");
		}
		$infoArray = array(
			"circle" => $plant->getCircle(),
			"orientation" => $plant->getOrientation(),
			"color" => $plant->getColor(),
			"validated" => $plant->getSite()->getValidationStatus($user),
			"species" => $plant->getSpecies(),
			"siteName" => $plant->getSite()->getName(),
			"region" => $plant->getSite()->getRegion(),
			"latitude" => $plant->getSite()->getLatitude(),
			"longitude" => $plant->getSite()->getLongitude(),
			"siteDescription" => $plant->getSite()->getDescription(),
			"circleCount" => (count($plant->getSite()->getPlants()) / 5),
		);
		die("true|" . json_encode($infoArray));
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
