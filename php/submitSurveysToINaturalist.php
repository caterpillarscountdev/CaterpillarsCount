<?php
	require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
	require_once("/opt/app-root/src/php/submitToINaturalist.php");
	require_once("/opt/app-root/src/php/orm/resources/mailing.php");
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$BATCH_SIZE = 5;

	//If we're already submitting to iNaturalist, don't execute this call.
	$query = mysqli_query($dbconn, "SELECT `Processing` FROM `CronJobStatus` WHERE `Name`='iNaturalistSurveySubmission'");
	if(mysqli_num_rows($query) > 0){
		if(intval(mysqli_fetch_assoc($query)["Processing"]) == 1){
	
			echo("<!-- closing because we are already processing cron -->");
			die();
		}
	}
	else{

		echo("<!-- closing because no rows need processing -->");
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
		echo("<!-- update to iNat complete, no remaining records need to send to iNat -->");
		die();
	}
	
	$idMatchSQL = "='" . $ids[1] . "'";
	if($BATCH_SIZE != 1){
		$idMatchSQL = " IN (" . implode(", ", $ids) . ")";
	}
	
	//Submit batch to iNaturalist
	$query = mysqli_query($dbconn, "SELECT ArthropodSighting.ID AS ID, ArthropodSighting.INaturalistID, User.INaturalistObserverID, User.INaturalistAccessToken, User.ID as UserID, User.Hidden, Plant.Code, Survey.LocalDate, Survey.LocalTime, Survey.ObservationMethod, Survey.Notes AS SurveyNotes, Survey.WetLeaves, ArthropodSighting.OriginalGroup, ArthropodSighting.Hairy, ArthropodSighting.Rolled, ArthropodSighting.Tented, ArthropodSighting.OriginalSawfly, ArthropodSighting.OriginalBeetleLarva, ArthropodSighting.Quantity, ArthropodSighting.Length, ArthropodSighting.PhotoURL, ArthropodSighting.Notes AS ArthropodSightingNotes, Survey.NumberOfLeaves, Survey.AverageLeafLength, Survey.HerbivoryScore FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN `User` ON Survey.UserFKOfObserver=`User`.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE ArthropodSighting.ID" . $idMatchSQL . " LIMIT " . $BATCH_SIZE);
	if(mysqli_num_rows($query) > 0){
		echo("<!-- starting to send ArthropodSightings to iNat, count:" . mysqli_num_rows($query) . " -->");  
		while($row = mysqli_fetch_assoc($query)){
                  $userID = $row["UserID"];
                  $observerID = $row["INaturalistObserverID"];
                  $accessToken = $row["INaturalistAccessToken"];
                  $id = $row["ID"];
                  if(filter_var($row["Hidden"], FILTER_VALIDATE_BOOLEAN)){
                    $observerID = "anonymous";
                  }
                  if ($row['INaturalistID']) {
                    $body = "Error while processing " . $id . ": has iNat ID" . $row['INaturalistID'];
                    $body .= "\n\n" . print_r($row, true);
                    email("caterpillarscountdev@gmail.com", "Cron Error for iNat (dupe)", $body);
                    continue;
                  }
                  echo("<!-- sending one ID to iNat:" . $id . " -->");
                  try {
                    submitINaturalistObservation($dbconn, $id, $userID, $observerID, $accessToken, $row["Code"], $row["LocalDate"], $row["LocalTime"], $row["ObservationMethod"], $row["SurveyNotes"], filter_var($row["WetLeaves"], FILTER_VALIDATE_BOOLEAN), $row["OriginalGroup"], filter_var($row["Hairy"], FILTER_VALIDATE_BOOLEAN), filter_var($row["Rolled"], FILTER_VALIDATE_BOOLEAN), filter_var($row["Tented"], FILTER_VALIDATE_BOOLEAN), filter_var($row["OriginalSawfly"], FILTER_VALIDATE_BOOLEAN), filter_var($row["OriginalBeetleLarva"], FILTER_VALIDATE_BOOLEAN), intval($row["Quantity"]), intval($row["Length"]), "/" . $row["PhotoURL"], $row["ArthropodSightingNotes"], intval($row["NumberOfLeaves"]), intval($row["AverageLeafLength"]), intval($row["HerbivoryScore"]));
                  } catch (Exception $e) {
                    $body = "Error while processing " . $id . ": " . $e->getMessage();
                    $body .= "\n\n" . print_r($row, true);
                    email("caterpillarscountdev@gmail.com", "Cron Error for iNat $id", $body);
                  } finally {
                    //Mark that we're finished submitting to iNaturalist
                    mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistSurveySubmission'");
                  }
		}
	}
?>
