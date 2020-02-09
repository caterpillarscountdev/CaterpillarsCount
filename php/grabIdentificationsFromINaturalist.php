<?php
	require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
	require_once('/opt/app-root/src/php/resultMemory.php');
	
	//Check if we need to run a fetch
	$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php'));
	$savedFinishedMonth = getSave($baseFileName . "finishedMonth", 31 * 24 * 60 * 60);
	if($savedFinishedMonth !== null && intval($savedFinishedMonth) == intval(date('n'))){
		die("Already finished this month based on cache.");
	}
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	if ($dbconn->connect_error) {
   		die("Connection failed: " . $dbconn->connect_error);
	}
	$query = mysqli_query($dbconn, "SELECT MONTH(UTCLastCalled) AS `Month`, `Processing`, `Iteration`, `UTCLastCalled` FROM `CronJobStatus` WHERE `Name`='iNaturalistIdentificationFetch'");
	if(mysqli_num_rows($query) == 0){
		mysqli_close($dbconn);
		die("\"iNaturalistIdentificationFetch\" not in CronJobStatus table.");
	}
	$cronJobStatusRow = mysqli_fetch_assoc($query);
	$month = intval($cronJobStatusRow["Month"]);
	$processing = filter_var($cronJobStatusRow["Processing"], FILTER_VALIDATE_BOOLEAN);
	$iteration = intval($cronJobStatusRow["Iteration"]);
	if($processing){
		if(strtotime($cronJobStatusRow["UTCLastCalled"]) < strtotime("-10 minutes")){
			mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistIdentificationFetch'");
		}
		mysqli_close($dbconn);
		die("Already processing.");
	}
	if($month == intval(date('n')) && $iteration == 0){
		/*save($baseFileName . "finishedMonth", date('n'));
		mysqli_close($dbconn);
		die("Already finished this month based on CronJobStatus table.");*/
	}
	
	//If so, mark as processing and increment interation
	$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='1' WHERE `Name`='iNaturalistIdentificationFetch'");
	
	//Note which ArthropodSightingFK's have already been expertly identified (so we know whether to UPDATE or INSERT later)
	$previouslyIdentifiedArthropodSightingFKs = array();
	$query = mysqli_query($dbconn, "SELECT ArthropodSightingFK FROM ExpertIdentification WHERE 1");
	while($row = mysqli_fetch_assoc($query)){
		$previouslyIdentifiedArthropodSightingFKs[] = intval($row["ArthropodSightingFK"]);
	}
	
	//Note which ArthropodSightingFK's have already been marked as disputed (so we know whether to DELETE, UPDATE, or INSERT later)
	$previouslyDisputedArthropodSightingFKs = array();
	$query = mysqli_query($dbconn, "SELECT ArthropodSightingFK FROM DisputedIdentification WHERE 1");
	while($row = mysqli_fetch_assoc($query)){
		$previouslyDisputedArthropodSightingFKs[] = intval($row["ArthropodSightingFK"]);
	}
	
	//Fetch data from iNaturalist
	$ch = curl_init("https://api.inaturalist.org/v1/observations?project_id=caterpillars-count-foliage-arthropod-survey&user_login=caterpillarscount&page=" . (++$iteration) . "&per_page=50&order=desc&order_by=created_at");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = json_decode(curl_exec($ch), true);
	curl_close($ch);
	
	//Simplify the translation process from iNaturalistIDs to ArthropodSightingIDs
	$iNaturalistIDs = [];
	for($i = 0; $i < count($data["results"]); $i++){
		$iNaturalistIDs[] = $data["results"][$i]["id"];
	}
	
	$iNaturalistIDTranslations = array();
	$originalGroupTranslations = array();
	$query = mysqli_query($dbconn, "SELECT `ID`, `INaturalistID`, `Group` FROM ArthropodSighting WHERE INaturalistID IN ('" . implode("', '", $iNaturalistIDs) . "')");
	while($row = mysqli_fetch_assoc($query)){
		$iNaturalistIDTranslations[$row["INaturalistID"]] = $row["ID"];
		$originalGroupTranslations[$row["INaturalistID"]] = $row["Group"];
	}
  	
	//Build update queries string
	$updateMySQL = "";
	$updateDisputedMySQL = "";
	for($i = 0; $i < count($data["results"]); $i++){
		//GET VALUE: ArthropodSightingFK
		if(!array_key_exists("id", $data["results"][$i]) || !array_key_exists($data["results"][$i]["id"], $iNaturalistIDTranslations)){
			continue;
		}
		
		$iNaturalistID = $data["results"][$i]["id"];
		$arthropodSightingFK = $iNaturalistIDTranslations[$data["results"][$i]["id"]];
		$originalGroup = $originalGroupTranslations[$data["results"][$i]["id"]];
		
		//GET VALUE: Finest Rank
		if($data["results"][$i] === null || !array_key_exists("taxon", $data["results"][$i]) || $data["results"][$i]["taxon"] === null || !array_key_exists("rank", $data["results"][$i]["taxon"])){
			continue;
		}
		
		$rank = $data["results"][$i]["taxon"]["rank"];
		
		//GET VALUE: Finest Taxon Name
		if(!array_key_exists("name", $data["results"][$i]["taxon"])){
			continue;
		}
		
		$taxonName = $data["results"][$i]["taxon"]["name"];
		
		//GET VALUE: Plurality Identification where Number of Votes > 1
		$identificationVotes = array();
		
		if(!array_key_exists("identifications", $data["results"][$i])){
			continue;
		}
		
		$identifications = $data["results"][$i]["identifications"];
		if(count($identifications) < 2){
			continue;//don't allow single-vote winners
		}
		
		$isLarva = false;
		if(array_key_exists("annotations", $data["results"][$i]) && $data["results"][$i]["annotations"] !== null){
			for($j = 0; $j < count($data["results"][$i]["annotations"]); $j++){
				if(array_key_exists("controlled_attribute_id", $data["results"][$i]["annotations"][$j]) && intval($data["results"][$i]["annotations"][$j]["controlled_attribute_id"]) == 1 && array_key_exists("controlled_value_id", $data["results"][$i]["annotations"][$j]) && intval($data["results"][$i]["annotations"][$j]["controlled_value_id"]) == 6){
					$isLarva = true;
					break;
				}
			}
		}
		
		$mostRecentCaterpillarsCountIdentification = "";
		$mostRecentCaterpillarsCountIdentificationTimestamp = -1;
		$numberOfCaterpillarsCountIdentifications = 0;
		for($j = 0; $j < count($identifications); $j++){
			$order = "";
			$suborder = "";
			$family = "";
			if(!array_key_exists("taxon", $identifications[$j]) || !array_key_exists("rank", $identifications[$j]["taxon"]) || !array_key_exists("name", $identifications[$j]["taxon"])){
				continue;
			}
			$finestRank = $identifications[$j]["taxon"]["rank"];
			$finestName = $identifications[$j]["taxon"]["name"];
			if($finestRank == "order"){
				$order = $finestName;
			}
			else if($finestRank == "suborder"){
				$suborder = $finestName;
			}
			else if($finestRank == "family"){
				$family = $finestName;
			}
			
			if(array_key_exists("ancestors", $identifications[$j]["taxon"])){
				$ancestors = $identifications[$j]["taxon"]["ancestors"];
				for($t = 0; $t < count($ancestors); $t++){
					if(!array_key_exists("rank", $ancestors[$t]) || !array_key_exists("name", $ancestors[$t])){
						continue;
					}
					
					if($ancestors[$t]["rank"] == "order"){
						$order = $ancestors[$t]["name"];
					}
					else if($ancestors[$t]["rank"] == "suborder"){
						$suborder = $ancestors[$t]["name"];
					}
					else if($ancestors[$t]["rank"] == "family"){
						$family = $ancestors[$t]["name"];
					}
				}
			}
			
			if($order == "Hymenoptera" && $family == "Formicidae"){
				$identificationVotes[] = "ant";
			}
			else if($order == "Hemiptera" && $suborder == "Sternorrhyncha"){
				$identificationVotes[] = "aphid";
			}
			else if($order == "Hymenoptera" && $suborder == "Symphyta"){
				$identificationVotes[] = "sawfly";
			}
			else if($order == "Hymenoptera"){
				$identificationVotes[] = "bee";
			}
			else if($order == "Coleoptera"){
				$identificationVotes[] = "beetle";
			}
			else if($order == "Lepidoptera" && $isLarva){
				$identificationVotes[] = "caterpillar";
			}
			else if($order == "Opiliones"){
				$identificationVotes[] = "daddylonglegs";
			}
			else if($order == "Diptera"){
				$identificationVotes[] = "fly";
			}
			else if($order == "Orthoptera"){
				$identificationVotes[] = "grasshopper";
			}
			else if($order == "Hemiptera" && $suborder == "Auchenorrhyncha"){
				$identificationVotes[] = "leafhopper";
			}
			else if($order == "Lepidoptera"){
				$identificationVotes[] = "moths";
			}
			else if($order == "Araneae"){
				$identificationVotes[] = "spider";
			}
			else if($order == "Hemiptera"){
				$identificationVotes[] = "truebugs";
			}
			else if($order != ""){
				$identificationVotes[] = $order;
			}
			
			if(array_key_exists("user", $identifications[$j]) && array_key_exists("login", $identifications[$j]["user"]) && $identifications[$j]["user"]["login"] == "caterpillarscount" && array_key_exists("created_at", $identifications[$j])){
				$timestamp = strtotime($identifications[$j]["created_at"]);
				if($timestamp >= $mostRecentCaterpillarsCountIdentificationTimestamp){
					$mostRecentCaterpillarsCountIdentificationTimestamp = $timestamp;
					$mostRecentCaterpillarsCountIdentification = $identificationVotes[count($identificationVotes) - 1];
				}
				$numberOfCaterpillarsCountIdentifications++;
			}
		}
		
		$identificationVoteCounts = array_count_values($identificationVotes);
		arsort($identificationVoteCounts);
		$keys = array_keys($identificationVoteCounts);
		$pluralityIdentification = $keys[0];
		if($numberOfCaterpillarsCountIdentifications > 1){
			$pluralityIdentification = $mostRecentCaterpillarsCountIdentification;//our follow-up identification trumps all other identifications
			if(in_array(intval($arthropodSightingFK), $previouslyDisputedArthropodSightingFKs)){
				$updateDisputedMySQL .= "DELETE FROM DisputedIdentification WHERE ArthropodSightingFK='$arthropodSightingFK';";
			}
		}
		else{
			if(count($keys) > 1){
				//if we haven't followed up with another identification yet, and there's a disagreement with our original identification
				if(array_key_exists($mostRecentCaterpillarsCountIdentification, $identificationVoteCounts)){
					$supporting = $identificationVoteCounts[$mostRecentCaterpillarsCountIdentification] - 1;
					$disputing = array_sum($identificationVoteCounts) - $supporting;
					if(in_array(intval($arthropodSightingFK), $previouslyDisputedArthropodSightingFKs)){
						//update
						$updateDisputedMySQL .= "UPDATE `DisputedIdentification` SET `SupportingIdentifications`='$supporting', `DisputingIdentifications`='$disputing' WHERE ArthropodSightingFK='$arthropodSightingFK';";
					}
					else{
						//insert
						$updateDisputedMySQL .= "INSERT INTO `DisputedIdentification` (`ArthropodSightingFK`, `OriginalGroup`, `SupportingIdentifications`, `DisputingIdentifications`, `INaturalistObservationURL`) VALUES ('$arthropodSightingFK', '$originalGroup', '$supporting', '$disputing', 'https://www.inaturalist.org/observations/$iNaturalistID');";
					}
				}
			}
			else if(in_array(intval($arthropodSightingFK), $previouslyDisputedArthropodSightingFKs)){
				$updateDisputedMySQL .= "DELETE FROM DisputedIdentification WHERE ArthropodSightingFK='$arthropodSightingFK';";
			}
			
			if(count($identifications) < 2 || (count($keys) > 1 && $identificationVoteCounts[$keys[0]] == $identificationVoteCounts[$keys[1]])){
				continue;//don't allow ties
			}
		}
		
		//GET VALUE: Plurality Agreement
		$pluralityIdentificationAgreement = $identificationVoteCounts[$pluralityIdentification];
		
		//GET VALUE: Runner-Up Agreement
		$runnerUpIdentificationVoteAgreement = 0;
		if(count($keys) > 1){
			$runnerUpIdentificationVoteAgreement = $identificationVoteCounts[$keys[1]];
		}
		
		//Mark sawflies as bees with sawfly being true
		$isSawfly = false;
		if($pluralityIdentification == "sawfly"){
			$pluralityIdentification = "bee";
			$isSawfly = true;
		}
		
		//Add to our update queries string
		if(in_array(intval($arthropodSightingFK), $previouslyIdentifiedArthropodSightingFKs)){
			$updateMySQL .= "UPDATE `ExpertIdentification` SET `Rank`='$rank', `TaxonName`='$taxonName', `StandardGroup`='$pluralityIdentification', `BeetleLarvaUpdated`='" . ($pluralityIdentificationAgreement == "beetle" && $isLarva) . "', `SawflyUpdated`='$isSawfly', `Agreement`='$pluralityIdentificationAgreement', `RunnerUpAgreement`='$runnerUpIdentificationVoteAgreement', `LastUpdated`=NOW() WHERE `ArthropodSightingFK`='$arthropodSightingFK';";
		}
		else{
			$updateMySQL .= "INSERT INTO `ExpertIdentification` (`ArthropodSightingFK`, `OriginalGroup`, `Rank`, `TaxonName`, `StandardGroup`, `BeetleLarvaUpdated`, `SawflyUpdated`, `Agreement`, `RunnerUpAgreement`, `INaturalistObservationURL`) VALUES ('$arthropodSightingFK', '$originalGroup', '$rank', '$taxonName', '$pluralityIdentification', '" . ($pluralityIdentificationAgreement == "beetle" && $isLarva) . "', '$isSawfly', '$pluralityIdentificationAgreement', '$runnerUpIdentificationVoteAgreement', 'https://www.inaturalist.org/observations/$iNaturalistID');";
		}
	}
	
	//Run the update queries string we built
	if($updateMySQL != ""){
		$query = mysqli_multi_query($dbconn, $updateMySQL);
		while(mysqli_more_results($dbconn)){$temp = mysqli_next_result($dbconn);}
	}
	
	if($updateDisputedMySQL != ""){
		$query = mysqli_multi_query($dbconn, $updateDisputedMySQL);
		while(mysqli_more_results($dbconn)){$temp = mysqli_next_result($dbconn);}
	}
	
	//Mark the progress in the database
	if(count($data["results"]) == 0){
		//Finished for the month
		$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0', `Iteration`='0', `UTCLastCalled`=NOW() WHERE `Name`='iNaturalistIdentificationFetch'");
		save($baseFileName . "finishedMonth", date('n'));
	}
	else{
		//Finished with this run, but needs more iterations this month still
		$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0', `Iteration`='$iteration', `UTCLastCalled`=NOW() WHERE `Name`='iNaturalistIdentificationFetch'");
	}
	
	mysqli_close($dbconn);
?>
