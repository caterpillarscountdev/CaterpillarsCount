<?php
/*
	require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
	
	//TODO: get current iteration from database to use in cURL
	$ch = curl_init('https://api.inaturalist.org/v1/observations?project_id=caterpillars-count-foliage-arthropod-survey&user_login=caterpillarscount&page=1&per_page=50&order=desc&order_by=created_at');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = json_decode(curl_exec($ch), true);
	curl_close ($ch);
	
	$iNaturalistIDs = [];
	for($i = 0; i < count($data["results"]); $i++){
		$iNaturalistIDs[] = $data["results"][$i]["id"];
	}

	$iNaturalistIDTranslations = array();
	$query = mysqli_query($dbconn, "SELECT ID, INaturalistID FROM ArthropodSighting WHERE INaturalistID IN ('" . implode("', '", $iNaturalistIDs) . "')");
	while($row = mysqli_fetch_assoc($query)){
		$iNaturalistIDTranslations[$row["INaturalistID"]] = $row["ID"];
	}
  
	for($i = 0; i < count($data["results"]); $i++){
		$arthropodFK = $iNaturalistIDTranslations[$data["results"][$i]["id"]];
		
		$rank = $data["results"][$i]["taxon"]["rank"];
		
		$orderIdentifications = array();
		$identifications = $data["results"][$i]["identifications"];
		if(count($identifications) < 2){
			continue;//don't allow single-vote winners
		}
		for($j = 0; $j < count($identifications); $j++){
			$finestRank = $identifications[$j]["taxon"]["rank"];
			if($finestRank == "order"){
				$finestName = $identifications[$j]["taxon"]["name"];
				$orderIdentifications[] = $finestName;
			}
			else{
				$ancestors = $identifications[$j]["taxon"]["ancestors"];
				for($t = 0; $t < count($ancestors); $t++){
					if($ancestors[$t]["rank"] == "order"){
						$orderIdentifications[] = $ancestors[$t]["name"];
						break;
					}
				}
			}
		}
		$orderIdentificationCounts = array_count_values($array);
		arsort($orderIdentificationCounts);
		$keys = array_keys($orderIdentificationCounts);
		if(count($identifications) < 2 || $orderIdentificationCounts[$keys[0]] == $orderIdentificationCounts[$keys[1]]){
			continue;//don't allow ties
		}
		$pluralityOrderIdentification = $keys[0];
		
		$pluralityOrderIdentificationAgreement = $orderIdentificationCounts[$keys[0]];
		
		$runnerUpOrderIdentificationAgreement = 0;
		if(count($keys) > 1){
			$runnerUpOrderIdentificationAgreement = $orderIdentificationCounts[$keys[1]];
		}
		
		$orderTranslations = array(
			"Ants" => "ant",
			"Sternorrhyncha" => "aphid",
			"Hymenoptera" => "bee",
			"Beetles" => "beetle",
			"Lepidoptera" => "caterpillar",
			"Daddy longlegs" => "daddylonglegs",
			"Flies" => "fly",
			"Orthoptera" => "grasshopper",
			"Auchenorrhyncha" => "leafhopper",
			"Lepidoptera" => "moths",
			"Spiders" => "spider",
			"True bugs" => "truebugs",
		);
		
		$taxonName = $data["results"][$i]["taxon"]["name"];
		$standardGroup = "";//TODO
	}
	
	if(count($data["results"]) == 0){
		//mark finished in db and reset iteration
	}
	*/
?>
