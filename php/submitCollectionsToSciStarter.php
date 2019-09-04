<?php
	require_once("/opt/app-root/src/php/orm/resources/Keychain.php");
	require_once("/opt/app-root/src/php/submitToSciStarter.php");

	$BATCH_SIZE = 1;

	$dbconn = (new Keychain)->getDatabaseConnection();

	//If we're already submitting to SciStarter, don't execute this call.
	$query = mysqli_query($dbconn, "SELECT `Processing` FROM `CronJobStatus` WHERE `Name`='SciStarter'");
	if(mysqli_num_rows($query) > 0){
		if(intval(mysqli_fetch_assoc($query)["Processing"]) == 1){
			mysqli_close($dbconn);
			die();
		}
	}

	//Otherwise,
	//Mark that we're submitting to SciStarter
	$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='1', `UTCLastCall`=NOW() WHERE `Name`='SciStarter'");

	//Get survey id
	$ids = array("0");
	$query = mysqli_query($dbconn, "SELECT ID FROM Survey WHERE NeedToSendToSciStarter='1' LIMIT " . $BATCH_SIZE);
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			$ids[] = $row["ID"];
		}
	}

	$idMatchSQL = "='" . $ids[1] . "'";
	if($BATCH_SIZE != 1){
		$idMatchSQL = " IN (" . implode(", ", $ids) . ")";
	}
	
	//Mark as submitted
	mysqli_query($dbconn, "UPDATE Survey SET NeedToSendToSciStarter='0' WHERE ID" . $idMatchSQL . " LIMIT " . $BATCH_SIZE);
	
	//Submit
	$query = mysqli_query($dbconn, "SELECT Survey.ID AS SurveyID, User.Email, Survey.LocalDate, Survey.`LocalTime` FROM Survey JOIN User ON Survey.UserFKOfObserver=User.ID WHERE Survey.ID" . $idMatchSQL . " LIMIT " . $BATCH_SIZE);
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			submitToSciStarter($row["Email"], "collection", null, $row["LocalDate"] . "T" . $row["LocalTime"], 300, 2, null);
		}
	}
	mysqli_close($dbconn);
?>
