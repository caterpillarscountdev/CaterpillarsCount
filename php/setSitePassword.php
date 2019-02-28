<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Site.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$siteName = $_GET["siteName"];
	$newPassword = $_GET["newPassword"];
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$sites = $user->getSites();
		for($i = 0; $i < count($sites); $i++){
			$sites[$i] = $sites[$i]->getID();
		}
		$site = Site::findByName($siteName);
		if(is_object($site) && get_class($site) == "Site" && in_array($site, $sites)){
			if($site->passwordIsCorrect($newPassword)){
				die("false|That is already " . $siteName . "'s password.");
			}
			if($site->setPassword($newPassword)){
				die("true");
			}
			die("false|Password must be at least 8 characters with no spaces.");
		}
		die("false|You do not have permission to change this site's password.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
