<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/resources/Keychain.php');
	require_once('orm/User.php');
	require_once('orm/Site.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$sites = $user->getSites();
		$sitesArray = array();
		$siteIDs = array();
		for($i = 0; $i < count($sites); $i++){
			$siteIDs[] = $sites[$i]->getID();
			$sitesArray[] = array(
				"id" => $sites[$i]->getID(),
				"name" => $sites[$i]->getName(),
				"region" => $sites[$i]->getRegion(),
			);
		}
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT Site.ID, Site.Name, Site.Region FROM `Survey` JOIN `Plant` ON Survey.PlantFK = Plant.ID JOIN `Site` ON Plant.SiteFK=Site.ID WHERE Survey.UserFKOfObserver='" . $user->getID() . "' GROUP BY Site.ID");
		while($siteRow = mysqli_fetch_assoc($query)){
			$id = $siteRow["ID"];
			if(!in_array($id, $siteIDs)){
				$siteIDs[] = $id;
				$sitesArray[] =  array(
					"id" => $id,
					"name" => $siteRow["Name"],
					"region" => $siteRow["Region"],
				);
			}
		}
		die("true|" . json_encode($sitesArray));
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
