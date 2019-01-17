<?php
	header('Access-Control-Allow-Origin: *');

	require_once("../orm/Site.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
  
	$sites = Site::findAll();
	$dbconn = (new Keychain)->getDatabaseConnection();
	for($i = 0; $i < count($sites); $i++){
		if($sites[$i]->getActive() && $sites[$i]->getNumberOfSurveysByYear(date("Y")) <= 2){
			$query = mysqli_query($dbconn, "SELECT COUNT(Survey.ID) AS Count FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>'" . date("Y") . "-06-13'");
			if(intval(mysqli_fetch_assoc($query)["Count"]) == 0){
				$emails = $sites[$i]->getAuthorityEmails();
				for($j = 0; $j < count($emails); $j++){
					//email6($emails[$j], "Caterpillars Count! at " . $sites[$i]->getName(), $sites[$i]->getName());
					echo $emails[$j] . "Caterpillars Count! at " . $sites[$i]->getName() . $sites[$i]->getName();
				}
			}
		}
	}
	mysqli_close($dbconn);
?>
