<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Site.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$siteID = custgetparam("siteID");
	$appVersion = intval(preg_replace("/[^0-9]/", "", isset($_GET["appVersion"]) ? $_GET["appVersion"] : "0"));
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$sites = $user->getSites();
		for($i = 0; $i < count($sites); $i++){
			$sites[$i] = $sites[$i]->getID();
		}
	    $site = Site::findByID($siteID);
	    if(is_object($site) && get_class($site) == "Site" && in_array($site->getID(), $sites)){
	      $plants = $site->getPlants();
	      $circles = array();
	      for($i = 0; $i < ceil(count($plants)/5); $i++){
		$circles[$i] = array(($i + 1), array());
	      }
	      for($i = 0; $i < count($plants); $i++){
		      if($appVersion < 150){
			      $circles[($plants[$i]->getCircle() - 1)][1][] = array($plants[$i]->getOrientation(), $plants[$i]->getCode(), $plants[$i]->getSpecies());
		      }
                      else if ($appVersion < 160) {
                              $circles[($plants[$i]->getCircle() - 1)][1][] = array($plants[$i]->getOrientation(), $plants[$i]->getCode(), $plants[$i]->getSpecies(), $plants[$i]->getIsConifer());
                      
                      }
		      else{
			      $circles[($plants[$i]->getCircle() - 1)][1][] = array($plants[$i]->getOrientation(), $plants[$i]->getCode(), $plants[$i]->getSpecies(), $plants[$i]->getIsConifer(), $plants[$i]->getLatitude(), $plants[$i]->getLongitude(), $plants[$i]->getColor());
		      }
	      }
	    	die("true|" . json_encode(array($site->getName() . " (" . $site->getRegion() . ")", $circles, $site->getLatitude(), $site->getLongitude())));
	    }
	    die("false|You do not have access to this site.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
