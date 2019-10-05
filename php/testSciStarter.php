<?php
  //109179
  require_once("orm/resources/Keychain.php");
	$BATCH_SIZE = 1;//You should move/alter `NeedToSendToSciStarter` and `Processing` database updates if you change $BATCH_SIZE. That extends into the submitToSciStarter.php file as well. I just left the code to assume $BATCH_SIZE is 1.
	$dbconn = (new Keychain)->getDatabaseConnection();
  //Submit
	$query = mysqli_query($dbconn, "SELECT Survey.ID AS SurveyID, User.Email, Survey.LocalDate, Survey.`LocalTime` FROM Survey JOIN User ON Survey.UserFKOfObserver=User.ID WHERE Survey.ID='109223' LIMIT " . $BATCH_SIZE);
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
      $surveyID = '109223';
      $email = $row["Email"];
      $type = "collection";
      $where = null;
      $when = $row["LocalDate"] . "T" . $row["LocalTime"];
      $duration = 300;
      $magnitude = 2;
      $extra = null;
      $KEY = getenv("SciStarterKey");
		$ch = curl_init("https://scistarter.org/api/profile/id?hashed=" . hash("sha256", $email) . "&key=" . $KEY);
		//curl_setopt($ch, CURLOPT_REFERER, (new Keychain)->getRoot());
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$profileID = json_decode(curl_exec($ch), true)["scistarter_profile_id"];
		curl_close($ch);
		$extraParams = "";
		if($where !== null){
			$extraParams .= "&where=" . $where;
		}
		if($when !== null){
			$extraParams .= "&when=" . $when;
		}
		if($duration !== null){
			$extraParams .= "&duration=" . $duration;
		}
		if($magnitude !== null){
			$extraParams .= "&magnitude=" . $magnitude;
		}
		if($extra !== null){
			$extraParams .= "&extra=" . $extra;
		}
		$ch = curl_init("https://scistarter.org/api/record_event?key=" . $KEY);
			//curl_setopt($ch, CURLOPT_REFERER, (new Keychain)->getRoot());
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "profile_id=" . $profileID . "&project_id=" . getenv("SciStarterProjectID") . "&type=" . $type . $extraParams);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close ($ch);
    die("[" . $response . "]");
		}
	}
	mysqli_close($dbconn);
  
  
  
  
  
  
  
    
    ?>
