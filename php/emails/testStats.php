<?php
header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	
	$site = Site::findByID("2");
	$dbconn = (new Keychain)->getDatabaseConnection();
      $query = mysqli_query($dbconn, "SELECT COUNT(Survey.ID) AS Count FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $site->getID() . "' AND Survey.LocalDate>'2018-06-13'");
			if(intval(mysqli_fetch_assoc($query)["Count"]) == 0){
				$emails = $site->getAuthorityEmails();
				for($j = 0; $j < count($emails); $j++){
					if($emails[$j] == "plocharczykweb@gmail.com"){
						email6($emails[$j], "Touching Base about " . $site->getName(), $site->getName());
					}
				}
			}

?>
