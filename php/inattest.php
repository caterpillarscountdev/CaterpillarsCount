<?php
	require_once("orm/Plant.php");
	require_once('orm/resources/Keychain.php');
	
	function rp($search, $replace, $subject){
		return str_replace($search, $replace, $subject);
	}
	function myLocalUrlEncode($string) {
	    return rp(" ", "%20", rp(">", "%3E", rp("!", "%21", rp("*", "%2A", rp("(", "%28", rp(")", "%29", rp(";", "%3B", rp(":", "%3A", rp("@", "%40", rp("&", "%26", rp("=", "%3D", rp("+", "%2B", rp("$", "%24", rp(",", "%2C", rp("/", "%2F", rp("?", "%3F", rp("%", "%25", $string)))))))))))))))));
	}
    	function cleanParameter($param){
		$param = myLocalUrlEncode(preg_replace('!\s+!', ' ', trim(preg_replace('/[^a-zA-Z0-9.!*();:@&=+$,\/?%>-]/', ' ', trim((string)$param)))));
		if($param == ""){
			return "None";
		}
		return $param;
	}
	
	function submitINaturalistObservation($dbconn, $arthropodSightingID, $userTag, $plantCode, $date, $observationMethod, $surveyNotes, $wetLeaves, $order, $hairy, $rolled, $tented, $beetleLarva, $arthropodQuantity, $arthropodLength, $arthropodPhotoURL, $arthropodNotes, $numberOfLeaves, $averageLeafLength, $herbivoryScore){
		//GET AUTHORIZATION
		$ch = curl_init('https://www.inaturalist.org/oauth/token');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "client_id=" . getenv("iNaturalistAppID") . "&client_secret=" . getenv("iNaturalistAppSecret") . "&grant_type=password&username=caterpillarscount&password=" . getenv("iNaturalistPassword"));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$token = json_decode(curl_exec($ch), true)["access_token"];echo $token;
		curl_close ($ch);
		
		//CREATE OBSERVATION
		$plant = Plant::findByCode($plantCode);
		$site = $plant->getSite();
		
		if(trim($surveyNotes) !== "" && trim($arthropodNotes) !== ""){
			$surveyNotes = trim($surveyNotes) . " | " . trim($arthropodNotes);
		}
		else if(trim($surveyNotes) == ""){
			$surveyNotes = trim($arthropodNotes);
		}
		
		$newOrders = array(
			"ant" => "Ants",
			"aphid" => "Sternorrhyncha",
			"bee" => "Hymenoptera",
			"beetle" => "Beetles",
			"caterpillar" => "Lepidoptera",
			"daddylonglegs" => "Daddy longlegs",
			"fly" => "Flies",
			"grasshopper" => "Orthoptera",
			"leafhopper" => "Auchenorrhyncha",
			"moths" => "Lepidoptera",
			"spider" => "Spiders",
			"truebugs" => "True bugs",
			"other" => "Arthropoda",
			"unidentified" => "Arthropoda"
		);
		$newOrder = $order;
		if(array_key_exists($order, $newOrders)){
			$newOrder = $newOrders[$order];
		}
		
		$url = "http://www.inaturalist.org/observations.json?observation[species_guess]=" . cleanParameter($newOrder) . "&observation[id_please]=1&observation[observed_on_string]=" . cleanParameter($date) . "&observation[place_guess]=" . cleanParameter($site->getName()) . "&observation[latitude]=" . cleanParameter($site->getLatitude()) . "&observation[longitude]=" . cleanParameter($site->getLongitude());
		if(trim($arthropodNotes) != ""){
			$url .= "&observation[description]=" . cleanParameter($arthropodNotes);
		}
		$herbivoryScores = array("None", "0-5%", "6-10%", "11-25%", "> 25%");
		$params = [["9677", $averageLeafLength . " cm"], ["2926", $numberOfLeaves], ["9676", (($wetLeaves) ? 'Yes' : 'No')], ["3020", $observationMethod], ["9675", $surveyNotes], ["9670", $arthropodLength . " mm"], ["1194", $site->getName()], ["9671", $plant->getCircle()], ["1422", $plantCode], ["6609", $plant->getSpecies()], ["9672", $herbivoryScores[intval($herbivoryScore)]], ["544", $arthropodQuantity], ["9673", $userTag]];
		if($order == "caterpillar"){
			$params[] = ["9678", (($hairy) ? 'Yes' : 'No')];
			$params[] = ["9679", (($rolled) ? 'Yes' : 'No')];
			$params[] = ["9680", (($tented) ? 'Yes' : 'No')];
		}
		$observationFieldIDString = "&observation[observation_field_values_attributes]";
		for($i = 0; $i < count($params); $i++){
			$url .= $observationFieldIDString . "[" . $i . "][observation_field_id]=" . cleanParameter($params[$i][0]) . $observationFieldIDString . "[" . $i . "][value]=" . cleanParameter($params[$i][1]);
		}
		if($order == "caterpillar"){
			$url .= $observationFieldIDString . "[" . count($params) . "][observation_field_id]=3441" . $observationFieldIDString . "[" . count($params) . "][value]=caterpillar";
			$url .= $observationFieldIDString . "[" . (count($params) + 1) . "][observation_field_id]=325" . $observationFieldIDString . "[" . (count($params) + 1) . "][value]=larva";
		}
		if($order == "moths"){
			$url .= $observationFieldIDString . "[" . count($params) . "][observation_field_id]=3441" . $observationFieldIDString . "[" . count($params) . "][value]=adult";
			$url .= $observationFieldIDString . "[" . (count($params) + 1) . "][observation_field_id]=325" . $observationFieldIDString . "[" . (count($params) + 1) . "][value]=adult";
		}
		if($order == "beetle" && $beetleLarva){
			$url .= $observationFieldIDString . "[" . count($params) . "][observation_field_id]=325" . $observationFieldIDString . "[" . count($params) . "][value]=larva";
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=" . $token);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
		$observation = json_decode(curl_exec($ch), true)[0];echo "<br/>" . $observation;
		curl_close ($ch);
		
		//ADD PHOTO TO OBSERVATION
		$ch = curl_init();
		$arthropodPhotoPath = "../images/arthropods/" . $arthropodPhotoURL;
		if(strpos($arthropodPhotoURL, '/') !== false){
			$arthropodPhotoPath = "/opt/app-root/src/images/arthropods" . $arthropodPhotoURL;
		}
		
		if(function_exists('curl_file_create')){//PHP 5.5+
			$cFile = curl_file_create($arthropodPhotoPath);
		}
		else{
			curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
			$cFile = '@' . realpath($arthropodPhotoPath);
		}
		$post = array('access_token' => $token, 'observation_photo[observation_id]' => $observation["id"], 'file'=> $cFile);
		curl_setopt($ch, CURLOPT_URL,"http://www.inaturalist.org/observation_photos");
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$photoAddResponse = curl_exec($ch);echo "<br/>" . $photoAddResponse;
		curl_close ($ch);
		
		if($photoAddResponse !== "Just making sure that the exec is complete."){
			//LINK OBSERVATION TO CATERPILLARS COUNT PROJECT
			$ch = curl_init("http://www.inaturalist.org/project_observations");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=" . $token . "&project_observation[observation_id]=" . $observation["id"] . "&project_observation[project_id]=5443");
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$caterpillarsCountLinkResponse = curl_exec($ch);echo "<br/>" . $caterpillarsCountLinkResponse;
			curl_close ($ch);
			
			if($caterpillarsCountLinkResponse !== "Just making sure that the exec is complete."){
				if($order == "caterpillar"){
					//LINK OBSERVATION TO CATERPILLARS OF EASTERN NORTH AMERICA PROJECT IF IT'S IN AN ALLOWED REGION
					$ch = curl_init("http://www.inaturalist.org/project_observations");
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=" . $token . "&project_observation[observation_id]=" . $observation["id"] . "&project_observation[project_id]=9210");
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$caterpillarsOfEasternNALinkResponse = curl_exec($ch);echo "<br/>" . $caterpillarsOfEasternNALinkResponse;
					curl_close ($ch);

					if($caterpillarsOfEasternNALinkResponse !== "Just making sure that the exec is complete."){
						//Mark this ArthropodSighting as completed
						if(is_string($observation["id"]) && $observation["id"] != ""){
							mysqli_query($dbconn, "UPDATE ArthropodSighting SET NeedToSendToINaturalist='0', INaturalistID='" . $observation["id"] . "' WHERE ID='" . $arthropodSightingID . "' LIMIT 1");
						}
						
						//Mark that we're finished submitting to iNaturalist
						$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistSurveySubmission'");
					}
				}
				else{
					//Mark this ArthropodSighting as completed and save the INaturalistID to our database
					if(is_string($observation["id"]) && $observation["id"] != ""){
						mysqli_query($dbconn, "UPDATE ArthropodSighting SET NeedToSendToINaturalist='0', INaturalistID='" . $observation["id"] . "' WHERE ID='" . $arthropodSightingID . "' LIMIT 1");
					}
					
					//Mark that we're finished submitting to iNaturalist
					$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistSurveySubmission'");
				}
			}
		}
	}
  
	
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
	$query = mysqli_query($dbconn, "SELECT ArthropodSighting.ID AS ArthropodSightingID, User.INaturalistObserverID, User.Hidden, Plant.Code, Survey.LocalDate, Survey.ObservationMethod, Survey.Notes AS SurveyNotes, Survey.WetLeaves, ArthropodSighting.OriginalGroup, ArthropodSighting.Hairy, ArthropodSighting.Rolled, ArthropodSighting.Tented, ArthropodSighting.OriginalBeetleLarva, ArthropodSighting.Quantity, ArthropodSighting.Length, ArthropodSighting.PhotoURL, ArthropodSighting.Notes AS ArthropodSightingNotes, Survey.NumberOfLeaves, Survey.AverageLeafLength, Survey.HerbivoryScore FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN `User` ON Survey.UserFKOfObserver=`User`.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE ArthropodSighting.ID" . $idMatchSQL . " LIMIT " . $BATCH_SIZE);
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			$observerID = $row["INaturalistObserverID"];
			if(filter_var($row["Hidden"], FILTER_VALIDATE_BOOLEAN)){
				$observerID = "anonymous";
			}
			submitINaturalistObservation($dbconn, $ids[1], $observerID, $row["Code"], $row["LocalDate"], $row["ObservationMethod"], $row["SurveyNotes"], filter_var($row["WetLeaves"], FILTER_VALIDATE_BOOLEAN), $row["OriginalGroup"], filter_var($row["Hairy"], FILTER_VALIDATE_BOOLEAN), filter_var($row["Rolled"], FILTER_VALIDATE_BOOLEAN), filter_var($row["Tented"], FILTER_VALIDATE_BOOLEAN), filter_var($row["OriginalBeetleLarva"], FILTER_VALIDATE_BOOLEAN), intval($row["Quantity"]), intval($row["Length"]), "/" . $row["PhotoURL"], $row["ArthropodSightingNotes"], intval($row["NumberOfLeaves"]), intval($row["AverageLeafLength"]), intval($row["HerbivoryScore"]));
		}
	}
	mysqli_close($dbconn);
?>
