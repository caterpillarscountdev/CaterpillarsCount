<?php
	require_once("orm/User.php");
	require_once("orm/Site.php");
	require_once("orm/Plant.php");

// 	$email = $_GET["email"];
// 	$salt = $_GET["salt"];
	
// 	$user = User::findBySignInKey($email, $salt);
// 	if(is_object($user) && get_class($user) == "User"){
//     		$site = Site::findByID(255);
// 		if(is_object($site) && get_class($site) == "Site"){
//       			die($site->isAuthority($user) ? "IS_AUTHORITY" : "NOT_AUTHORITY");
//     		}
//     		die("NOT A SITE");
//   	}
// 	die("NOT A USER");


	$users = User::findUsersByIDs(array(2066, 25));
	$userCount = 0;
	for($i = 0; $i < count($users); $i++){
		try{
			$userID = $users[$i]->getID();
			if($userID == 2066 || $userID == 25){
				$userCount++;
			}
		}catch(Exception $e){}
	}

	$plants = Plant::findPlantsByIDs(array(5663, 5664));
	$plantCount = 0;
	for($i = 0; $i < count($plants); $i++){
		try{
			$plantID = $plants[$i]->getID();
			if($plantID == 5663 || $plantID == 5664){
				$plantCount++;
			}
		}catch(Exception $e){}
	}

	echo $userCount . "|" . $plantCount;
?>
