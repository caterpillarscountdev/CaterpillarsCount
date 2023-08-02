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
 		"bee" => "Bees, Wasps, Sawflies",
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
				$query = mysqli_query($dbconn, "SELECT ArthropodSighting.UpdatedGroup, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS SurveysWithArthropodCount FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY ArthropodSighting.UpdatedGroup");
				while($row = mysqli_fetch_assoc($query)){
					$arthropodSurveys[$row["UpdatedGroup"]] = floatval($row["SurveysWithArthropodCount"]);
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
				$query = mysqli_query($dbconn, "SELECT ArthropodSighting.UpdatedGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY ArthropodSighting.UpdatedGroup");
				while($row = mysqli_fetch_assoc($query)){
					$arthropodCounts[$row["UpdatedGroup"]] = floatval($row["ArthropodCount"]);
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
				$query = mysqli_query($dbconn, "SELECT ArthropodSighting.UpdatedGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY ArthropodSighting.UpdatedGroup, ArthropodSighting.Length");
				while($row = mysqli_fetch_assoc($query)){
					if(!array_key_exists($row["UpdatedGroup"], $arthropodBiomasses)){
						$arthropodBiomasses[$row["UpdatedGroup"]] = 0;
					}
					$arthropodBiomasses[$row["UpdatedGroup"]] += (getBiomass($row["UpdatedGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
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
				$query = mysqli_query($dbconn, "SELECT ArthropodSighting.UpdatedGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY ArthropodSighting.UpdatedGroup");
				while($row = mysqli_fetch_assoc($query)){
					$arthropodCounts[$row["UpdatedGroup"]] = floatval($row["ArthropodCount"]);
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
 	else if(in_array($breakdown, array("year", "month"))){
		//CHECK FOR SAVE
		$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $siteID . $breakdown . $comparisonMetric);
		if($HIGH_TRAFFIC_MODE){
			$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
			if($save !== null){
				die($save);
			}
		}
		
		function renameMonthProperties($obj){
			$keysWereMonths = false;
			$months = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
			foreach($obj as $key => $value){
				if(intval($key) - 1 < count($months)){
					$obj[$months[intval($key) - 1]] = $obj[$key];
					unset($obj[$key]);
					$keysWereMonths = true;
				}
			}
			
			if($keysWereMonths){
				uksort($obj, function($a, $b) use ($months){
					return array_search($a, $months) - array_search($b, $months);
				});
			}
			
			return $obj;
		}
		
		$breakdownUpper = strtoupper($breakdown);
		$breakdownTitle = ucwords($breakdown);
		
		if($comparisonMetric == "occurrence"){
 			$arthropodOccurrencesSet = array();
 			$arthropodSurveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT DISTINCT $breakdownUpper(LocalDate) AS $breakdownTitle FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE SiteFK='$siteID'");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodSurveyCounts[strval($row[$breakdownTitle])] = array();
				$arthropodOccurrencesSet[strval($row[$breakdownTitle])] = array();
 			}
 			$query = mysqli_query($dbconn, "SELECT $breakdownUpper(Survey.LocalDate) AS $breakdownTitle, ArthropodSighting.UpdatedGroup, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS ArthropodSurveyCounts FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY CONCAT($breakdownUpper(Survey.LocalDate), '-', ArthropodSighting.UpdatedGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodSurveyCounts[strval($row[$breakdownTitle])][$row["UpdatedGroup"]] = $row["ArthropodSurveyCounts"];
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT $breakdownUpper(Survey.LocalDate) AS $breakdownTitle, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY $breakdownUpper(Survey.LocalDate)");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[strval($row[$breakdownTitle])] = $row["SurveyCount"];
 			}
 
 			$monthOrYearKeys = array_keys($arthropodSurveyCounts);
 			foreach($monthOrYearKeys as $monthOrYear) {
				$arthropodOccurrences = array();
				$arthropodKeys = array_keys($arthropodSurveyCounts[$monthOrYear]);
				foreach($arthropodKeys as $arthropod){
					$arthropodOccurrences[$readableArthropods[$arthropod]] = round(($arthropodSurveyCounts[$monthOrYear][$arthropod] / $surveyCounts[$monthOrYear]) * 100, 2);
				}
				$arthropodOccurrencesSet[$monthOrYear] = $arthropodOccurrences;
			}
 			uksort($arthropodOccurrencesSet, function($a, $b){
				return intval($a) - intval($b);
			});
			$result = "true|" . json_encode(renameMonthProperties($arthropodOccurrencesSet));
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
 		else if($comparisonMetric == "absoluteDensity"){
 			$arthropodDensitiesSet = array();
 			$arthropodCounts = array();
 			$query = mysqli_query($dbconn, "SELECT DISTINCT $breakdownUpper(LocalDate) AS $breakdownTitle FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE SiteFK='$siteID'");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodCounts[strval($row[$breakdownTitle])] = array();
				$arthropodDensitiesSet[strval($row[$breakdownTitle])] = array();
 			}
 			$query = mysqli_query($dbconn, "SELECT $breakdownUpper(Survey.LocalDate) AS $breakdownTitle, ArthropodSighting.UpdatedGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY CONCAT($breakdownUpper(Survey.LocalDate), '-', ArthropodSighting.UpdatedGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodCounts[strval($row[$breakdownTitle])][$row["UpdatedGroup"]] = $row["ArthropodCount"];
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT $breakdownUpper(Survey.LocalDate) AS $breakdownTitle, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY $breakdownUpper(Survey.LocalDate)");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[strval($row[$breakdownTitle])] = $row["SurveyCount"];
 			}
 
 			$monthOrYearKeys = array_keys($arthropodCounts);
 			foreach($monthOrYearKeys as $monthOrYear) {
				$arthropodDensities = array();
				$arthropodKeys = array_keys($arthropodCounts[$monthOrYear]);
				foreach($arthropodKeys as $arthropod){
					$arthropodDensities[$readableArthropods[$arthropod]] = round($arthropodCounts[$monthOrYear][$arthropod] / $surveyCounts[$monthOrYear], 2);
				}
				$arthropodDensitiesSet[$monthOrYear] = $arthropodDensities;
			}
 			uksort($arthropodDensitiesSet, function($a, $b){
				return intval($a) - intval($b);
			});
			$result = "true|" . json_encode(renameMonthProperties($arthropodDensitiesSet));
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
		else if($comparisonMetric == "meanBiomass"){
			$meanBiomassesSet = array();
 			$biomasses = array();
 			$query = mysqli_query($dbconn, "SELECT DISTINCT $breakdownUpper(LocalDate) AS $breakdownTitle FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE SiteFK='$siteID'");
 			while($row = mysqli_fetch_assoc($query)){
				$biomasses[strval($row[$breakdownTitle])] = array();
				$meanBiomassesSet[strval($row[$breakdownTitle])] = array();
 			}
 			$query = mysqli_query($dbconn, "SELECT $breakdownUpper(Survey.LocalDate) AS $breakdownTitle, ArthropodSighting.UpdatedGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' GROUP BY $breakdownUpper(Survey.LocalDate), ArthropodSighting.UpdatedGroup, ArthropodSighting.Length");
 			while($row = mysqli_fetch_assoc($query)){
				if(!array_key_exists($row["UpdatedGroup"], $biomasses[strval($row[$breakdownTitle])])){
					$biomasses[strval($row[$breakdownTitle])][$row["UpdatedGroup"]] = 0;
				}
				$biomasses[strval($row[$breakdownTitle])][$row["UpdatedGroup"]] += (getBiomass($row["UpdatedGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT $breakdownUpper(Survey.LocalDate) AS $breakdownTitle, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY $breakdownUpper(Survey.LocalDate)");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[strval($row[$breakdownTitle])] = $row["SurveyCount"];
 			}
 
 			$monthOrYearKeys = array_keys($biomasses);
 			foreach($monthOrYearKeys as $monthOrYear) {
				$meanBiomasses = array();
				$arthropodKeys = array_keys($biomasses[$monthOrYear]);
				foreach($arthropodKeys as $arthropod){
					$meanBiomasses[$readableArthropods[$arthropod]] = round(($biomasses[$monthOrYear][$arthropod] / $surveyCounts[$monthOrYear]), 2);
				}
				$meanBiomassesSet[$monthOrYear] = $meanBiomasses;
			}
 			uksort($meanBiomassesSet, function($a, $b){
				return intval($a) - intval($b);
			});
			$result = "true|" . json_encode(renameMonthProperties($meanBiomassesSet));
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
		}
 		else{//relative proportion
			$arthropodRelativeProportionsSet = array();
 			$arthropodCounts = array();
 			$query = mysqli_query($dbconn, "SELECT DISTINCT $breakdownUpper(LocalDate) AS $breakdownTitle FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE SiteFK='$siteID'");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodCounts[strval($row[$breakdownTitle])] = array();
				$arthropodRelativeProportionsSet[strval($row[$breakdownTitle])] = array();
 			}
 			$query = mysqli_query($dbconn, "SELECT $breakdownUpper(Survey.LocalDate) AS $breakdownTitle, ArthropodSighting.UpdatedGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY CONCAT($breakdownUpper(Survey.LocalDate), '-', ArthropodSighting.UpdatedGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodCounts[strval($row[$breakdownTitle])][$row["UpdatedGroup"]] = $row["ArthropodCount"];
 			}
 
 			$allArthropodCounts = array();
 			$query = mysqli_query($dbconn, "SELECT $breakdownUpper(Survey.LocalDate) AS $breakdownTitle, SUM(ArthropodSighting.Quantity) AS AllArthropodsCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' GROUP BY $breakdownUpper(Survey.LocalDate)");
 			while($row = mysqli_fetch_assoc($query)){
 				$allArthropodCounts[strval($row[$breakdownTitle])] = $row["AllArthropodsCount"];
 			}
 
 			$monthOrYearKeys = array_keys($arthropodCounts);
 			foreach($monthOrYearKeys as $monthOrYear) {
				$arthropodRelativeProportions = array();
				$arthropodKeys = array_keys($arthropodCounts[$monthOrYear]);
				foreach($arthropodKeys as $arthropod){
					$arthropodRelativeProportions[$readableArthropods[$arthropod]] = round(($arthropodCounts[$monthOrYear][$arthropod] / $allArthropodCounts[$monthOrYear]) * 100, 2);
				}
				$arthropodRelativeProportionsSet[$monthOrYear] = $arthropodRelativeProportions;
			}
 			uksort($arthropodRelativeProportionsSet, function($a, $b){
				return intval($a) - intval($b);
			});
			$result = "true|" . json_encode(renameMonthProperties($arthropodRelativeProportionsSet));
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
 		}
 	}
 		else{//plant species  OR circle
		//edit fields to allow more than just Species out of the Plant table
		$extraWhere = ' (1=1) '; //show everything
		if ($breakdown=='circle') {
		  $plantField = 'Circle';  
		  $extraWhere = ' (Plant.Circle>0) ';	 // only show positive circles
		} else {
		  $plantField = 'Species';	
		}	
			
		//CHECK FOR SAVE
		$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $siteID . $breakdown . $comparisonMetric);
		if($HIGH_TRAFFIC_MODE){
			$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
			if($save !== null){
				die($save);
			}
		}
		
		$totalDensity = array();
		$circleNums = array();
		$query = mysqli_query($dbconn, "SELECT Plant.$plantField, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN 
		   Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND $extraWhere GROUP BY Plant.$plantField");
		while($row = mysqli_fetch_assoc($query)){
			$totalDensity[$row[$plantField]] = floatval($row["ArthropodCount"]);
		        if ($breakdown=='circle') { //add to circle array
			  $circleNums[$row[$plantField]] = floatval($row["Circle"]); 
			}
		}
		$query = mysqli_query($dbconn, "SELECT Plant.$plantField, COUNT(*) AS SurveyCount, COUNT(DISTINCT Plant.ID) AS Branches FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID 
		   WHERE Plant.SiteFK='$siteID' AND $extraWhere GROUP BY Plant.$plantField");
		while($row = mysqli_fetch_assoc($query)){
			$totalDensity[$row[$plantField]] = $totalDensity[$row[$plantField]] / floatval($row["SurveyCount"]);
		}
		if ($breakdown=='circle') { //sort by circle number
		      asort($circleNums, SORT_NUMERIC);
	              $order = array_keys($circleNums);	
			
		} else {  //typical sort
		      asort($totalDensity, SORT_NUMERIC);
	              $order = array_keys($totalDensity);
		}
		
 		if($comparisonMetric == "occurrence"){
 			$arthropodOccurrencesSet = array();
 			$arthropodSurveyCounts = array();
 			$branchCount = array();
 			$query = mysqli_query($dbconn, "SELECT $plantField, COUNT(*) AS Branches FROM Plant WHERE SiteFK='$siteID' AND $extraWhere GROUP BY $plantField");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodSurveyCounts[$row[$plantField]] = array();
				$arthropodOccurrencesSet[$row[$plantField] . " (" . $row["Branches"] . ")"] = array();
				$branchCount[$row[$plantField]] = $row["Branches"];
 			}
 			$query = mysqli_query($dbconn, "SELECT Plant.$plantField, ArthropodSighting.UpdatedGroup, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS ArthropodSurveyCounts FROM `ArthropodSighting` JOIN Survey ON 
			  ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' AND $extraWhere GROUP BY CONCAT(Plant.$plantField, '-', ArthropodSighting.UpdatedGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodSurveyCounts[$row[$plantField]][$row["UpdatedGroup"]] = $row["ArthropodSurveyCounts"];
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT Plant.$plantField, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND $extraWhere GROUP BY Plant.$plantField");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[$row[$plantField]] = $row["SurveyCount"];
 			}
 			// could be species keys or also circles:
			$speciesKeys = array_keys($arthropodSurveyCounts);
 			foreach($speciesKeys as $species){
				$arthropodOccurrences = array();
				$arthropodKeys = array_keys($arthropodSurveyCounts[$species]);
				foreach($arthropodKeys as $arthropod){
					$arthropodOccurrences[$readableArthropods[$arthropod]] = round(($arthropodSurveyCounts[$species][$arthropod] / $surveyCounts[$species]) * 100, 2);
				}
				$arthropodOccurrencesSet[$species . " (" . $branchCount[$species] . ")"] = $arthropodOccurrences;
			}
 			$resultSet = $arthropodOccurrencesSet;
 		}
 		else if($comparisonMetric == "absoluteDensity"){
 			$arthropodDensitiesSet = array();
 			$arthropodCounts = array();
 			$branchCount = array();
 			$query = mysqli_query($dbconn, "SELECT $plantField, COUNT(*) AS Branches FROM Plant WHERE SiteFK='$siteID' AND $extraWhere GROUP BY $plantField");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodCounts[$row[$plantField]] = array();
				$arthropodDensitiesSet[$row[$plantField] . " (" . $row["Branches"] . ")"] = array();
				$branchCount[$row[$plantField]] = $row["Branches"];
 			}
 			$query = mysqli_query($dbconn, "SELECT Plant.$plantField, ArthropodSighting.UpdatedGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON 
			  ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND $extraWhere GROUP BY CONCAT(Plant.$plantField, '-', ArthropodSighting.UpdatedGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodCounts[$row[$plantField]][$row["UpdatedGroup"]] = $row["ArthropodCount"];
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT Plant.$plantField, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND $extraWhere GROUP BY Plant.$plantField");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[$row[$plantField]] = $row["SurveyCount"];
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
 			$resultSet = $arthropodDensitiesSet;
 		}
		else if($comparisonMetric == "meanBiomass"){
			$meanBiomassesSet = array();
 			$biomasses = array();
 			$branchCount = array();
 			$query = mysqli_query($dbconn, "SELECT $plantField, COUNT(*) AS Branches FROM Plant WHERE SiteFK='$siteID' AND $extraWhere GROUP BY $plantField");
 			while($row = mysqli_fetch_assoc($query)){
				$biomasses[$row[$plantField]] = array();
				$meanBiomassesSet[$row[$plantField] . " (" . $row["Branches"] . ")"] = array();
				$branchCount[$row[$plantField]] = $row["Branches"];
 			}
 			$query = mysqli_query($dbconn, "SELECT Plant.$plantField, ArthropodSighting.UpdatedGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM `ArthropodSighting` JOIN Survey ON 
			  ArthropodSighting.SurveyFK = Survey.ID JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK = '$siteID' AND $extraWhere GROUP BY Plant.$plantField, ArthropodSighting.UpdatedGroup, ArthropodSighting.Length");
 			while($row = mysqli_fetch_assoc($query)){
				if(!array_key_exists($row["UpdatedGroup"], $biomasses[$row[$plantField]])){
					$biomasses[$row[$plantField]][$row["UpdatedGroup"]] = 0;
				}
				$biomasses[$row[$plantField]][$row["UpdatedGroup"]] += (getBiomass($row["UpdatedGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
 			}
 
 			$surveyCounts = array();
 			$query = mysqli_query($dbconn, "SELECT Plant.$plantField, COUNT(Survey.ID) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID'  AND $extraWhere GROUP BY Plant.$plantField");
 			while($row = mysqli_fetch_assoc($query)){
 				$surveyCounts[$row[$plantField]] = $row["SurveyCount"];
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
 			$resultSet = $meanBiomassesSet;
		}
 		else{//relative proportion
			$arthropodRelativeProportionsSet = array();
 			$arthropodCounts = array();
 			$branchCount = array();
 			$query = mysqli_query($dbconn, "SELECT $plantField, COUNT(*) AS Branches FROM Plant WHERE SiteFK='$siteID'  AND $extraWhere  GROUP BY $plantField");
 			while($row = mysqli_fetch_assoc($query)){
				$arthropodCounts[$row[$plantField]] = array();
				$arthropodRelativeProportionsSet[$row[$plantField] . " (" . $row["Branches"] . ")"] = array();
				$branchCount[$row[$plantField]] = $row["Branches"];
 			}
 			$query = mysqli_query($dbconn, "SELECT Plant.$plantField, ArthropodSighting.UpdatedGroup, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON 
			  ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID'  AND $extraWhere  GROUP BY CONCAT(Plant.$plantField, '-', ArthropodSighting.UpdatedGroup)");
 			while($row = mysqli_fetch_assoc($query)){
 				$arthropodCounts[$row[$plantField]][$row["UpdatedGroup"]] = $row["ArthropodCount"];
 			}
 
 			$allArthropodCounts = array();
 			$query = mysqli_query($dbconn, "SELECT Plant.$plantField, SUM(ArthropodSighting.Quantity) AS AllArthropodsCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON 
			  Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID'  AND $extraWhere GROUP BY Plant.$plantField");
 			while($row = mysqli_fetch_assoc($query)){
 				$allArthropodCounts[$row[$plantField]] = $row["AllArthropodsCount"];
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
 			$resultSet = $arthropodRelativeProportionsSet;
		
 		}
		if (!empty($resultSet)) {
		    uksort($resultSet, function($a, $b){
				global $order;
				return array_search(substr($b, 0, strrpos($b, " (")), $order) - array_search(substr($a, 0, strrpos($a, " (")), $order);
			});
			if ($breakdown=='circle'){
				// if breakdown is cicle, we want to sort ascending and also remove () from keys	 
				$resultSet=  array_reverse($resultSet);
				$resultSetCircle = array();
				$resultKeys = array_keys($resultSet);
				foreach($resultKeys as $onekey) {
					$resultSetCircle[substr($onekey, 0, strrpos($onekey, " ("))] = $resultSet[$onekey];
				}
				//swap to new array
			   	$resultSet = $resultSetCircle;
		        }
			$result = "true|" . json_encode($resultSet);
			if($HIGH_TRAFFIC_MODE){
				save($baseFileName, $result);
			}
 			die($result);
		  
	       } //non-empty result set
		
 	}
 ?>
