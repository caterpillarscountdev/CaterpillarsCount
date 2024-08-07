<?php
	require_once('orm/resources/Keychain.php');
	require_once('resultMemory.php');
    require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8   

	$siteID = custgetparam("siteID");

	$HIGH_TRAFFIC_MODE = true;
	$SAVE_TIME_LIMIT = 60;
	
	$baseFileName = basename(__FILE__, '.php') . $siteID;
	if($HIGH_TRAFFIC_MODE){
		$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
		if($save !== null){
			die($save);
		}
	}
	
	$extraSQL = "";
	if(isset($siteID)){
		$extraSQL = " JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . intval($siteID) . "'";
	}
	
	$dbconn = (new Keychain)->getDatabaseConnection();
  
	$query = mysqli_query($dbconn, "SELECT YEAR(LocalDate) AS EarliestYear FROM Survey" . $extraSQL . " ORDER BY LocalDate ASC LIMIT 1");
	
	$result = "" . mysqli_fetch_assoc($query)["EarliestYear"];
	if($HIGH_TRAFFIC_MODE){
		save($baseFileName, $result);
	}
	die($result);
?>
