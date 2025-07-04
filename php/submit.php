<?php
	header('Access-Control-Allow-Origin: *');
        require_once('orm/resources/Keychain.php');
	require_once('orm/User.php');
	require_once('orm/Plant.php');
	require_once('orm/Survey.php');
	
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$plantCode = custgetparam("plantCode");
	$sitePassword = custgetparam("sitePassword");
	$date = custgetparam("date");
	$time = custgetparam("time");
	$observationMethod = custgetparam("observationMethod");
	$siteNotes = custgetparam("siteNotes");			//String
	$wetLeaves = custgetparam("wetLeaves");			//"true" or "false"
	$arthropodData = json_decode(custgetparam("arthropodData"));		//JSON
	$plantSpecies = custgetparam("plantSpecies");
	$numberOfLeaves = custgetparam("numberOfLeaves");		//number
	$averageLeafLength = custgetparam("averageLeafLength");	//number
	$herbivoryScore = custgetparam("herbivoryScore");
	$averageNeedleLength = array_key_exists("averageNeedleLength", $_POST) ? $_POST["averageNeedleLength"] : -1;
	$linearBranchLength = array_key_exists("linearBranchLength", $_POST) ? $_POST["linearBranchLength"] : -1;
	$submittedThroughApp = custgetparam("submittedThroughApp");
	
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
					return $arthropodSighting->setPhotoURL($name, true);
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
                  $conn = (new Keychain)->getDatabaseConnection();
                  mysqli_begin_transaction($conn);
                  try {
                
			$user->setObservationMethodPreset($site, $observationMethod);

                        //check for duplicate first, by [User, Plant, Date, Time] and count of arthropods
                        $duplicateSQL = "SELECT Survey.ID, COUNT(ArthropodSighting.ID) AS ArthroCount FROM Survey JOIN User ON Survey.UserFKOfObserver = User.ID JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK = Site.ID JOIN ArthropodSighting ON Survey.ID = ArthropodSighting.SurveyFK WHERE Plant.Code='$plantCode' AND User.ID='{$user->getID()}' AND ObservationMethod='$observationMethod' AND Survey.LocalDate='$date' AND Survey.LocalTime='$time' GROUP BY Survey.ID;";
                        $query = mysqli_query($conn, $duplicateSQL);

                        if(mysqli_num_rows($query) > 0){
                          $row = mysqli_fetch_assoc($query);
                          if ($row["ArthroCount"] == count($arthropodData)) {
                            // silently ignore this duplicate submission
                            mysqli_rollback($conn);
                            die("true|");
                          }
                        }

			//submit data to database
			$survey = Survey::create($user, $plant, $date, $time, $observationMethod, $siteNotes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp);
			
			if(is_object($survey) && get_class($survey) == "Survey"){
				//$arthropodData = orderType, orderLength, orderQuantity, orderNotes, pupa, hairy, leafRoll, silkTent, sawfly, beetleLarva, fileInput
				$arthropodSightingFailures = "";
				for($i = 0; $i < count($arthropodData); $i++){
					//if the user is submitting from an outdated app that doesn't include the pupa checkbox
					if(count($arthropodData[$i]) < 10 || !is_bool($arthropodData[$i][9])){
						//set pupa to false by default
						array_splice($arthropodData[$i], 4, 0, array(false));
					}
					
					if($arthropodData[$i][0] != "moths"){
						$arthropodData[$i][4] = false;
					}
					if($arthropodData[$i][0] != "caterpillar"){
						$arthropodData[$i][5] = false;
						$arthropodData[$i][6] = false;
						$arthropodData[$i][7] = false;
					}
					if($arthropodData[$i][0] != "bee"){
						$arthropodData[$i][8] = false;
					}
					if($arthropodData[$i][0] != "beetle"){
						$arthropodData[$i][9] = false;
					}
					$arthropodSighting = $survey->addArthropodSighting($arthropodData[$i][0], $arthropodData[$i][1], $arthropodData[$i][2], $arthropodData[$i][3], $arthropodData[$i][4], $arthropodData[$i][5], $arthropodData[$i][6], $arthropodData[$i][7], $arthropodData[$i][8], $arthropodData[$i][9]);
					if(is_object($arthropodSighting) && get_class($arthropodSighting) == "ArthropodSighting"){
                                          if(array_key_exists('file' . $i, $_FILES)) {
                                              $attachResult = attachPhotoToArthropodSighting($_FILES['file' . $i], $arthropodSighting);
                                              if($attachResult != "File not uploaded." && $attachResult !== true){
                                                $arthropodSightingFailures .= strval($attachResult);
                                              }
                                          }
                                        }
					else{
						$arthropodSightingFailures .= $arthropodSighting;
					}
				}
				
				if($arthropodSightingFailures == ""){
                                  mysqli_commit($conn);
                                  die("true|");
				}
				die("false|" . $arthropodSightingFailures);
			}
			die("false|" . $survey);
                  } catch (Exception $exception) {
                    mysqli_rollback($conn);
                    throw $exception;
                  }
		}
		die("false|Enter a valid password.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
