<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Site.php');
	
	$siteID = $_GET["siteID"];
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$siteName = $_GET["siteName"];
	$description = $_GET["description"];
	$url = $_GET["url"];
	$sitePassword = $_GET["sitePassword"];
	$public = $_GET["public"];
	$active = $_GET["active"];
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$sites = $user->getSites();
		$site = Site::findByID($siteID);
		if(is_object($site) && get_class($site) == "Site" && in_array($site, $sites)){
			$errors = "";
			if($site->getName() != $siteName){
				if(preg_replace('/\s+/', '', $siteName) == ""){
					$errors .= "Enter a site name. ";
				}
				else if(!$site->setName($siteName)){
					$errors .= "That site name is already in use. Choose a different one. ";
				}
			}
			if($site->getDescription() != $description){
				if(!$site->setDescription($description)){
					$errors .= "Site description must be between 1 and 255 characters. ";
				}
			}
			if($site->getURL() != $url){
				$site->setURL($url);
			}
			if(!$site->passwordIsCorrect($sitePassword) && $sitePassword != "hf!Eo 2k"){//"hf!Eo 2k" is just the placeholder default value from the front end
				if($user->passwordIsCorrect($password)){
					$errors .= "Password cannot be the same as your Caterpillars Count! account password because you may be sharing it with vistors at this site. ";
				}
				else if(!$site->setPassword($sitePassword)){
					$errors .= "Password must be at least 4 characters with no spaces. ";
				}
			}
			if($site->getOpenToPublic() !== filter_var($public, FILTER_VALIDATE_BOOLEAN)){
				if(!$site->setOpenToPublic($public)){
					$errors .= "Could not set site's public status. ";
				}
			}
			if($site->getActive() !== filter_var($active, FILTER_VALIDATE_BOOLEAN)){
				if(!$site->setActive($active)){
					$errors .= "Could not set site's active status. ";
				}
			}
			if($errors == ""){
				die("true");
			}
			die("false|" . $errors);
		}
		die("false|You do not have permission to edit this site.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
