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

	function explainError($fileError){
		if($fileError == UPLOAD_ERR_INI_SIZE){return 'The uploaded file exceeds the upload_max_filesize directive in php.ini. ';}
		if($fileError == UPLOAD_ERR_FORM_SIZE){return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form. ';}
		if($fileError == UPLOAD_ERR_PARTIAL){return 'The uploaded file was only partially uploaded. ';}
		if($fileError == UPLOAD_ERR_NO_FILE){return 'No file was uploaded. ';}
		if($fileError == UPLOAD_ERR_NO_TMP_DIR){return 'Missing a temporary folder. Introduced in PHP 5.0.3. ';}
		if($fileError == UPLOAD_ERR_CANT_WRITE){return 'Failed to write file to disk. Introduced in PHP 5.1.0. ';}
		if($fileError == UPLOAD_ERR_EXTENSION){return 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0. ';}
		return 'Upload unsuccessful. ';
	}
		
	function attachPhotoToArthropodSighting($file, $arthropodSighting){
		if(!is_uploaded_file($file['tmp_name'])){
			return "File not uploaded. ";
		}
		
		$fileName = $file['name'];
		$fileType = $file['type'];
		$fileType = str_replace("image/", "", strToLower($fileType));
		$fileError = $file['error'];
		$fileContent = file_get_contents($file['tmp_name']);
		$path = "../images/arthropods/";
		$name = $arthropodSighting->getID() . "." . $fileType;
			
		if($fileError == UPLOAD_ERR_OK){
			//Processes your file here
			if(file_exists($path . $name) && !unlink($path . $name)){
				return "Could not overwrite arthropod photo. ";
			}
				
			if(in_array($fileType, array("png", "jpg", "jpeg", "gif"))){
				if(move_uploaded_file($file["tmp_name"], $path . $name)){
					return $arthropodSighting->setPhotoURL($name, true);
				}
				return "Unable to transfer file to server. ";
			}
			return "file type must be an image. ";
		}
		return explainError($fileError);
	}
	
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
				
				//update arthropod sightings
				$arthropodSightings = $survey->getArthropodSightings();
				$failures = "";
				$existingArthropodSightingIDs = array();
				for($i = 0; $i < count($arthropodSightings); $i++){
					for($j = 0; $j < count($arthropodData); $j++){
						if(strval($arthropodSightings[$i]->getID()) == strval($arthropodData[$j][0])){
							$existingArthropodSightingIDs[] = strval($arthropodData[$j][0]);
							
							$updateResult = $arthropodSightings[$i]->setAllEditables($arthropodData[$j][1], $arthropodData[$j][2], $arthropodData[$j][3], $arthropodData[$j][4], $arthropodData[$j][5], $arthropodData[$j][6], $arthropodData[$j][7], $arthropodData[$j][8], $arthropodData[$j][9]);
							if($updateResult === false){
								$failures .= "Could not locate " . $arthropodData[$j][1] . " sighting record. ";
							}
							else if($updateResult !== true){
								$failures .= $updateResult;
							}
							else{
								//if we successfully set all editables
								//add a photo to the arthropod sighting if it exists
								$attachResult = attachPhotoToArthropodSighting($_FILES['file' . $j], $arthropodSightings[$i]);
								if($attachResult != "File not uploaded. " && $attachResult !== true){
									$failures .= "Photo #" . $j . ": " . strval($attachResult);
								}
							}
						}
					}
				}
				for($i = 0; $i < count($arthropodSightings); $i++){
					if(!in_array(strval($arthropodSightings[$i]->getID()), $existingArthropodSightingIDs)){
						if(!$arthropodSightings[$i]->permanentDelete()){
							$failures .= "Could not delete arthropod sighting #" . $i . ". ";
						}
					}
				}
				for($i = 0; $i < count($arthropodData); $i++){
					if(!in_array(strval($arthropodData[$i][0]), $existingArthropodSightingIDs)){
						$newArthropodSighting = $survey->addArthropodSighting($arthropodData[$i][1], $arthropodData[$i][2], $arthropodData[$i][3], $arthropodData[$i][4], $arthropodData[$i][5], $arthropodData[$i][6], $arthropodData[$i][7], $arthropodData[$i][8], $arthropodData[$i][9]);
						if(is_object($newArthropodSighting) && get_class($newArthropodSighting) == "ArthropodSighting"){
							$attachResult = attachPhotoToArthropodSighting($_FILES['file' . $i], $newArthropodSighting);
							if($attachResult != "File not uploaded. " && $attachResult !== true){
								$failures .= "Photo #" . $i . ": " . strval($attachResult);
							}
						}
						else{
							$failures .= "Could not add arthropod sighting #$i. ";
						}
					}
				}
				if($failures != ""){
					die("false|" . $failures);
				}
				die("true|");
			}
			die("false|" . $survey);
		}
		die("false|Enter a valid site password.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
