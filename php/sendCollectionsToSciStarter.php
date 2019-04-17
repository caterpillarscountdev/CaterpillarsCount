<?php
	require_once("orm/resources/Keychain.php");
	require_once("submitToSciStarter.php");

	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT Survey.ID AS SurveyID, User.Email, Survey.LocalDate, Survey.`LocalTime` FROM Survey JOIN User ON Survey.UserFKOfObserver=User.ID WHERE Survey.NeedToSendToSciStarter='1'");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			submitToSciStarter($row["Email"], "collection", null, $row["LocalDate"] . "T" . $row["LocalTime"], 300, 2, null);
			mysqli_query($dbconn, "UPDATE Survey SET NeedToSendToSciStarter='0' WHERE ID='" . $row["SurveyID"] . "'");
		}
	}
	mysqli_close($dbconn);
?>
