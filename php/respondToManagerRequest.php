<?php
	require_once('orm/Site.php');
	require_once('orm/ManagerRequest.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$managerRequestID = custgetparam("managerRequestID");
  	$response = custgetparam("response");
  	$email = custgetparam("email");
  	$salt = custgetparam("salt");

  	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$managerRequest = ManagerRequest::findByID($managerRequestID);
		if(get_class($managerRequest) == "ManagerRequest"){
			if($managerRequest->getManager() == $user){
    				if($response == "approve"){
      					if($managerRequest->setStatus("Approved")){
						die("true|" . $managerRequest->getSite()->getName());
					}
					die("false|Could not approve request.");
    				}
    				else if($response == "deny"){
      					if($managerRequest->setStatus("Denied")){
						die("true|");
					}
					die("false|Could not deny request.");
    				}
    				die("false|Invalid response.");
			}
			die("false|You do not have permission to respond to this site manager request.");
		}
		die("false|Could not locate manager request in order to respond to it.");
  	}
  	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
