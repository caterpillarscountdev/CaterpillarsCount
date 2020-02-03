<?php
	//require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
	//require_once('/opt/app-root/src/php/resultMemory.php');
	require_once('orm/resources/Keychain.php');
	require_once('resultMemory.php');
	
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
	$query = mysqli_query($dbconn, "SELECT MONTH(UTCLastCalled) AS `Month`, `Processing`, `Iteration` FROM `CronJobStatus` WHERE `Name`='iNaturalistExpertIdentificationFetch'");
	if(mysqli_num_rows($query) == 0){
		die("\"iNaturalistExpertIdentificationFetch\" not in CronJobStatus table.");
	}
	$cronJobStatusRow = mysqli_fetch_assoc($query);
	$month = intval($cronJobStatusRow["Month"]);
	$processing = filter_var($cronJobStatusRow["Processing"], FILTER_VALIDATE_BOOLEAN);
	$iteration = intval($cronJobStatusRow["Iteration"]);
	if($processing){
		die("Already processing.");
	}
	if($month == intval(date('n')) && $iteration == 0){
		//save($baseFileName . "finishedMonth", date('n'));
		//die("Already finished this month based on CronJobStatus table.");
		echo "Already finished this month BUT NOT STOPPED based on CronJobStatus table.";
	}
	
	//If so, mark as processing and increment interation
	$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='1', `Iteration`='" . (++$iteration) . "' WHERE `Name`='iNaturalistExpertIdentificationFetch'");
	
	//Note which ArthropodSightingFK's have already been identified (so we know whether to UPDATE or INSERT later)
	$previouslyIdentifiedArthropodSightingFKs = array();
	$query = mysqli_query($dbconn, "SELECT ArthropodSightingFK FROM ExpertIdentification WHERE 1");
	while($row = mysqli_fetch_assoc($query)){
		$previouslyIdentifiedArthropodSightingFKs[] = intval($row["ArthropodSightingFK"]);
	}
	
	//Fetch data from iNaturalist
	$ch = curl_init("https://api.inaturalist.org/v1/observations?project_id=caterpillars-count-foliage-arthropod-survey&user_login=caterpillarscount&page=" . $iteration . "&per_page=50&order=desc&order_by=created_at");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($ch);
	curl_close ($ch);
	echo "<br/><br/>" . $data . "<br/><br/>";
	$data = json_decode($data, true);
	
	//Simplify the translation process from iNaturalistIDs to ArthropodSightingIDs
	$iNaturalistIDs = [];
	for($i = 0; $i < count($data["results"]); $i++){
		$iNaturalistIDs[] = $data["results"][$i]["id"];
	}
	
	$iNaturalistIDTranslations = array();
	$query = mysqli_query($dbconn, "SELECT ID, INaturalistID FROM ArthropodSighting WHERE INaturalistID IN ('" . implode("', '", $iNaturalistIDs) . "')");
	while($row = mysqli_fetch_assoc($query)){
		$iNaturalistIDTranslations[$row["INaturalistID"]] = $row["ID"];
	}
  	
	//Build update queries string
	$updateMySQL = "";
	for($i = 0; $i < count($data["results"]); $i++){
		//GET VALUE: ArthropodSightingFK
		if(!array_key_exists("id", $data["results"][$i]) || !array_key_exists($data["results"][$i]["id"], $iNaturalistIDTranslations)){
			continue;
		}
		
		$arthropodSightingFK = $iNaturalistIDTranslations[$data["results"][$i]["id"]];
		
		//GET VALUE: Finest Rank
		if(!array_key_exists("taxon", $data["results"][$i]) || !array_key_exists("rank", $data["results"][$i]["taxon"])){
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
		if(array_key_exists("annotations", $data["results"][$i])){
			for($j = 0; $j < count($data["results"][$i]["annotations"]); $j++){
				if(array_key_exists("controlled_attribute_id", $data["results"][$i]["annotations"][$j]) && intval($data["results"][$i]["annotations"][$j]["controlled_attribute_id"]) == 1 && array_key_exists("controlled_value_id", $data["results"][$i]["annotations"][$j]) && intval($data["results"][$i]["annotations"][$j]["controlled_value_id"]) == 6){
					$isLarva = true;
					break;
				}
			}
		}
		
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
			else{
				$identificationVotes[] = $order;
			}
		}
		
		$identificationVoteCounts = array_count_values($array);
		arsort($identificationVoteCounts);
		$keys = array_keys($identificationVoteCounts);
		if(count($identifications) < 2 || $identificationVoteCounts[$keys[0]] == $identificationVoteCounts[$keys[1]]){
			continue;//don't allow ties
		}
		$pluralityIdentification = $keys[0];
		
		//GET VALUE: Plurality Agreement
		$pluralityIdentificationAgreement = $identificationVoteCounts[$keys[0]];
		
		//GET VALUE: Runner-Up Agreement
		$runnerUpIdentificationVoteAgreement = 0;
		if(count($keys) > 1){
			$runnerUpIdentificationVoteAgreement = $identificationVoteCounts[$keys[1]];
		}
		
		//Add to our update queries string
		if(in_array(intval($arthropodSightingFK), $previouslyIdentifiedArthropodSightingFKs)){
			$updateMySQL .= "UPDATE `ExpertIdentification` SET `Rank`='$rank', `TaxonName`='$taxonName', `StandardGroup`='$pluralityIdentification', `Agreement`='$pluralityIdentificationAgreement', `RunnerUpAgreement`='$runnerUpIdentificationVoteAgreement' WHERE `ArthropodSightingFK`='$arthropodSightingFK';";
		}
		else{
			$updateMySQL .= "INSERT INTO `ExpertIdentification` (`ArthropodSightingFK`, `Rank`, `TaxonName`, `StandardGroup`, `Agreement`, `RunnerUpAgreement`) VALUES ('$arthropodSightingFK', '$rank', '$taxonName', '$pluralityIdentification', '$pluralityIdentificationAgreement', '$runnerUpIdentificationVoteAgreement');";
		}
	}
	
	//Run the update queries string we built
	if($updateMySQL != ""){
		$query = mysqli_query($dbconn, $updateMySQL);
	}
	
	//Mark the progress in the database
	if(count($data["results"]) == 0){
		//Finished for the month
		$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0', `Iteration`='0' WHERE `Name`='iNaturalistExpertIdentificationFetch'");
		save($baseFileName . "finishedMonth", date('n'));
	}
	else{
		//Finished with this run, but needs more iterations this month still
		$query = mysqli_query($dbconn, "UPDATE `CronJobStatus` SET `Processing`='0' WHERE `Name`='iNaturalistExpertIdentificationFetch'");
	}
?>
