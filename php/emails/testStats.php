<?php
header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT Survey.LocalDate) AS DateCount, COUNT(DISTINCT Survey.UserFKOfObserver) AS ParticipantCount, COUNT(DISTINCT Survey.PlantFK) AS PlantCount, COUNT(DISTINCT Plant.Circle) AS SurveyedCircleCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='" . (intval(date("Y")) - 1) . "' AND Plant.SiteFK='2' GROUP BY Plant.SiteFK");
	$siteRow = mysqli_fetch_assoc($query);
	$site = Site::findByID("2");
	if($site->getActive() && $site->getLatitude() < 36.5 && $site->getNumberOfSurveysByYear("2019") == 0){
      $emails = $site->getAuthorityEmails();
      for($j = 0; $j < count($emails); $j++){
        $firstName = "there";
        $user = User::findByEmail($emails[$j]);
        if(is_object($user) && get_class($user) == "User"){
          $firstName = $user->getFirstName();
        }
	      if($emails[$j] == "plocharczykweb@gmail.com"){
        email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
	      }
      }
    }
?>
