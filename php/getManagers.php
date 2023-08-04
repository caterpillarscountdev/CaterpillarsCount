<?php
  	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Site.php');
	require_once('orm/ManagerRequest.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
  	$siteID = custgetparam("siteID");
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    		$site = Site::findByID($siteID);
    		if(is_object($site) && get_class($site) == "Site" && $site->hasCreatorPermissions($user)){
			$managerRequests = ManagerRequest::findManagerRequestsBySite($site);
			$mangers = array();
			for($i = 0; $i < count($managerRequests); $i++){
				$managers[] = array(
					"managerID" => $managerRequests[$i]->getManager()->getID(),
					"fullName" => $managerRequests[$i]->getManager()->getFullName(),
					"email" => $managerRequests[$i]->getManager()->getEmail(),
					"hasCompleteAuthority" => $managerRequests[$i]->getHasCompleteAuthority(),
					"status" => $managerRequests[$i]->getStatus(),
				);
			}
			
			$siteCreator = array(
				"siteCreatorID" => $site->getCreator()->getID(),
				"fullName" => $site->getCreator()->getFullName(), 
				"email" => $site->getCreator()->getEmail()
			);
      			die("true|" . json_encode(array($siteCreator, $managers)));
    		}
    		die("false|You do not have permission to oversee this site's management.");
  	}
  	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
