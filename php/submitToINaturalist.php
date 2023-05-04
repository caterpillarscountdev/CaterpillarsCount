<?php
        $debuginat = true;
	require_once("orm/Plant.php");
	require_once("orm/resources/mailing.php");
	
	function cleanParameter($param){
		$param = preg_replace('!\s+!', ' ', trim(preg_replace('/[^a-zA-Z0-9.!*();:@&=+$,\/?%>-]/', ' ', trim((string)$param))));
		if($param == ""){
			return "None";
		}
		return $param;
	}

	function guidv4($data){
		assert(strlen($data) == 16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	function submitINaturalistObservation($dbconn, $arthropodSightingID, $userTag, $plantCode, $date, $observationMethod, $surveyNotes, $wetLeaves, $order, $hairy, $rolled, $tented, $sawfly, $beetleLarva, $arthropodQuantity, $arthropodLength, $arthropodPhotoURL, $arthropodNotes, $numberOfLeaves, $averageLeafLength, $herbivoryScore){
		//GET AUTHORIZATION
		if ($debuginat==true) {  echo("<!-- submitINaturalistObservation .. init for ID " . $arthropodSightingID . " -->");}
		$ch = curl_init('https://www.inaturalist.org/oauth/token');
		if ($debuginat==true) {  echo("<!-- submitINaturalistObservation .. post oauth/token -->");}
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "client_id=" . getenv("iNaturalistAppID") . "&client_secret=" . getenv("iNaturalistAppSecret") . "&grant_type=password&username=caterpillarscount&password=" . getenv("iNaturalistPassword"));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($debuginat==true) {  echo("<!-- submitINaturalistObservation .. post set all opts -->");}
		$token = json_decode(curl_exec($ch), true)["access_token"];
		if ($debuginat==true) {  echo("<!-- got token  -->");}
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
		
		$other = $arthropodNotes;
		if(trim($other) == ""){
			$other = "Arthropoda";	
		}
		
		$newOrders = array(
			"ant" => "Ants",
			"aphid" => "Sternorrhyncha",
			"bee" => "Hymenoptera",
			"sawfly" => "Symphyta",
			"beetle" => "Beetles",
			"caterpillar" => "Lepidoptera",
			"daddylonglegs" => "Daddy longlegs",
			"fly" => "Flies",
			"grasshopper" => "Orthoptera",
			"leafhopper" => "Auchenorrhyncha",
			"moths" => "Lepidoptera",
			"spider" => "Spiders",
			"truebugs" => "True bugs",
			"other" => $other,
			"unidentified" => "Arthropoda"
		);
		$newOrder = $order;
		if($order === "bee" && $sawfly){
			$newOrder = $newOrders["sawfly"];
		}
		else if(array_key_exists($order, $newOrders)){
			$newOrder = $newOrders[$order];
		}
		
		$data = array(
			"observation" => array(
				"species_guess" => cleanParameter($newOrder),
				"id_please" => 1,
				"observed_on_string" => cleanParameter($date),
				"place_guess" => cleanParameter($site->getName()),
				"latitude" => cleanParameter($site->getLatitude()),
				"longitude" => cleanParameter($site->getLongitude())
			)
		);
		
		if(trim($arthropodNotes) != ""){
			$data["observation"]["description"] = cleanParameter($arthropodNotes);
		}
		
		$observationFieldValuesAttributes = array();
		
		$herbivoryScores = array("None", "0-5%", "6-10%", "11-25%", "> 25%");
		$params = [["9677", $averageLeafLength . " cm"], ["2926", $numberOfLeaves], ["9676", (($wetLeaves) ? 'Yes' : 'No')], ["3020", $observationMethod], ["9675", $surveyNotes], ["9670", $arthropodLength . " mm"], ["1194", $site->getName()], ["9671", $plant->getCircle()], ["1422", $plantCode], ["6609", $plant->getSpecies()], ["9672", $herbivoryScores[intval($herbivoryScore)]], ["544", $arthropodQuantity], ["9673", $userTag]];
		if($order == "caterpillar"){
			$params[] = ["9678", (($hairy) ? 'Yes' : 'No')];
			$params[] = ["9679", (($rolled) ? 'Yes' : 'No')];
			$params[] = ["9680", (($tented) ? 'Yes' : 'No')];
		}
		$observationFieldIDString = "&observation[observation_field_values_attributes]";
		for($i = 0; $i < count($params); $i++){
			$observationFieldValuesAttributes[] = array(
				"observation_field_id" => cleanParameter($params[$i][0]),
				"value" => cleanParameter($params[$i][1])
			);
		}
		if($order == "caterpillar"){
			$observationFieldValuesAttributes[] = array(
				"observation_field_id" => 3441,
				"value" => "caterpillar"
			);
			
			$observationFieldValuesAttributes[] = array(
				"observation_field_id" => 325,
				"value" => "larva"
			);
		}
		if($order == "moths"){
			$observationFieldValuesAttributes[] = array(
				"observation_field_id" => 3441,
				"value" => "adult"
			);
			
			$observationFieldValuesAttributes[] = array(
				"observation_field_id" => 325,
				"value" => "adult"
			);
		}
		if($order == "beetle" && $beetleLarva){
			$observationFieldValuesAttributes[] = array(
				"observation_field_id" => 325,
				"value" => "larva"
			);
		}
		
		$data["observation"]["observation_field_values_attributes"] = $observationFieldValuesAttributes;
  		$json = json_encode($data);
  
  		$ch = curl_init("https://api.inaturalist.org/v1/observations");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token, "Accept: application/json", "Content-Type: application/json"));
		$observation = json_decode(curl_exec($ch), true);
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
		$post = array('observation_photo[observation_id]' => $observation["id"], 'observation_photo[uuid]' => guidv4(openssl_random_pseudo_bytes(16)), 'file' => $cFile);
		curl_setopt($ch, CURLOPT_URL, "https://api.inaturalist.org/v1/observation_photos");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token, "Accept: application/json", "Content-Type: multipart/form-data"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$photoAddResponse = curl_exec($ch);
		curl_close ($ch);
		
		if($photoAddResponse !== "Just making sure that the exec is complete."){
			//LINK OBSERVATION TO CATERPILLARS COUNT PROJECT
			$data = array(
				"project_id" => 5443,
				"observation_id" => $observation["id"]
			);
			$json = json_encode($data);

			$ch = curl_init("https://api.inaturalist.org/v1/project_observations");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token, "Accept: application/json", "Content-Type: application/json"));
			$caterpillarsCountLinkResponse = curl_exec($ch);
			curl_close ($ch);
			
			if($caterpillarsCountLinkResponse !== "Just making sure that the exec is complete."){
				if($order == "caterpillar"){
					//LINK OBSERVATION TO CATERPILLARS OF EASTERN NORTH AMERICA PROJECT IF IT'S IN AN ALLOWED REGION
					$data = array(
						"project_id" => 9210,
						"observation_id" => $observation["id"]
					);
					$json = json_encode($data);

					$ch = curl_init("https://api.inaturalist.org/v1/project_observations");
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token, "Accept: application/json", "Content-Type: application/json"));
					$caterpillarsOfEasternNALinkResponse = curl_exec($ch);
					curl_close ($ch);

					if($caterpillarsOfEasternNALinkResponse !== "Just making sure that the exec is complete."){
						//Mark this ArthropodSighting as completed
						if(is_int($observation["id"]) && $observation["id"] > 0){
							mysqli_query($dbconn, "UPDATE ArthropodSighting SET NeedToSendToINaturalist='0', INaturalistID='" . $observation["id"] . "' WHERE ID='" . $arthropodSightingID . "' LIMIT 1");
						}
						
						//Mark that we're finished submitting to iNaturalist
						$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistSurveySubmission'");
					}
					else{
						//Mark that we're finished submitting to iNaturalist
						$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistSurveySubmission'");
					}
				}
				else{
					//Mark this ArthropodSighting as completed and save the INaturalistID to our database
					if(is_int($observation["id"]) && $observation["id"] > 0){
						mysqli_query($dbconn, "UPDATE ArthropodSighting SET NeedToSendToINaturalist='0', INaturalistID='" . $observation["id"] . "' WHERE ID='" . $arthropodSightingID . "' LIMIT 1");
					}
					
					//Mark that we're finished submitting to iNaturalist
					$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistSurveySubmission'");
				}
			}
		}
	}
?>
