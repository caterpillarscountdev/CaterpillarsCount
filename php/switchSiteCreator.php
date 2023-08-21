<?php
  	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Site.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
  	
	$siteID = custgetparam("siteID");
  	$managerID = custgetparam("managerID");
  	$demotedPosition = custgetparam("demotedPosition"); //"unaffiliated", "highManagement", or "lowManagement"
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    		$site = Site::findByID($siteID);
    		if(is_object($site) && get_class($site) == "Site" && $site->getCreator()->getID() == $user->getID()){
      			$manager = User::findByID($managerID);
      			if(is_object($manager) && get_class($manager) == "User"){
				if($manager != $user){
					$switchResult = $site->setCreator($manager, $demotedPosition);
					if($switchResult === true){
						die("true|");
					}
					else if($switchResult === false){
						die("false|" . $manager->getFullName() . " has not been approved as a manager of this site.");
					}
					die("false|" . $switchResult);
				}
				die("false|You are already the owner of this site.");
			}
      			die("false|We could not locate the account of the manager you would like to replace you as site owner. Please reload the page and try again.");
    		}
    		die("false|You do not have permission to appoint a new owner of this site.");
  	}
  	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
