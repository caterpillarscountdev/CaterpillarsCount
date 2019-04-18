<?php
	require_once("/opt/app-root/src/php/orm/resources/Keychain.php");
	require_once("/opt/app-root/src/php/submitToSciStarter.php");

	//iNaturalist testing (in this file so we can use the cronjob that already hits this file every minute)
	require_once("/opt/app-root/src/php/submitToINaturalist.php");
	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT `temp` FROM ManagerRequest WHERE ID='16'");
	$needToSend = filter_var(mysqli_fetch_assoc($query)["temp"], FILTER_VALIDATE_BOOLEAN);
	if($needToSend){
		submitINaturalistObservation("plocharczykweb", "BYE", "2019-04-18", "Visual", "", false, "bee", false, false, false, 1, 5, "32.jpeg", "", 50, 10, 2);
		$query = mysqli_query($dbconn, "UPDATE ManagerRequest SET `temp`='0' WHERE ID='16'");
	}
	mysqli_close($dbconn);
	//End of iNaturalist testing

	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT Survey.ID AS SurveyID, User.Email, Survey.LocalDate, Survey.`LocalTime` FROM Survey JOIN User ON Survey.UserFKOfObserver=User.ID WHERE Survey.NeedToSendToSciStarter='1' ORDER BY RAND()");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			submitToSciStarter($row["Email"], "collection", null, $row["LocalDate"] . "T" . $row["LocalTime"], 300, 2, null);
			mysqli_query($dbconn, "UPDATE Survey SET NeedToSendToSciStarter='0' WHERE ID='" . $row["SurveyID"] . "'");
		}
	}
	mysqli_close($dbconn);
?>
