<?php
  	require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
  	require_once('/opt/app-root/src/php/orm/resources/mailing.php');
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$mobileTimeouts = array();
	$query = mysqli_query($dbconn, "SELECT Survey.ID, Plant.Code, Survey.LocalDate, Survey.LocalTime, CONCAT(User.FirstName, " ", User.LastName) AS FullName FROM Survey JOIN User ON Survey.UserFKOfObserver = User.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.SubmittedThroughApp='1' AND Plant.SiteFK<>'2' AND CORRESPONDING_OLD_DATABASE_SURVEY_ID='0' AND Survey.ID > '100000' GROUP BY UserFKOfObserver, PlantFK, LocalDate, `LocalTime`, ObservationMethod, Notes, WetLeaves, PlantSpecies, NumberOfLeaves, AverageLeafLength, HerbivoryScore, SubmittedThroughApp, MinimumTemperature, MaximumTemperature, CORRESPONDING_OLD_DATABASE_SURVEY_ID HAVING COUNT(*) > 1 ORDER BY Survey.SubmittedThroughApp DESC, `Survey`.`SubmissionTimestamp` ASC")
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			$mobileTimeouts[] = "SURVEY ID: " . $row["ID"] . "| " . $row["Code"] . " " . $row["LocalDate"] . " " . $row["LocalTime"] . " " . $row["FullName"];
		}
	}
	
	$desktopResubmissions = array();
	$query = mysqli_query($dbconn, "SELECT Survey.ID, Plant.Code, Survey.LocalDate, Survey.LocalTime, CONCAT(User.FirstName, " ", User.LastName) AS FullName FROM Survey JOIN User ON Survey.UserFKOfObserver = User.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.SubmittedThroughApp='0' AND Plant.SiteFK<>'2' AND CORRESPONDING_OLD_DATABASE_SURVEY_ID='0' AND Survey.ID > '100000' GROUP BY UserFKOfObserver, PlantFK, LocalDate, `LocalTime`, ObservationMethod, Notes, WetLeaves, PlantSpecies, NumberOfLeaves, AverageLeafLength, HerbivoryScore, SubmittedThroughApp, MinimumTemperature, MaximumTemperature, CORRESPONDING_OLD_DATABASE_SURVEY_ID HAVING COUNT(*) > 1 ORDER BY Survey.SubmittedThroughApp DESC, `Survey`.`SubmissionTimestamp` ASC")
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			$desktopResubmissions[] = "SURVEY ID: " . $row["ID"] . "| " . $row["Code"] . " " . $row["LocalDate"] . " " . $row["LocalTime"] . " " . $row["FullName"];
		}
	}
  
  	if(count($mobileTimeouts) > 0 || count($desktopResubmissions) > 0){
		email("plocharczykweb@gmail.com", "Duplicate survey detected", "There may have been a timeout or some other error that caused a user to have to submit the same survey twice. The following Survey IDs should be investigated: \n\n\nMOBILE APP:\n" . implode("\n\n", $mobileTimeouts)) . "\n\n\nWEBSITE:\n" . implode("\n\n", $desktopResubmissions));
  	}
?>
