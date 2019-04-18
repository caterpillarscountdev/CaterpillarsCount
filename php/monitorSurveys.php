<?php
  require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
  require_once('/opt/app-root/src/php/orm/resources/mailing.php');
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$query = mysqli_query($dbconn, "SELECT Survey.ID FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK<>'2' AND CORRESPONDING_OLD_DATABASE_SURVEY_ID='0' AND Survey.ID NOT IN ('719', '2985', '2986', '2987', '2988', '2989', '2990', '2991', '2992', '2993', '2994', '2995', '2996', '2997', '2998', '2999', '3000', '5487', '6202', '6701', '11555', '28828', '29079', '29085', '29357', '29504', '29938', '30315', '30799', '31068') GROUP BY UserFKOfObserver, PlantFK, LocalDate, `LocalTime`, ObservationMethod, Notes, WetLeaves, PlantSpecies, NumberOfLeaves, AverageLeafLength, HerbivoryScore, SubmittedThroughApp, MinimumTemperature, MaximumTemperature, CORRESPONDING_OLD_DATABASE_SURVEY_ID HAVING COUNT(*) > 1 ORDER BY `Survey`.`SubmissionTimestamp` ASC");
	$ids = array();
  if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
      $ids[] = $row["ID"];
	  }
	}
  
  if(count($ids) > 0){
    email("plocharczykweb@gmail.com", "Duplicate survey detected", "There may have been a timeout or some other error that caused a user to have to submit the same survey twice. The following Survey IDs should be investigated: " . implode(", ", $ids));
  }
?>
