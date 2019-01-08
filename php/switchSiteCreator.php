<?php
  	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Site.php');
	require_once('orm/ManagerRequest.php');
	
  	$siteID = $_GET["siteID"];
  	$managerID = $_GET["managerID"];
  	$creatorPermissions = filter_var($_GET["creatorPermissions"], FILTER_VALIDATE_BOOLEAN);
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    		$site = Site::findByID($siteID);
    		if(is_object($site) && get_class($site) == "Site" && $site->hasCreatorPermissions($user)){
      			$manager = User::findByID($managerID);
      			if(is_object($manager) && get_class($manager) == "User"){
				if($manager != $user){
					$managerRequest = ManagerRequest::findByManagerAndSite($manager, $site);
					if(get_class($managerRequest) == "ManagerRequest"){
						$managerRequest->setHasCompleteAuthority($creatorPermissions);
						die("true|");
					}
					die("false|" . $manager->getFullName() . " is not a manager of this site.");
				}
				die("false|You cannot edit your own authority level.");
			}
      			die("false|We could not locate that manager's account. Please reload the page and try again.");
    		}
    		die("false|You do not have permission to oversee this site's management.");
  	}
  	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
