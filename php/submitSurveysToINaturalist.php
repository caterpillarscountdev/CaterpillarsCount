<?php
	/*
	require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
	require_once("/opt/app-root/src/php/orm/Plant.php");
	
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
	
	function submitINaturalistObservation($userTag, $plantCode, $date, $observationMethod, $surveyNotes, $wetLeaves, $order, $hairy, $rolled, $tented, $arthropodQuantity, $arthropodLength, $arthropodPhotoURL, $arthropodNotes, $numberOfLeaves, $averageLeafLength, $herbivoryScore){
		//GET AUTHORIZATION
		$ch = curl_init('https://www.inaturalist.org/oauth/token');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "client_id=" . getenv("iNaturalistAppID") . "&client_secret=" . getenv("iNaturalistAppSecret") . "&grant_type=password&username=caterpillarscount&password=" . getenv("iNaturalistPassword"));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$token = json_decode(curl_exec($ch), true)["access_token"];
		curl_close ($ch);
		
		//CREATE OBSERVATION
		$plant = Plant::findByCode($plantCode);
		$site = $plant->getSite();
		
		$other = $arthropodNotes;
		if(trim($other) == ""){
			$other = "Arthropoda";
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
			"other" => $other,
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
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=" . $token);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
		$observation = json_decode(curl_exec($ch), true)[0];
		curl_close ($ch);
		
		//ADD PHOTO TO OBSERVATION
		$ch = curl_init();
		if(function_exists('curl_file_create')){//PHP 5.5+
			$cFile = curl_file_create("../images/arthropods/" . $arthropodPhotoURL);
		}
		else{
			curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
			$cFile = '@' . realpath("../images/arthropods/" . $arthropodPhotoURL);
		}
		$post = array('access_token' => $token, 'observation_photo[observation_id]' => $observation["id"], 'file'=> $cFile);
		curl_setopt($ch, CURLOPT_URL,"http://www.inaturalist.org/observation_photos");
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_exec($ch);
		curl_close ($ch);
		
		//LINK OBSERVATION TO CATERPILLARS COUNT PROJECT
		$ch = curl_init("http://www.inaturalist.org/project_observations");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=" . $token . "&project_observation[observation_id]=" . $observation["id"] . "&project_observation[project_id]=5443");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_exec($ch);
		curl_close ($ch);
		
		if($order == "caterpillar"){
			//LINK OBSERVATION TO CATERPILLARS OF EASTERN NORTH AMERICA PROJECT IF IT'S IN AN ALLOWED REGION
			$ch = curl_init("http://www.inaturalist.org/project_observations");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=" . $token . "&project_observation[observation_id]=" . $observation["id"] . "&project_observation[project_id]=9210");
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_exec($ch);
			curl_close ($ch);
		}
	}
	
	//NOT DONE WITH CRON
	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT ArthropodSighting.ID AS ArthropodSightingID, User.INaturalistObserverID, User.Hidden, Plant.Code, Survey.LocalDate, Survey.ObservationMethod, Survey.Notes AS SurveyNotes, Survey.WetLeaves, ArthropodSighting.Group, ArthropodSighting.Hairy, ArthropodSighting.Rolled, ArthropodSighting.Tented, ArthropodSighting.Quantity, ArthropodSighting.Length, ArthropodSighting.PhotoURL, ArthropodSighting.Notes AS ArthropodSightingNotes, Survey.NumberOfLeaves, Survey.AverageLeafLength, Survey.HerbivoryScore FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK JOIN `User` ON Survey.UserFKOfObserver=`User`.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE ArthropodSighting.NeedToSendToINaturalist='1' ORDER BY RAND() LIMIT 3");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			$observerID = $row["INaturalistObserverID"];
			if(filter_var($row["Hidden"], FILTER_VALIDATE_BOOLEAN)){
				$observerID = "anonymous";
			}
			submitINaturalistObservation($observerID, $row["Code"], $row["LocalDate"], $row["ObservationMethod"], $row["SurveyNotes"], filter_var($row["WetLeaves"], FILTER_VALIDATE_BOOLEAN), $row["Group"], filter_var($row["Hairy"], FILTER_VALIDATE_BOOLEAN), filter_var($row["Rolled"], FILTER_VALIDATE_BOOLEAN), filter_var($row["Tented"], FILTER_VALIDATE_BOOLEAN), intval($row["Quantity"]), intval($row["Length"]), $row["PhotoURL"], $row["ArthropodSightingNotes"], intval($row["NumberOfLeaves"]), intval($row["AverageLeafLength"]), intval($row["HerbivoryScore"]));
			mysqli_query($dbconn, "UPDATE ArthropodSighting SET NeedToSendToINaturalist='0' WHERE ID='" . $row["ArthropodSightingID"] . "'");
		}
	}
	mysqli_close($dbconn);
	*/
?>