<?php
        
	require_once("orm/Plant.php");
	require_once("orm/resources/mailing.php");
        require_once("orm/resources/Customfunctions.php");
	
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

        $cachedTokens = array();

        function iNatToken($dbconn, $userID, $accessToken) {
          $token = array_key_exists($accessToken, $cachedTokens) && $cachedTokens[$accessToken];
          if ($token) {
            return $token
          }
          // retrieve from access token
          if ($accessToken) {
            $response = curlINatJWT($accessToken);
            if (array_key_exists("api_token", $response)) {
              $token = $response["api_token"];
            } else {
              // error getting user token, invalidate
              mysqli_query($dbconn, "UPDATE User SET `INaturalistAccessToken` = '', `INaturalistJWToken = '' WHERE ID='$userID';")
            }
          }
          if (!$token) {
            // null user token will get CC token
            $fields = array(
              "grant_type" => "password",
              "username" => "caterpillarscount",
              "password" => getenv("iNaturalistPassword")
              );
            
            $ccToken = curlINatOAuth($fields)["access_token"];
            $token = curlINatJWT($ccToken)["api_token"];
          }
          $cachedTokens[$accessToken] = $token;
          return $token;
        }
        

        function submitINaturalistObservation($dbconn, $arthropodSightingID, $userID, $userTag, $userAccessToken, $plantCode, $date, $time, $observationMethod, $surveyNotes, $wetLeaves, $order, $hairy, $rolled, $tented, $sawfly, $beetleLarva, $arthropodQuantity, $arthropodLength, $arthropodPhotoURL, $arthropodNotes, $numberOfLeaves, $averageLeafLength, $herbivoryScore){
		//GET AUTHORIZATION

                $token = iNatToken($dbconn, $userID, $userAccessToken);
                
		echo("<!-- got token: $token  -->");
		
		//CREATE OBSERVATION
		$plant = Plant::findByCode($plantCode);
		$site = $plant->getSite();

                $latitude = $site->getLatitude();
                if ($plant->getLatitude()) {
                  $latitude = $plant->getLatitude();
                }
                $longitude = $site->getLongitude();
                if ($plant->getLongitude()) {
                  $longitude = $plant->getLongitude();
                }
		
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
			"sawfly" => "Tenthredinoidea",
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
				"observed_on_string" => cleanParameter($date . ' ' . $time),
				"place_guess" => cleanParameter($site->getName()),
				"latitude" => cleanParameter($latitude),
				"longitude" => cleanParameter($longitude)
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
		if($order == "beetle" && $beetleLarva){
			$observationFieldValuesAttributes[] = array(
				"observation_field_id" => 325,
				"value" => "larva"
			);
		}
		
		$data["observation"]["observation_field_values_attributes"] = $observationFieldValuesAttributes;

                $observation = curlINatAPI("/v1/observations", $data, $token);
                echo("\nMade observation for " . $arthropodSightingID . " :" . $observation["id"]);

		//ADD PHOTO TO OBSERVATION
		$ch = curl_init();
		$arthropodPhotoPath = "../images/arthropods/" . $arthropodPhotoURL;
		if(strpos($arthropodPhotoURL, '/') !== false){
			$arthropodPhotoPath = "/opt/app-root/src/images/arthropods" . $arthropodPhotoURL;
		}
                $cFile = curl_file_create($arthropodPhotoPath);
		$post = array('observation_photo[observation_id]' => $observation["id"], 'observation_photo[uuid]' => guidv4(openssl_random_pseudo_bytes(16)), 'file' => $cFile);

                $photoAddResponse = curlINatAPI("/v1/observation_photos", $post, $token, array("multipart" => 1));
                echo("\nGot photo response " . $arthropodSightingID . " :" . $photoAddResponse);
		
                //LINK OBSERVATION TO CATERPILLARS COUNT PROJECT
                $data = array(
                  "project_id" => 5443,
                  "observation_id" => $observation["id"]
                  );
                $caterpillarsCountLinkResponse = curlINatAPI("/v1/project_observations", $data, $token);
                echo("\nGot link response " . $arthropodSightingID . " :" . $caterpillarsCountLinkResponse);

                //Mark this ArthropodSighting as completed and save the INaturalistID to our database
                if(is_int($observation["id"]) && $observation["id"] > 0){
                  mysqli_query($dbconn, "UPDATE ArthropodSighting SET NeedToSendToINaturalist='0', INaturalistID='" . $observation["id"] . "' WHERE ID='" . $arthropodSightingID . "' LIMIT 1");
                }
        }
?>
