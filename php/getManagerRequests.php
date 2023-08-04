<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8   

	$email = custgetparam("email");
	$salt = custgetparam("salt");
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$managerRequests = $user->getPendingManagerRequests();
		$requestsArray = array();
		for($i = 0; $i < count($managerRequests); $i++){
			$requestArray = array(
				"id" => $managerRequests[$i]->getID(),
				"requester" => $managerRequests[$i]->getSite()->getCreator()->getFullName(),
				"siteName" => $managerRequests[$i]->getSite()->getName(),
				"siteDescription" => $managerRequests[$i]->getSite()->getDescription(),
				"siteCoordinates" => $managerRequests[$i]->getSite()->getLatitude() . ", " . $managerRequests[$i]->getSite()->getLongitude(),
				"siteRegion" => $managerRequests[$i]->getSite()->getRegion(),
				"siteOpenToPublic" => $managerRequests[$i]->getSite()->getOpenToPublic(),
			);
			
			array_push($requestsArray, $requestArray);
		}
		die("true|" . json_encode($requestsArray));
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
