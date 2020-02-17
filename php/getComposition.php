<?php
 	require_once('orm/resources/Keychain.php');
	require_once('resultMemory.php');
	require_once('tools/biomassCalculator.php');
   
 	$siteIDs = json_decode($_GET["siteIDs"]);
 	$breakdown = $_GET["breakdown"]; //site, year, plant species, none
 	$comparisonMetric = $_GET["comparisonMetric"]; //occurrence, absoluteDensity, relativeProportion, meanBiomass
	$HIGH_TRAFFIC_MODE = true;
	$SAVE_TIME_LIMIT = 15 * 60;
   
 	$dbconn = (new Keychain)->getDatabaseConnection();
 	$readableArthropods = array(
 		"ant" => "Ants",
 		"aphid" => "Aphids and Psyllids",
 		"bee" => "Bees and Wasps",
 		"beetle" => "Beetles",
 		"caterpillar" => "Caterpillars",
 		"daddylonglegs" => "Daddy Longlegs",
 		"fly" => "Flies",
 		"grasshopper" => "Grasshoppers and Crickets",
 		"leafhopper" => "Leaf Hoppers and Cicadas",
 		"moths" => "Butterflies and Moths",
 		"spider" => "Spiders",
 		"truebugs" => "True Bugs",
 		"other" => "Other",
 		"unidentified" => "Unidentified"
 	);
 	$siteID = intval($siteIDs[0]);
   
 	if($breakdown == "site" || $breakdown == "none"){
 		//get percents
 		$arthropodPercents = array();
 		for($i = 0; $i < count($siteIDs); $i++){
 			$siteID = intval($siteIDs[$i]);
			$query = mysqli_query($dbconn, "SELECT `Name` FROM `Site` WHERE `ID`='$siteID' LIMIT 1");
			if(mysqli_num_rows($query) == 0){
				continue;
			}
			$siteName = mysqli_fetch_assoc($query)["Name"];
			
			//CHECK FOR SAVE
			$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $siteID . str_replace("site", "none", $breakdown) . $comparisonMetric);
			if($HIGH_TRAFFIC_MODE){
				$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
				if($save !== null){
					$arthropodPercents[strval($siteName)] = json_decode($save);
					continue;
				}
			}
 			
 			if($comparisonMetric == "occurrence"){
				//surveys with arthropod at site
				$arthropodSurveys = array();
				$query = mysqli_query($dbconn, "SELECT ArthropodSighting.LeadingGroup, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS SurveysWithArthropodCount FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY ArthropodSighting.LeadingGroup");
				while($row = mysqli_fetch_assoc($query)){
					$arthropodSurveys[$row["LeadingGroup"]] = floatval($row["SurveysWithArthropodCount"]);
				}
				//surveys at site
				$query = mysqli_query($dbconn, "SELECT COUNT(*) AS TotalSurveyCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID'");
				$totalSurveyCount = floatval(mysqli_fetch_assoc($query)["TotalSurveyCount"]);
				$arthropodOccurrence = array();
				$keys = array_keys($arthropodSurveys);
				for($i = 0; $i < count($keys); $i++){
					$arthropodOccurrence[$readableArthropods[$keys[$i]]] = round(($arthropodSurveys[$keys[$i]] / $totalSurveyCount) * 100, 2);
				}
				//SAVE
				if($HIGH_TRAFFIC_MODE){
					save($baseFileName, json_encode($arthropodOccurrence));
				}
 				$arthropodPercents[strval($siteName)] = $arthropodOccurrence;
 			}
 			else if($comparisonMetric == "absoluteDensity"){
				//sum of each arthropod at site
				$arthropodCounts = array();
				$query = mysqli_query($dbconn, "SELECT ArthropodSighting.LeadingGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY ArthropodSighting.LeadingGroup");
				while($row = mysqli_fetch_assoc($query)){
					$arthropodCounts[$row["LeadingGroup"]] = floatval($row["ArthropodCount"]);
				}
				//total survey count at site
				$query = mysqli_query($dbconn, "SELECT COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID'");
				$surveyCount = floatval(mysqli_fetch_assoc($query)["SurveyCount"]);
				$arthropodAbsoluteDensity = array();
				$keys = array_keys($arthropodCounts);
				for($i = 0; $i < count($keys); $i++){
					$arthropodAbsoluteDensity[$readableArthropods[$keys[$i]]] = round(($arthropodCounts[$keys[$i]] / $surveyCount), 2);
				}
 				//SAVE
				if($HIGH_TRAFFIC_MODE){
					save($baseFileName, json_encode($arthropodAbsoluteDensity));
				}
 				$arthropodPercents[strval($siteName)] = $arthropodAbsoluteDensity;
 			}
			else if($comparisonMetric == "meanBiomass"){
				//total biomass at site
				$arthropodBiomasses = array();
				$query = mysqli_query($dbconn, "SELECT ArthropodSighting.LeadingGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY ArthropodSighting.LeadingGroup, ArthropodSighting.Length");
				while($row = mysqli_fetch_assoc($query)){
					if(!array_key_exists($row["LeadingGroup"], $arthropodBiomasses)){
						$arthropodBiomasses[$row["LeadingGroup"]] = 0;
					}
					$arthropodBiomasses[$row["LeadingGroup"]] += (getBiomass($row["LeadingGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
				}
				//surveys at site
				$query = mysqli_query($dbconn, "SELECT COUNT(*) AS TotalSurveyCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID'");
				$totalSurveyCount = floatval(mysqli_fetch_assoc($query)["TotalSurveyCount"]);
				$arthropodMeanBiomass = array();
				$keys = array_keys($arthropodBiomasses);
				for($i = 0; $i < count($keys); $i++){
					$arthropodMeanBiomass[$readableArthropods[$keys[$i]]] = round(($arthropodBiomasses[$keys[$i]] / $totalSurveyCount), 2);
				}
				//SAVE
				if($HIGH_TRAFFIC_MODE){
					save($baseFileName, json_encode($arthropodMeanBiomass));
				}
 				$arthropodPercents[strval($siteName)] = $arthropodMeanBiomass;
			}
 			else{//relativeProportion
				//sum of each arthropod at site
				$arthropodCounts = array();
				$query = mysqli_query($dbconn, "SELECT ArthropodSighting.LeadingGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY ArthropodSighting.LeadingGroup");
				while($row = mysqli_fetch_assoc($query)){
					$arthropodCounts[$row["LeadingGroup"]] = floatval($row["ArthropodCount"]);
				}
				//total survey count at site
				$query = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) AS AllArthropodsCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID'");
				$allArthropodsCount = floatval(mysqli_fetch_assoc($query)["AllArthropodsCount"]);
				$arthropodRelativeProportion = array();
				$keys = array_keys($arthropodCounts);
				for($i = 0; $i < count($keys); $i++){
					$arthropodRelativeProportion[$readableArthropods[$keys[$i]]] = round(($arthropodCounts[$keys[$i]] / $allArthropodsCount) * 100, 2);
				}
 				//SAVE
				if($HIGH_TRAFFIC_MODE){
					save($baseFileName, json_encode($arthropodRelativeProportion));
				}
 				$arthropodPercents[strval($siteName)] = $arthropodRelativeProportion;
 			}
 		}
 		die("true|" . json_encode($arthropodPercents));
 	}
 	else if($breakdown == "year"){
		//CHECK FOR SAVE
		$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $siteID . $breakdown . $comparisonMetric);
		if($HIGH_TRAFFIC_MODE){
			$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
			if($save !== null){
				die($save);
			}
		}
		
		if($comparisonMetric == "occurrence"){
 			$arthropodOccurrencesSet = array();
 			$arthropodSurveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT DISTINCT YEAR(LocalDate) AS Year FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE SiteFK='$siteID'");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodSurveyCounts[strval($row["Year"])] = array();
				$arthropodOccurrencesSet[strval($row["Year"])] = array();
 			}
 			$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, ArthropodSighting.LeadingGroup, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS ArthropodSurveyCounts FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY CONCAT(YEAR(Survey.LocalDate), '-', ArthropodSighting.LeadingGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodSurveyCounts[strval($row["Year"])][$row["LeadingGroup"]] = $row["ArthropodSurveyCounts"];
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY YEAR(Survey.LocalDate)");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[strval($row["Year"])] = $row["SurveyCount"];
 			}
 
 			$yearKeys = array_keys($arthropodSurveyCounts);
 			foreach($yearKeys as $year) {
				$arthropodOccurrences = array();
				$arthropodKeys = array_keys($arthropodSurveyCounts[$year]);
				foreach($arthropodKeys as $arthropod){
					$arthropodOccurrences[$readableArthropods[$arthropod]] = round(($arthropodSurveyCounts[$year][$arthropod] / $surveyCounts[$year]) * 100, 2);
				}
				$arthropodOccurrencesSet[$year] = $arthropodOccurrences;
			}
 			uksort($arthropodOccurrencesSet, function($a, $b){
				return intval($a) - intval($b);
			});
			$result = "true|" . json_encode($arthropodOccurrencesSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
 		else if($comparisonMetric == "absoluteDensity"){
 			$arthropodDensitiesSet = array();
 			$arthropodCounts = array();
 			$query = mysqli_query($dbconn, "SELECT DISTINCT YEAR(LocalDate) AS Year FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE SiteFK='$siteID'");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodCounts[strval($row["Year"])] = array();
				$arthropodDensitiesSet[strval($row["Year"])] = array();
 			}
 			$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, ArthropodSighting.LeadingGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY CONCAT(YEAR(Survey.LocalDate), '-', ArthropodSighting.LeadingGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodCounts[strval($row["Year"])][$row["LeadingGroup"]] = $row["ArthropodCount"];
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY YEAR(Survey.LocalDate)");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[strval($row["Year"])] = $row["SurveyCount"];
 			}
 
 			$yearKeys = array_keys($arthropodCounts);
 			foreach($yearKeys as $year) {
				$arthropodDensities = array();
				$arthropodKeys = array_keys($arthropodCounts[$year]);
				foreach($arthropodKeys as $arthropod){
					$arthropodDensities[$readableArthropods[$arthropod]] = round($arthropodCounts[$year][$arthropod] / $surveyCounts[$year], 2);
				}
				$arthropodDensitiesSet[$year] = $arthropodDensities;
			}
 			uksort($arthropodDensitiesSet, function($a, $b){
				return intval($a) - intval($b);
			});
			$result = "true|" . json_encode($arthropodDensitiesSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
		else if($comparisonMetric == "meanBiomass"){
			$meanBiomassesSet = array();
 			$biomasses = array();
 			$query = mysqli_query($dbconn, "SELECT DISTINCT YEAR(LocalDate) AS Year FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE SiteFK='$siteID'");
 			while($row = mysqli_fetch_assoc($query)){
				$biomasses[strval($row["Year"])] = array();
				$meanBiomassesSet[strval($row["Year"])] = array();
 			}
 			$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, ArthropodSighting.LeadingGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY YEAR(Survey.LocalDate), ArthropodSighting.LeadingGroup, ArthropodSighting.Length");
 			while($row = mysqli_fetch_assoc($query)){
				if(!array_key_exists($row["LeadingGroup"], $biomasses[strval($row["Year"])])){
					$biomasses[strval($row["Year"])][$row["LeadingGroup"]] = 0;
				}
				$biomasses[strval($row["Year"])][$row["LeadingGroup"]] += (getBiomass($row["LeadingGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY YEAR(Survey.LocalDate)");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[strval($row["Year"])] = $row["SurveyCount"];
 			}
 
 			$yearKeys = array_keys($biomasses);
 			foreach($yearKeys as $year) {
				$meanBiomasses = array();
				$arthropodKeys = array_keys($biomasses[$year]);
				foreach($arthropodKeys as $arthropod){
					$meanBiomasses[$readableArthropods[$arthropod]] = round(($biomasses[$year][$arthropod] / $surveyCounts[$year]), 2);
				}
				$meanBiomassesSet[$year] = $meanBiomasses;
			}
 			uksort($meanBiomassesSet, function($a, $b){
				return intval($a) - intval($b);
			});
			$result = "true|" . json_encode($meanBiomassesSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
		}
 		else{//relative proportion
			$arthropodRelativeProportionsSet = array();
 			$arthropodCounts = array();
 			$query = mysqli_query($dbconn, "SELECT DISTINCT YEAR(LocalDate) AS Year FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE SiteFK='$siteID'");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodCounts[strval($row["Year"])] = array();
				$arthropodRelativeProportionsSet[strval($row["Year"])] = array();
 			}
 			$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, ArthropodSighting.LeadingGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY CONCAT(YEAR(Survey.LocalDate), '-', ArthropodSighting.LeadingGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodCounts[strval($row["Year"])][$row["LeadingGroup"]] = $row["ArthropodCount"];
 			}
 
 			$allArthropodCounts = array();
 			$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, SUM(ArthropodSighting.Quantity) AS AllArthropodsCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY YEAR(Survey.LocalDate)");
 			while($row = mysqli_fetch_assoc($query)){
 				$allArthropodCounts[strval($row["Year"])] = $row["AllArthropodsCount"];
 			}
 
 			$yearKeys = array_keys($arthropodCounts);
 			foreach($yearKeys as $year) {
				$arthropodRelativeProportions = array();
				$arthropodKeys = array_keys($arthropodCounts[$year]);
				foreach($arthropodKeys as $arthropod){
					$arthropodRelativeProportions[$readableArthropods[$arthropod]] = round(($arthropodCounts[$year][$arthropod] / $allArthropodCounts[$year]) * 100, 2);
				}
				$arthropodRelativeProportionsSet[$year] = $arthropodRelativeProportions;
			}
 			uksort($arthropodRelativeProportionsSet, function($a, $b){
				return intval($a) - intval($b);
			});
			$result = "true|" . json_encode($arthropodRelativeProportionsSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
 	}
 	else{//plant species
		//CHECK FOR SAVE
		$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $siteID . $breakdown . $comparisonMetric);
		if($HIGH_TRAFFIC_MODE){
			$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
			if($save !== null){
				die($save);
			}
		}
		
		$totalDensity = array();
		$query = mysqli_query($dbconn, "SELECT Plant.Species, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY Plant.Species");
		while($row = mysqli_fetch_assoc($query)){
			$totalDensity[$row["Species"]] = floatval($row["ArthropodCount"]);
		}
		$query = mysqli_query($dbconn, "SELECT Plant.Species, COUNT(*) AS SurveyCount, COUNT(DISTINCT Plant.ID) AS Branches FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY Plant.Species");
		while($row = mysqli_fetch_assoc($query)){
			$totalDensity[$row["Species"]] = $totalDensity[$row["Species"]] / floatval($row["SurveyCount"]);
		}
		asort($totalDensity, SORT_NUMERIC);
		$order = array_keys($totalDensity);
		
 		if($comparisonMetric == "occurrence"){
 			$arthropodOccurrencesSet = array();
 			$arthropodSurveyCounts = array();
 			$branchCount = array();
 			$query = mysqli_query($dbconn, "SELECT Species, COUNT(*) AS Branches FROM Plant WHERE SiteFK='$siteID' GROUP BY Species");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodSurveyCounts[$row["Species"]] = array();
				$arthropodOccurrencesSet[$row["Species"] . " (" . $row["Branches"] . ")"] = array();
				$branchCount[$row["Species"]] = $row["Branches"];
 			}
 			$query = mysqli_query($dbconn, "SELECT Plant.Species, ArthropodSighting.LeadingGroup, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS ArthropodSurveyCounts FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY CONCAT(Plant.Species, '-', ArthropodSighting.LeadingGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodSurveyCounts[$row["Species"]][$row["LeadingGroup"]] = $row["ArthropodSurveyCounts"];
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT Plant.Species, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY Plant.Species");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[$row["Species"]] = $row["SurveyCount"];
 			}
 			
			$speciesKeys = array_keys($arthropodSurveyCounts);
 			foreach($speciesKeys as $species){
				$arthropodOccurrences = array();
				$arthropodKeys = array_keys($arthropodSurveyCounts[$species]);
				foreach($arthropodKeys as $arthropod){
					$arthropodOccurrences[$readableArthropods[$arthropod]] = round(($arthropodSurveyCounts[$species][$arthropod] / $surveyCounts[$species]) * 100, 2);
				}
				$arthropodOccurrencesSet[$species . " (" . $branchCount[$species] . ")"] = $arthropodOccurrences;
			}
 			uksort($arthropodOccurrencesSet, function($a, $b){
				global $order;
				return array_search(substr($b, 0, strrpos($b, " (")), $order) - array_search(substr($a, 0, strrpos($a, " (")), $order);
			});
			$result = "true|" . json_encode($arthropodOccurrencesSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
 		else if($comparisonMetric == "absoluteDensity"){
 			$arthropodDensitiesSet = array();
 			$arthropodCounts = array();
 			$branchCount = array();
 			$query = mysqli_query($dbconn, "SELECT Species, COUNT(*) AS Branches FROM Plant WHERE SiteFK='$siteID' GROUP BY Species");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodCounts[$row["Species"]] = array();
				$arthropodDensitiesSet[$row["Species"] . " (" . $row["Branches"] . ")"] = array();
				$branchCount[$row["Species"]] = $row["Branches"];
 			}
 			$query = mysqli_query($dbconn, "SELECT Plant.Species, ArthropodSighting.LeadingGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY CONCAT(Plant.Species, '-', ArthropodSighting.LeadingGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodCounts[$row["Species"]][$row["LeadingGroup"]] = $row["ArthropodCount"];
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT Plant.Species, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY Plant.Species");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[$row["Species"]] = $row["SurveyCount"];
 			}
 
 			$speciesKeys = array_keys($arthropodCounts);
 			foreach($speciesKeys as $species){
				$arthropodDensities = array();
				$arthropodKeys = array_keys($arthropodCounts[$species]);
				foreach($arthropodKeys as $arthropod){
					$arthropodDensities[$readableArthropods[$arthropod]] = round($arthropodCounts[$species][$arthropod] / $surveyCounts[$species], 2);
				}
				$arthropodDensitiesSet[$species . " (" . $branchCount[$species] . ")"] = $arthropodDensities;
			}
 			uksort($arthropodDensitiesSet, function($a, $b){
				global $order;
				return array_search(substr($b, 0, strrpos($b, " (")), $order) - array_search(substr($a, 0, strrpos($a, " (")), $order);
			});
			$result = "true|" . json_encode($arthropodDensitiesSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
		else if($comparisonMetric == "meanBiomass"){
			$meanBiomassesSet = array();
 			$biomasses = array();
 			$branchCount = array();
 			$query = mysqli_query($dbconn, "SELECT Species, COUNT(*) AS Branches FROM Plant WHERE SiteFK='$siteID' GROUP BY Species");
 			while($row = mysqli_fetch_assoc($query)){
				$biomasses[$row["Species"]] = array();
				$meanBiomassesSet[$row["Species"] . " (" . $row["Branches"] . ")"] = array();
				$branchCount[$row["Species"]] = $row["Branches"];
 			}
 			$query = mysqli_query($dbconn, "SELECT Plant.Species, ArthropodSighting.LeadingGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY Plant.Species, ArthropodSighting.LeadingGroup, ArthropodSighting.Length");
 			while($row = mysqli_fetch_assoc($query)){
				if(!array_key_exists($row["LeadingGroup"], $biomasses[$row["Species"]])){
					$biomasses[$row["Species"]][$row["LeadingGroup"]] = 0;
				}
				$biomasses[$row["Species"]][$row["LeadingGroup"]] += (getBiomass($row["LeadingGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT Plant.Species, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY Plant.Species");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[$row["Species"]] = $row["SurveyCount"];
 			}
 			
			$speciesKeys = array_keys($biomasses);
 			foreach($speciesKeys as $species){
				$meanBiomasses = array();
				$arthropodKeys = array_keys($biomasses[$species]);
				foreach($arthropodKeys as $arthropod){
					$meanBiomasses[$readableArthropods[$arthropod]] = round(($biomasses[$species][$arthropod] / $surveyCounts[$species]), 2);
				}
				$meanBiomassesSet[$species . " (" . $branchCount[$species] . ")"] = $meanBiomasses;
			}
 			uksort($meanBiomassesSet, function($a, $b){
				global $order;
				return array_search(substr($b, 0, strrpos($b, " (")), $order) - array_search(substr($a, 0, strrpos($a, " (")), $order);
			});
			$result = "true|" . json_encode($meanBiomassesSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
		}
 		else{//relative proportion
			$arthropodRelativeProportionsSet = array();
 			$arthropodCounts = array();
 			$branchCount = array();
 			$query = mysqli_query($dbconn, "SELECT Species, COUNT(*) AS Branches FROM Plant WHERE SiteFK='$siteID' GROUP BY Species");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodCounts[$row["Species"]] = array();
				$arthropodRelativeProportionsSet[$row["Species"] . " (" . $row["Branches"] . ")"] = array();
				$branchCount[$row["Species"]] = $row["Branches"];
 			}
 			$query = mysqli_query($dbconn, "SELECT Plant.Species, ArthropodSighting.LeadingGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY CONCAT(Plant.Species, '-', ArthropodSighting.LeadingGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodCounts[$row["Species"]][$row["LeadingGroup"]] = $row["ArthropodCount"];
 			}
 
 			$allArthropodCounts = array();
 			$query = mysqli_query($dbconn, "SELECT Plant.Species, SUM(ArthropodSighting.Quantity) AS AllArthropodsCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY Plant.Species");
 			while($row = mysqli_fetch_assoc($query)){
 				$allArthropodCounts[$row["Species"]] = $row["AllArthropodsCount"];
 			}
 
 			$speciesKeys = array_keys($arthropodCounts);
 			foreach($speciesKeys as $species){
				$arthropodRelativeProportions = array();
				$arthropodKeys = array_keys($arthropodCounts[$species]);
				foreach($arthropodKeys as $arthropod){
					$arthropodRelativeProportions[$readableArthropods[$arthropod]] = round(($arthropodCounts[$species][$arthropod] / $allArthropodCounts[$species]) * 100, 2);
				}
				$arthropodRelativeProportionsSet[$species . " (" . $branchCount[$species] . ")"] = $arthropodRelativeProportions;
			}
 			uksort($arthropodRelativeProportionsSet, function($a, $b){
				global $order;
				return array_search(substr($b, 0, strrpos($b, " (")), $order) - array_search(substr($a, 0, strrpos($a, " (")), $order);
			});
			$result = "true|" . json_encode($arthropodRelativeProportionsSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
 	}
 ?>
