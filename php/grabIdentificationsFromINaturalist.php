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
		save($baseFileName . "finishedMonth", date('n'));
		mysqli_close($dbconn);
		die("Already finished this month based on CronJobStatus table.");
	}
	
	//If so, mark as processing
	$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='1' WHERE `Name`='iNaturalistIdentificationFetch'");
	
	//Note which ArthropodSightingFK's have already been expertly identified (so we know whether to UPDATE or INSERT later)
	$previouslyIdentifiedArthropodSightingFKs = array();
	$previouslyIdentifiedStandardGroups = array();
	$query = mysqli_query($dbconn, "SELECT ArthropodSightingFK, StandardGroup, SawflyUpdated, BeetleLarvaUpdated FROM ExpertIdentification WHERE 1");
	while($row = mysqli_fetch_assoc($query)){
		$previouslyIdentifiedArthropodSightingFKs[] = intval($row["ArthropodSightingFK"]);
		$previouslyIdentifiedStandardGroupsByArthropodSightingFK[strval($row["ArthropodSightingFK"])] = array(
			"standardGroup" => $row["StandardGroup"],
			"sawflyUpdated" => $row["SawflyUpdated"],
			"beetleLarvaUpdated" => $row["BeetleLarvaUpdated"]
		);
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
	$originalTranslations = array();
	$query = mysqli_query($dbconn, "SELECT `ID`, `INaturalistID`, `OriginalGroup`, `OriginalSawfly`, `OriginalBeetleLarva` FROM ArthropodSighting WHERE INaturalistID IN ('" . implode("', '", $iNaturalistIDs) . "')");
	while($row = mysqli_fetch_assoc($query)){
		$iNaturalistIDTranslations[$row["INaturalistID"]] = $row["ID"];
		$originalTranslations[$row["INaturalistID"]] = array(
			"originalGroup" => $row["OriginalGroup"],
			"originalSawfly" => boolval($row["OriginalSawfly"]),
			"originalBeetleLarva" => boolval($row["OriginalBeetleLarva"]),
		);
	}
  	
	//Build update queries string
	$updateMySQL = "";
	$updateDisputedMySQL = "";
	for($i = 0; $i < count($data["results"]); $i++){
		//GET VALUE: ArthropodSightingFK
		if(!array_key_exists("id", $data["results"][$i]) || $data["results"][$i]["id"] === null || !array_key_exists($data["results"][$i]["id"], $iNaturalistIDTranslations)){
			continue;
		}
		
		$iNaturalistID = $data["results"][$i]["id"];
		$arthropodSightingFK = $iNaturalistIDTranslations[$data["results"][$i]["id"]];
		$originalGroup = $originalTranslations[$data["results"][$i]["id"]]["originalGroup"];
		$originalSawfly = $originalTranslations[$data["results"][$i]["id"]]["originalSawfly"];
		$originalBeetleLarva = $originalTranslations[$data["results"][$i]["id"]]["originalBeetleLarva"];
		
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
		
		$oldestCaterpillarsCountIdentification = "";
		$oldestCaterpillarsCountIdentificationTimestamp = -1;
		$mostRecentCaterpillarsCountIdentification = "";
		$mostRecentCaterpillarsCountIdentificationTimestamp = -1;
		$numberOfCaterpillarsCountIdentifications = 0;
		for($j = 0; $j < count($identifications); $j++){
			$order = "";
			$suborder = "";
			$family = "";
			if(!array_key_exists("taxon", $identifications[$j]) || $identifications[$j]["taxon"] === null || !array_key_exists("rank", $identifications[$j]["taxon"]) || $identifications[$j]["taxon"]["rank"] === null || !array_key_exists("name", $identifications[$j]["taxon"]) || $identifications[$j]["taxon"]["name"] === null){
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
			
			if(array_key_exists("ancestors", $identifications[$j]["taxon"]) && $identifications[$j]["taxon"]["ancestors"] !== null){
				$ancestors = $identifications[$j]["taxon"]["ancestors"];
				for($t = 0; $t < count($ancestors); $t++){
					if(!array_key_exists("rank", $ancestors[$t]) || $ancestors[$t]["rank"] === null || !array_key_exists("name", $ancestors[$t]) || $ancestors[$t]["name"] === null){
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
			
			$vote = "";
			if($order == "Hymenoptera" && $family == "Formicidae"){
				$vote = "ant";
			}
			else if($order == "Hemiptera" && $suborder == "Sternorrhyncha"){
				$vote = "aphid";
			}
			else if($order == "Hymenoptera" && $suborder == "Symphyta"){
				$vote = "sawfly";
			}
			else if($order == "Hymenoptera"){
				$vote = "bee";
			}
			else if($order == "Coleoptera"){
				$vote = "beetle";
			}
			else if($order == "Lepidoptera" && $isLarva){
				$vote = "caterpillar";
			}
			else if($order == "Opiliones"){
				$vote = "daddylonglegs";
			}
			else if($order == "Diptera"){
				$vote = "fly";
			}
			else if($order == "Orthoptera"){
				$vote = "grasshopper";
			}
			else if($order == "Hemiptera" && $suborder == "Auchenorrhyncha"){
				$vote = "leafhopper";
			}
			else if($order == "Lepidoptera"){
				$vote = "moths";
			}
			else if($order == "Araneae"){
				$vote = "spider";
			}
			else if($order == "Hemiptera"){
				$vote = "truebugs";
			}
			else{
				$vote = $order;
			}
			
			if(array_key_exists("user", $identifications[$j]) && $identifications[$j]["user"] !== null && array_key_exists("login", $identifications[$j]["user"]) && $identifications[$j]["user"]["login"] == "caterpillarscount"){
				if(array_key_exists("created_at", $identifications[$j]) && $identifications[$j]["created_at"] !== null){
					$timestamp = strtotime($identifications[$j]["created_at"]);
					if($timestamp < $oldestCaterpillarsCountIdentificationTimestamp || $oldestCaterpillarsCountIdentificationTimestamp == -1){
						$oldestCaterpillarsCountIdentificationTimestamp = $timestamp;
						$oldestCaterpillarsCountIdentification = $vote;
					}
					if($timestamp >= $mostRecentCaterpillarsCountIdentificationTimestamp && !(array_key_exists("current", $identifications[$j]) && $identifications[$j]["current"] === false)){
						$mostRecentCaterpillarsCountIdentificationTimestamp = $timestamp;
						$mostRecentCaterpillarsCountIdentification = $vote;
					}
				}
				$numberOfCaterpillarsCountIdentifications++;
			}
			
			if(!(array_key_exists("current", $identifications[$j]) && $identifications[$j]["current"] === false) && $vote != ""){
				$identificationVotes[] = $vote;
			}
		}
		
		$identificationVoteCounts = array_count_values($identificationVotes);
		arsort($identificationVoteCounts);
		$keys = array_keys($identificationVoteCounts);
		if(count($keys) == 0){
			$updateMySQL .= "DELETE FROM `ExpertIdentification` WHERE `ArthropodSightingFK`='$arthropodSightingFK';";
			continue;
		}
		$pluralityIdentification = $keys[0];
		if($numberOfCaterpillarsCountIdentifications > 1 && $mostRecentCaterpillarsCountIdentificationTimestamp > -1){
			$pluralityIdentification = $mostRecentCaterpillarsCountIdentification;//our follow-up identification trumps all other identifications
			if(in_array(intval($arthropodSightingFK), $previouslyDisputedArthropodSightingFKs)){
				$updateDisputedMySQL .= "DELETE FROM DisputedIdentification WHERE ArthropodSightingFK='$arthropodSightingFK';";
			}
		}
		else{
			if(count($keys) > 1 || (count($keys > 0) && $keys[0] != $oldestCaterpillarsCountIdentification)){
				//if we haven't followed up with another identification yet, and there's a disagreement with our original identification
				$supporting = 0;
				if(array_key_exists($oldestCaterpillarsCountIdentification, $identificationVoteCounts)){
					$supporting = $identificationVoteCounts[$oldestCaterpillarsCountIdentification] - 1;
				}
				
				$disputing = array_sum($identificationVoteCounts) - $supporting - 1;
				$expertIdentification = str_replace("sawfly", "bee (sawfly)", $pluralityIdentification);
				if(count($identifications) < 2 || (count($keys) > 1 && $identificationVoteCounts[$keys[0]] == $identificationVoteCounts[$keys[1]])){
					$expertIdentification = "";
				}
				
				$keysWithoutOldestCaterpillarsCountIdentification = $keys;
				if(($key = array_search($oldestCaterpillarsCountIdentification, $keysWithoutOldestCaterpillarsCountIdentification)) !== false){
					unset($keysWithoutOldestCaterpillarsCountIdentification[$key]);
				}
				$suggestedGroups = str_replace("sawfly", "bee (sawfly)", str_replace("'", "", implode(", ", $keysWithoutOldestCaterpillarsCountIdentification)));
				
				if(in_array(intval($arthropodSightingFK), $previouslyDisputedArthropodSightingFKs)){
					//update DisputedIdentification
					$updateDisputedMySQL .= "UPDATE `DisputedIdentification` SET `SupportingIdentifications`='$supporting', `DisputingIdentifications`='$disputing', `SuggestedGroups`='$suggestedGroups', `ExpertIdentification`='$expertIdentification', `LastUpdated`=NOW() WHERE ArthropodSightingFK='$arthropodSightingFK';";
				}
				else{
					//insert into DisputedIdentification
					$updateDisputedMySQL .= "INSERT INTO `DisputedIdentification` (`ArthropodSightingFK`, `OriginalGroup`, `SupportingIdentifications`, `DisputingIdentifications`, `SuggestedGroups`, `ExpertIdentification`, `INaturalistObservationURL`) VALUES ('$arthropodSightingFK', '$originalGroup', '$supporting', '$disputing', '$suggestedGroups', '$expertIdentification', 'https://www.inaturalist.org/observations/$iNaturalistID');";
				}
			}
			else if(in_array(intval($arthropodSightingFK), $previouslyDisputedArthropodSightingFKs)){
				//delete from DisputedIdentification
				$updateDisputedMySQL .= "DELETE FROM DisputedIdentification WHERE ArthropodSightingFK='$arthropodSightingFK';";
			}
			
			if(count($identifications) < 2 || (count($keys) > 1 && $identificationVoteCounts[$keys[0]] == $identificationVoteCounts[$keys[1]])){
				$updateMySQL .= "DELETE FROM `ExpertIdentification` WHERE `ArthropodSightingFK`='$arthropodSightingFK';";
				continue;//don't allow ties for ExpertIdentification plurality agreement
			}
		}
		
		//GET VALUE: Plurality Agreement
		$pluralityIdentificationAgreement = $identificationVoteCounts[$pluralityIdentification];
		
		//GET VALUE: Runner-Up Agreement
		$runnerUpIdentificationVoteAgreement = 0;
		if(count($keys) > 1){
			if($keys[0] == $pluralityIdentification){
				$runnerUpIdentificationVoteAgreement = $identificationVoteCounts[$keys[1]];
			}
			else{
				$runnerUpIdentificationVoteAgreement = $identificationVoteCounts[$keys[0]];
			}
		}
		
		//Mark sawflies as bees with sawfly being true
		$isSawfly = false;
		if($pluralityIdentification == "sawfly"){
			$pluralityIdentification = "bee";
			$isSawfly = true;
		}
		
		//Add to our update queries string
		if(in_array(intval($arthropodSightingFK), $previouslyIdentifiedArthropodSightingFKs)){
			$updateMySQL .= "UPDATE `ExpertIdentification` SET `Rank`='$rank', `TaxonName`='$taxonName', `StandardGroup`='$pluralityIdentification', `BeetleLarvaUpdated`='" . ($pluralityIdentification == "beetle" && $isLarva) . "', `SawflyUpdated`='$isSawfly', `Agreement`='$pluralityIdentificationAgreement', `RunnerUpAgreement`='$runnerUpIdentificationVoteAgreement', `LastUpdated`=NOW() WHERE `ArthropodSightingFK`='$arthropodSightingFK';";
		}
		else{
			$updateMySQL .= "INSERT INTO `ExpertIdentification` (`ArthropodSightingFK`, `OriginalGroup`, `Rank`, `TaxonName`, `StandardGroup`, `BeetleLarvaUpdated`, `SawflyUpdated`, `Agreement`, `RunnerUpAgreement`, `INaturalistObservationURL`) VALUES ('$arthropodSightingFK', '$originalGroup', '$rank', '$taxonName', '$pluralityIdentification', '" . ($pluralityIdentification == "beetle" && $isLarva) . "', '$isSawfly', '$pluralityIdentificationAgreement', '$runnerUpIdentificationVoteAgreement', 'https://www.inaturalist.org/observations/$iNaturalistID');";
		}
		
		//Log ExpertIdentification change in TemporaryExpertIdentificationChangeLog table
		$previouslyIdentifiedStandardGroup = "";
		$previouslyIdentifiedSawfly = false;
		$previouslyIdentifiedBeetleLarva = false;
		if(array_key_exists(strval($arthropodSightingFK), $previouslyIdentifiedStandardGroupsByArthropodSightingFK)){
			$previouslyIdentifiedStandardGroup = $previouslyIdentifiedStandardGroupsByArthropodSightingFK[strval($arthropodSightingFK)]["standardGroup"];
			$previouslyIdentifiedSawfly = boolval($previouslyIdentifiedStandardGroupsByArthropodSightingFK[strval($arthropodSightingFK)]["sawflyUpdated"]);
			$previouslyIdentifiedBeetleLarva = boolval($previouslyIdentifiedStandardGroupsByArthropodSightingFK[strval($arthropodSightingFK)]["beetleLarvaUpdated"]);
		}
		if(($previouslyIdentifiedStandardGroup == "" && ($pluralityIdentification != $originalGroup || $isSawfly != $originalSawfly || ($pluralityIdentification == "beetle" && $isLarva) != $originalBeetleLarva)) || ($previouslyIdentifiedStandardGroup != $pluralityIdentification || $previouslyIdentifiedSawfly != $isSawfly || $previouslyIdentifiedBeetleLarva != ($pluralityIdentification == "beetle" && $isLarva))){
			if($previouslyIdentifiedBeetleLarva){
				$previouslyIdentifiedStandardGroup = "beetle larva";
			}
			else if($previouslyIdentifiedSawfly){
				$previouslyIdentifiedStandardGroup = "sawfly";
			}
			
			$currentStandardGroupToEmailLater = $pluralityIdentification;
			if($currentStandardGroupToEmailLater == "beetle" && $isLarva){
				$currentStandardGroupToEmailLater = "beetle larva";
			}
			else if($isSawfly){
				$currentStandardGroupToEmailLater = "sawfly";
			}
			
			$updateMySQL .= "INSERT INTO `TemporaryExpertIdentificationChangeLog` (`ArthropodSightingFK`, `PreviousExpertIdentification`, `NewExpertIdentification`) VALUES ('$arthropodSightingFK', '$previouslyIdentifiedStandardGroup', '$currentStandardGroupToEmailLater');";
		}
		
		//Update UpdatedGroup in ArthropodSighting table
		$allGroups = array("ant", "aphid", "bee", "beetle", "caterpillar", "daddylonglegs", "fly", "grasshopper", "leafhopper", "moths", "spider", "truebugs", "other");
		$updatedGroup = $pluralityIdentification;
		if(!in_array($updatedGroup, $allGroups)){
			$updatedGroup = "other";
		}
		$updateMySQL .= "UPDATE `ArthropodSighting` SET `UpdatedGroup`='$updatedGroup', `UpdatedBeetleLarva`='" . ($pluralityIdentification == "beetle" && $isLarva) . "', `UpdatedSawfly`='$isSawfly' WHERE `ID`='$arthropodSightingFK';";
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
