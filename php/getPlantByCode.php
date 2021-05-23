<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Plant.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$code = $_GET["code"];
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$plant = Plant::findByCode($code);
		if(!is_object($plant) || $plant->getCircle() < 1){
			die("no plant");
		}
		$plantArray = array(
			"color" => $plant->getColor(),
			"siteName" => $plant->getSite()->getName(),
			"species" => $plant->getSpecies(),
			"circle" => $plant->getCircle(),
			"validated" => $plant->getSite()->getValidationStatus($user),
			"observationMethod" => $plant->getSite()->getObservationMethodPreset($user),
			"isConifer" => $plant->getIsConifer(),
		);
		die("true|" . json_encode($plantArray));
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
