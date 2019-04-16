<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('submitToINaturalist.php');
	require_once('submitToSciStarter.php');
	require_once('orm/User.php');
	require_once('orm/Plant.php');
	require_once('orm/Survey.php');
	
	$email = $_POST["email"];
	$salt = $_POST["salt"];
	$plantCode = $_POST["plantCode"];
	$sitePassword = $_POST["sitePassword"];
	$date = $_POST["date"];
	$time = $_POST["time"];
	$observationMethod = $_POST["observationMethod"];
	$siteNotes = $_POST["siteNotes"];			//String
	$wetLeaves = $_POST["wetLeaves"];			//"true" or "false"
	$arthropodData = json_decode($_POST["arthropodData"]);		//JSON
	$plantSpecies = $_POST["plantSpecies"];
	$numberOfLeaves = $_POST["numberOfLeaves"];		//number
	$averageLeafLength = $_POST["averageLeafLength"];	//number
	$herbivoryScore = $_POST["herbivoryScore"];		//String
	$submittedThroughApp = $_POST["submittedThroughApp"];
	
	function explainError($fileError){
		if($fileError == UPLOAD_ERR_INI_SIZE){return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';}
		if($fileError == UPLOAD_ERR_FORM_SIZE){return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';}
		if($fileError == UPLOAD_ERR_PARTIAL){return 'The uploaded file was only partially uploaded';}
		if($fileError == UPLOAD_ERR_NO_FILE){return 'No file was uploaded';}
		if($fileError == UPLOAD_ERR_NO_TMP_DIR){return 'Missing a temporary folder. Introduced in PHP 5.0.3';}
		if($fileError == UPLOAD_ERR_CANT_WRITE){return 'Failed to write file to disk. Introduced in PHP 5.1.0';}
		if($fileError == UPLOAD_ERR_EXTENSION){return 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0';}
		return 'Upload unsuccessful';
	}
		
	function attachPhotoToArthropodSighting($file, $arthropodSighting){
		if(!is_uploaded_file($file['tmp_name'])){
			return "File not uploaded.";
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
				return "Could not overwrite arthropod photo.";
			}
				
			if(in_array($fileType, array("png", "jpg", "jpeg", "gif"))){
				if(move_uploaded_file($file["tmp_name"], $path . $name)){
					return $arthropodSighting->setPhotoURL($name);
				}
				return "Unable to transfer file to server";
			}
			return "file type must be an image";
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
		if(!$site->getActive()){
			$site->setActive(true);
		}
		if($site->validateUser($user, $sitePassword)){
			$user->setObservationMethodPreset($site, $observationMethod);
			//submit data to database
			$survey = Survey::create($user, $plant, $date, $time, $observationMethod, $siteNotes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $submittedThroughApp);
			
			if(is_object($survey) && get_class($survey) == "Survey"){
				//$arthropodData = orderType, orderLength, orderQuantity, orderNotes, hairy, leafRoll, silkTent, sawfly, beetleLarva, fileInput
				$arthropodSightingFailures = "";
				for($i = 0; $i < count($arthropodData); $i++){
					if($arthropodData[$i][0] != "caterpillar"){
						$arthropodData[$i][4] = false;
						$arthropodData[$i][5] = false;
						$arthropodData[$i][6] = false;
					}
					if($arthropodData[$i][0] != "bee"){
						$arthropodData[$i][7] = false;
					}
					if($arthropodData[$i][0] != "beetle"){
						$arthropodData[$i][8] = false;
					}
					$arthropodSighting = $survey->addArthropodSighting($arthropodData[$i][0], $arthropodData[$i][1], $arthropodData[$i][2], $arthropodData[$i][3], $arthropodData[$i][4], $arthropodData[$i][5], $arthropodData[$i][6], $arthropodData[$i][7], $arthropodData[$i][8]);
					if(is_object($arthropodSighting) && get_class($arthropodSighting) == "ArthropodSighting"){
						$attachResult = attachPhotoToArthropodSighting($_FILES['file' . $i], $arthropodSighting);
						if($attachResult != "File not uploaded." && $attachResult !== true){
							$arthropodSightingFailures .= strval($attachResult);
						}
						if($arthropodSighting->getPhotoURL() != "" && $site->getName() != "Example Site"){
							$observerID = $user->getINaturalistObserverID();
							if($user->getHidden()){
								$observerID = "anonymous";
							}
							submitINaturalistObservation($observerID, $plant->getCode(), $survey->getLocalDate(), $survey->getObservationMethod(), $survey->getNotes(), $survey->getWetLeaves(), $arthropodSighting->getGroup(), $arthropodSighting->getHairy(), $arthropodSighting->getRolled(), $arthropodSighting->getTented(), $arthropodSighting->getQuantity(), $arthropodSighting->getLength(), $arthropodSighting->getPhotoURL(), $arthropodSighting->getNotes(), $survey->getNumberOfLeaves(), $survey->getAverageLeafLength(), $survey->getHerbivoryScore());
						}
					}
					else{
						$arthropodSightingFailures .= $arthropodSighting;
					}
				}
				
				if($site->getName() != "Example Site"){
					//submitToSciStarter($user->getEmail(), "collection", null, $survey->getLocalDate() . "T" . $survey->getLocalTime(), 300, 2, null);
				}
				
				if($arthropodSightingFailures != ""){
					die("false|" . $arthropodSightingFailures);
				}
				die("true|");
			}
			die("false|" . $survey);
		}
		die("false|Enter a valid password.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
