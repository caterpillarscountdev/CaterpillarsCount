<?php
	require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
	require_once("/opt/app-root/src/php/submitToINaturalist.php");
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$BATCH_SIZE = 1;//You should move/alter `NeedToSendToINaturalist` and `Processing` database updates if you change $BATCH_SIZE. That extends into the submitToINaturalist.php file as well. I just left the code to assume $BATCH_SIZE is 1.

	//If we're already submitting to iNaturalist, don't execute this call.
	$query = mysqli_query($dbconn, "SELECT `Processing` FROM `CronJobStatus` WHERE `Name`='iNaturalistSurveySubmission'");
	if(mysqli_num_rows($query) > 0){
		if(intval(mysqli_fetch_assoc($query)["Processing"]) == 1){
			mysqli_close($dbconn);
			die();
		}
	}
	else{
		mysqli_close($dbconn);
		die();
	}

	//Otherwise,
	//Mark that we're submitting to iNaturalist
	$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='1', `UTCLastCalled`=NOW() WHERE `Name`='iNaturalistSurveySubmission'");
	
	//Get batch
	$query = mysqli_query($dbconn, "SELECT ID FROM ArthropodSighting WHERE NeedToSendToINaturalist='1' LIMIT " . $BATCH_SIZE);
	$ids = array("0");
	if(mysqli_num_rows($query) > 0){
		while($idRow = mysqli_fetch_assoc($query)){
			$ids[] = $idRow["ID"];
		}
	}
	else{
		//Mark that we're finished submitting to iNaturalist
		$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistSurveySubmission'");
		mysqli_close($dbconn);
		die();
	}
	
	$idMatchSQL = "='" . $ids[1] . "'";
	if($BATCH_SIZE != 1){
		$idMatchSQL = " IN (" . implode(", ", $ids) . ")";
	}
	
	//Submit batch to iNaturalist
	$query = mysqli_query($dbconn, "SELECT ArthropodSighting.ID AS ArthropodSightingID, User.INaturalistObserverID, User.Hidden, Plant.Code, Survey.LocalDate, Survey.ObservationMethod, Survey.Notes AS SurveyNotes, Survey.WetLeaves, ArthropodSighting.OriginalGroup, ArthropodSighting.Hairy, ArthropodSighting.Rolled, ArthropodSighting.Tented, ArthropodSighting.OriginalSawfly, ArthropodSighting.OriginalBeetleLarva, ArthropodSighting.Quantity, ArthropodSighting.Length, ArthropodSighting.PhotoURL, ArthropodSighting.Notes AS ArthropodSightingNotes, Survey.NumberOfLeaves, Survey.AverageLeafLength, Survey.HerbivoryScore FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN `User` ON Survey.UserFKOfObserver=`User`.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE ArthropodSighting.ID" . $idMatchSQL . " ORDER BY RAND() LIMIT " . $BATCH_SIZE);
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			$observerID = $row["INaturalistObserverID"];
			if(filter_var($row["Hidden"], FILTER_VALIDATE_BOOLEAN)){
				$observerID = "anonymous";
			}
			submitINaturalistObservation($dbconn, $ids[1], $observerID, $row["Code"], $row["LocalDate"], $row["ObservationMethod"], $row["SurveyNotes"], filter_var($row["WetLeaves"], FILTER_VALIDATE_BOOLEAN), $row["OriginalGroup"], filter_var($row["Hairy"], FILTER_VALIDATE_BOOLEAN), filter_var($row["Rolled"], FILTER_VALIDATE_BOOLEAN), filter_var($row["Tented"], FILTER_VALIDATE_BOOLEAN), filter_var($row["OriginalSawfly"], FILTER_VALIDATE_BOOLEAN), filter_var($row["OriginalBeetleLarva"], FILTER_VALIDATE_BOOLEAN), intval($row["Quantity"]), intval($row["Length"]), "/" . $row["PhotoURL"], $row["ArthropodSightingNotes"], intval($row["NumberOfLeaves"]), intval($row["AverageLeafLength"]), intval($row["HerbivoryScore"]));
		}
	}
	mysqli_close($dbconn);
?>
