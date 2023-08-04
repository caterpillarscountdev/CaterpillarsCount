<?php
  	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Site.php');
	require_once('orm/ManagerRequest.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	
  	$siteID = custgetparam("siteID");
  	$managerEmail = custgetparam("managerEmail");
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    		$site = Site::findByID($siteID);
    		if(is_object($site) && get_class($site) == "Site" && $site->hasCreatorPermissions($user)){
      			$manager = User::findByEmail($managerEmail);
      			if(is_object($manager) && get_class($manager) == "User"){
				if($managerEmail == $site->getCreator()->getEmail()){
					die("false|" . $manager->getFullName() . " is the creator of this site and already has complete authority over it.");
				}
				
				$managerRequest = ManagerRequest::findByManagerAndSite($manager, $site);
				if($managerRequest !== null){
					if($managerRequest->getStatus() == "Pending"){
						die("false|" . $manager->getFullName() . " has already received a request to be a manager for this site.");
					}
					else if($managerRequest->getStatus() == "Approved"){
						die("false|" . $manager->getFullName() . " is already a manager of this site.");
					}
				}
				
				$managerRequest = ManagerRequest::create($site, $manager);
				
				if(get_class($managerRequest) == "ManagerRequest"){
					$managerRequestArray = array(
						"managerID" => $manager->getID(),
						"fullName" => $manager->getFullName(),
						"email" => $manager->getEmail(),
						"status" => $managerRequest->getStatus(),
					);
          				die("true|" . json_encode($managerRequestArray));
       				}
        			die("false|" . trim($managerRequest));
      			}
      			die("false|There is no Caterpillars Count! user associated with that email address.");
    		}
		die("false|You do not have permission to oversee this site's management.");
	}
  	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
