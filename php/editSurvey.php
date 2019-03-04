<?php
	require_once('orm/User.php');
	require_once('orm/Plant.php');
	require_once('orm/Survey.php');
	
	$email = $_POST["email"];
	$salt = $_POST["salt"];
	$plantCode = $_POST["plantCode"];
	$sitePassword = $_POST["sitePassword"];
	$surveyID = $_POST["surveyID"];
	$date = $_POST["date"];
	$time = $_POST["time"];
	$observationMethod = $_POST["observationMethod"];
	$siteNotes = $_POST["siteNotes"];			//String
	$wetLeaves = $_POST["wetLeaves"];			//"true" or "false"
	$arthropodData = json_decode($_POST["arthropodData"]);		//JSON
	//$plantSpecies = $_POST["plantSpecies"];
	$numberOfLeaves = $_POST["numberOfLeaves"];		//number
	$averageLeafLength = $_POST["averageLeafLength"];	//number
	$herbivoryScore = $_POST["herbivoryScore"];		//String
	
  	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$plant = Plant::findByCode($plantCode);
		if(!is_object($plant)){
			die("false|Enter a valid survey location code.");
		}
		
		$site = $plant->getSite();
		if($site->validateUser($user, $sitePassword)){
			$survey = Survey::findByID($surveyID);
			if(is_object($survey) && get_class($survey) == "Survey"){
				if(!($site->isAuthority($user) || $survey->getSubmissionTimestamp() >= (time() - (2 * 7 * 24 * 60 * 60)) || User::isSuperUser($user->getEmail()))){
					die("false|For sites that you do not own or manage, you only have 2 weeks after submitting a survey to come back and edit it. You can no longer edit this survey. If you really must edit this survey, ask your site director to do so for you.");
				}
				//edit survey
				$survey->setPlant($plant);
				$survey->setLocalDate($date);
				$survey->setLocalTime($time);
				$survey->setObservationMethod($observationMethod);
				$survey->setNotes($notes);
				$survey->setWetLeaves($wetLeaves);
				//$survey->setPlantSpecies($plantSpecies);
				$survey->setNumberOfLeaves($numberOfLeaves);
				$survey->setAverageLeafLength($averageLeafLength);
				$survey->setHerbivoryScore($herbivoryScore);
				$survey->setNotes($siteNotes);
				$arthropodData = json_decode($_POST["arthropodData"]);		//JSON
				
				//delete old arthropod sightings
				$arthropodSightings = $survey->getArthropodSightings();
				for($i = 0; $i < count($arthropodSightings); $i++){
					$arthropodSightings[$i]->permanentDelete();
				}
				
				//add new arthropod sightings
				for($i = 0; $i < count($arthropodData); $i++){
					$arthropodSightingFailures = "";
					$arthropodSighting = $survey->addArthropodSighting($arthropodData[$i][0], $arthropodData[$i][1], $arthropodData[$i][2], $arthropodData[$i][3], $arthropodData[$i][4], $arthropodData[$i][5], $arthropodData[$i][6], $arthropodData[$i][7], $arthropodData[$i][8]);
					if(is_object($arthropodSighting) && get_class($arthropodSighting) == "ArthropodSighting"){
						if($arthropodData[$i][9] != ""){
							$arthropodSighting->setPhotoURL($arthropodData[$i][9]);
						}
					}
					else{
						$arthropodSightingFailures .= $arthropodSighting;
					}
				}
				if($arthropodSightingFailures != ""){
					die("false|" . $arthropodSightingFailures);
				}
				die("true|");
			}
			die("false|" . $survey);
		}
		die("false|Enter a valid site password.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
