<?php
	header('Access-Control-Allow-Origin: *');

	require_once("orm/User.php");
	require_once("orm/Site.php");
    require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$siteID = custgetparam("siteID");
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
    		$site = Site::findByID($siteID);
		if(is_object($site) && get_class($site) == "Site" && $site->sendPrintTagsEmailTo($user)){
      			die("true|");
    		}
    		die("false|You do not have permission to request tags for this site.");
  	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
