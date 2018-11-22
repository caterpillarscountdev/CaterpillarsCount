<?php
	header('Access-Control-Allow-Origin: *');

	require_once("../orm/Site.php");
	require_once("../orm/User.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
  
	$sites = Site::findAll();
	$dbconn = (new Keychain)->getDatabaseConnection();
	for($i = 0; $i < count($sites); $i++){
		if($sites[$i]->getActive() && $sites[$i]->getNumberOfSurveysByYear(date("Y")) <= 2){
			$query = mysqli_query($dbconn, "SELECT COUNT(Survey.*) AS Count FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $$sites[$i]->getID() . "' AND Survey.LocalDate>'" . date("Y") . "-06-13'");
			if(intval(mysqli_fetch_assoc($query)["Count"]) == 0){
				$emails = $sites[$i]->getAuthorityEmails();
				for($j = 0; $j < count($emails); $j++){
					$firstName = "there";
					$user = User::findByEmail();
					if(is_object($user) && get_class($user) != "User"){
						$firstName = $user->getFirstName();
					}
					email6($emails[$j], "Touching Base about " . $sites[$i]->getName(), $sites[$i]->getName());
				}
			}
		}
	}
	mysqli_close($dbconn);
?>
