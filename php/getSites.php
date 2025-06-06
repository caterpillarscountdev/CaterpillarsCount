<?php
	require_once('orm/resources/Keychain.php');
	require_once('resultMemory.php');
	require_once('tools/biomassCalculator.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$cron = true;
	if(isset($_GET["cron"]) && !empty($_GET["cron"])){
		$cron = filter_var($_GET["cron"], FILTER_VALIDATE_BOOLEAN);
	}
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	$includeWetLeaves = filter_var(custgetparam("includeWetLeaves"), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
	$comparisonMetric = mysqli_real_escape_string($dbconn, htmlentities(rawurldecode(custgetparam("comparisonMetric"))));
	$observationMethod = mysqli_real_escape_string($dbconn, htmlentities(custgetparam("observationMethod")));
	$monthStart = sprintf('%02d', intval(custgetparam("monthStart")));
	$monthEnd = sprintf('%02d', intval(custgetparam("monthEnd")));
	$yearStart = intval(custgetparam("yearStart"));
	$yearEnd = intval(custgetparam("yearEnd"));
	$arthropod = mysqli_real_escape_string($dbconn, htmlentities(rawurldecode(custgetparam("arthropod"))));//% if all
	$minSize = intval(custgetparam("minSize"));
	$plantSpecies = mysqli_real_escape_string($dbconn, htmlentities(rawurldecode(custgetparam("plantSpecies"))));//% if all
	if($cron){
		$includeWetLeaves = 1;
		$comparisonMetric = "occurrence";
		$observationMethod = "%";
		$monthStart = sprintf('%02d', 1);
		$monthEnd = sprintf('%02d', 12);
		$yearStart = 0;
		$yearEnd = 99999;
		$arthropod = mysqli_real_escape_string($dbconn, htmlentities("caterpillar"));//% if all
		$minSize = 0;
		$plantSpecies = "%";//% if all
	}
	$HIGH_TRAFFIC_MODE = true;
	$SAVE_TIME_LIMIT = 15 * 60;
	
	$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $includeWetLeaves . $comparisonMetric . str_replace("%", "all", $observationMethod) . $monthStart . $monthEnd . $yearStart . $yearEnd . str_replace("%", "all", $arthropod) . $minSize . str_replace("%", "all", $plantSpecies));
	if($HIGH_TRAFFIC_MODE && !$cron){
		$save = getSaveFromDatabase($baseFileName, $SAVE_TIME_LIMIT);
		if($save !== null){
			die($save);
		}
	}
	$sitesArray = array();
	$siteIDs = array();
	$query = mysqli_query($dbconn, "SELECT `ID`, `Name`, `Latitude`, `Longitude`, `Description`, `DateEstablished`, `Active` FROM `Site`");
	while($row = mysqli_fetch_assoc($query)){
		$siteIDs[] = strval($row["ID"]);
		$sitesArray[strval($row["ID"])] = array(
			"ID" => intval($row["ID"]),
			"Name" => $row["Name"],
			"Coordinates" => $row["Latitude"] . "," . $row["Longitude"],
			"Description" => $row["Description"],
			"DateEstablished" => $row["DateEstablished"],
                        "Active" => $row["Active"],
                        "FilteredSurveyCount" => 0
		);
	}
	
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(*) AS FilteredSurveyCount FROM Survey JOIN Plant ON Plant.ID=Survey.PlantFK WHERE Survey.ReviewedAndApproved < 3 AND MONTH(Survey.LocalDate)>=$monthStart AND MONTH(Survey.LocalDate)<=$monthEnd AND YEAR(Survey.LocalDate)>=$yearStart AND YEAR(Survey.LocalDate)<=$yearEnd AND (Plant.Species LIKE '$plantSpecies' OR (Plant.Species='N/A' AND Survey.PlantSpecies LIKE '$plantSpecies')) AND Survey.WetLeaves IN (0, $includeWetLeaves) AND Survey.ObservationMethod LIKE '$observationMethod' GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["FilteredSurveyCount"] = intval($row["FilteredSurveyCount"]);
	}

        
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT Survey.ID) AS SurveyCount, COUNT(DISTINCT Survey.UserFKOfObserver) AS UserCount, COUNT(DISTINCT ArthropodSighting.UpdatedGroup) AS ArthropodGroupCount, SUM(ArthropodSighting.Quantity) AS ArthropodCount, MAX(STR_TO_DATE(CONCAT(Survey.LocalDate, ' ', Survey.LocalTime), '%Y-%m-%d %T')) AS MostRecentDateTime, MIN(STR_TO_DATE(CONCAT(Survey.LocalDate, ' ', Survey.LocalTime), '%Y-%m-%d %T')) AS EarliestDateTime FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID LEFT JOIN ArthropodSighting ON Survey.ID=ArthropodSighting.SurveyFK WHERE Survey.ReviewedAndApproved < 3 GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["SurveyCount"] = intval($row["SurveyCount"]);
		$sitesArray[strval($row["SiteFK"])]["UserCount"] = intval($row["UserCount"]);
		$sitesArray[strval($row["SiteFK"])]["ArthropodGroupCount"] = intval($row["ArthropodGroupCount"]);
		$sitesArray[strval($row["SiteFK"])]["ArthropodCount"] = intval($row["ArthropodCount"]);
		$sitesArray[strval($row["SiteFK"])]["MostRecentDateTime"] = $row["MostRecentDateTime"];
		$sitesArray[strval($row["SiteFK"])]["EarliestDateTime"] = $row["EarliestDateTime"];
	}

        $query = mysqli_query($dbconn, "SELECT Plant.SiteFK, SUM(ArthropodSighting.Quantity) AS CaterpillarCount, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS Caterpillars FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID JOIN ArthropodSighting ON Survey.ID=ArthropodSighting.SurveyFK WHERE Survey.ReviewedAndApproved < 3 AND ArthropodSighting.UpdatedGroup='caterpillar' GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["CaterpillarCount"] = intval($row["CaterpillarCount"]);
                $sitesArray[strval($row["SiteFK"])]["Caterpillars"] = $sitesArray[strval($row["SiteFK"])]["FilteredSurveyCount"] !=0 ? round(((floatval($row["Caterpillars"]) / floatval($sitesArray[strval($row["SiteFK"])]["FilteredSurveyCount"])) * 100), 2) . "%": 0;
	}

	
	$minLoggedDensity = 9999;
	if($comparisonMetric == "occurrence"){
		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS Arthropods FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.ReviewedAndApproved < 3 AND MONTH(Survey.LocalDate)>=$monthStart AND MONTH(Survey.LocalDate)<=$monthEnd AND YEAR(Survey.LocalDate)>=$yearStart AND YEAR(Survey.LocalDate)<=$yearEnd AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND (Plant.Species LIKE '$plantSpecies' OR (Plant.Species='N/A' AND Survey.PlantSpecies LIKE '$plantSpecies')) AND Survey.WetLeaves IN (0, $includeWetLeaves) AND ArthropodSighting.Length>=$minSize AND Survey.ObservationMethod LIKE '$observationMethod' GROUP BY Plant.SiteFK");
		while($row = mysqli_fetch_assoc($query)){
                  $occurrencePercentage = floatval($sitesArray[strval($row["SiteFK"])]["FilteredSurveyCount"]) !=0 ? round(((floatval($row["Arthropods"]) / floatval($sitesArray[strval($row["SiteFK"])]["FilteredSurveyCount"])) * 100), 2) : 0;
                  $sitesArray[strval($row["SiteFK"])]["RawValue"] = $occurrencePercentage . "%";
                  $sitesArray[strval($row["SiteFK"])]["Weight"] = $occurrencePercentage;
		}
	}
	else if($comparisonMetric == "density"){
		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, SUM(ArthropodSighting.Quantity) AS Arthropods FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID JOIN ArthropodSighting ON Survey.ID=ArthropodSighting.SurveyFK WHERE Survey.ReviewedAndApproved < 3 AND MONTH(Survey.LocalDate)>=$monthStart AND MONTH(Survey.LocalDate)<=$monthEnd AND YEAR(Survey.LocalDate)>=$yearStart AND YEAR(Survey.LocalDate)<=$yearEnd AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND (Plant.Species LIKE '$plantSpecies' OR (Plant.Species='N/A' AND Survey.PlantSpecies LIKE '$plantSpecies')) AND Survey.WetLeaves IN (0, $includeWetLeaves) AND ArthropodSighting.Length>=$minSize AND Survey.ObservationMethod LIKE '$observationMethod' GROUP BY Plant.SiteFK");
		while($row = mysqli_fetch_assoc($query)){
                  $arthropodsPerSurvey = floatval($sitesArray[strval($row["SiteFK"])]["FilteredSurveyCount"]) !=0 ? floatval($row["Arthropods"]) / floatval($sitesArray[strval($row["SiteFK"])]["FilteredSurveyCount"]) : 0;
			$sitesArray[strval($row["SiteFK"])]["RawValue"] = round($arthropodsPerSurvey, 2);
			$loggedDensity = round(log10($arthropodsPerSurvey + 0.000000000000000000000000000000000000000000000000001), 2);
			$sitesArray[strval($row["SiteFK"])]["Weight"] = $loggedDensity;
			if($loggedDensity < $minLoggedDensity || $minLoggedDensity == 9999){
				$minLoggedDensity = $loggedDensity;
			}
		}
	}
	else{//mean biomass
		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, ArthropodSighting.UpdatedGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.ReviewedAndApproved < 3 AND MONTH(Survey.LocalDate)>=$monthStart AND MONTH(Survey.LocalDate)<=$monthEnd AND YEAR(Survey.LocalDate)>=$yearStart AND YEAR(Survey.LocalDate)<=$yearEnd AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND (Plant.Species LIKE '$plantSpecies' OR (Plant.Species='N/A' AND Survey.PlantSpecies LIKE '$plantSpecies')) AND Survey.WetLeaves IN (0, $includeWetLeaves) AND ArthropodSighting.Length>=$minSize AND Survey.ObservationMethod LIKE '$observationMethod' GROUP BY Plant.SiteFK, ArthropodSighting.UpdatedGroup, ArthropodSighting.Length");
		$siteBiomasses = array();
		while($row = mysqli_fetch_assoc($query)){
			if(!array_key_exists(strval($row["SiteFK"]), $siteBiomasses)){
				$siteBiomasses[strval($row["SiteFK"])] = 0;
			}
			$siteBiomasses[strval($row["SiteFK"])] += (getBiomass($row["UpdatedGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
		}
		
		foreach($siteBiomasses as $siteID => $totalBiomass){
                  $meanBiomassPerSurvey = floatval($sitesArray[strval($siteID)]["FilteredSurveyCount"]) !=0 ? round(($totalBiomass / floatval($sitesArray[strval($siteID)]["FilteredSurveyCount"])), 2) : 0;
			$sitesArray[strval($siteID)]["RawValue"] = $meanBiomassPerSurvey;
			$sitesArray[strval($siteID)]["Weight"] = $meanBiomassPerSurvey;
		}
	}
	
	for($i = 0; $i < count($siteIDs); $i++){
		if(!array_key_exists("SurveyCount", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["SurveyCount"] = 0;
		}
		if(!array_key_exists("FilteredSurveyCount", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["FilteredSurveyCount"] = 0;
		}
		if(!array_key_exists("UserCount", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["UserCount"] = 0;
		}
		if(!array_key_exists("ArthropodGroupCount", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["ArthropodGroupCount"] = 0;
		}
		if(!array_key_exists("ArthropodCount", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["ArthropodCount"] = 0;
		}
		if(!array_key_exists("CaterpillarCount", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["CaterpillarCount"] = 0;
		}
		if(!array_key_exists("Caterpillars", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["Caterpillars"] = "0%";
		}
		if(!array_key_exists("MostRecentDateTime", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["MostRecentDateTime"] = "Never";
		}
		if(!array_key_exists("EarliestDateTime", $sitesArray[$siteIDs[$i]])){
			$sitesArray[$siteIDs[$i]]["EarliestDateTime"] = "Never";
		}
		if(!array_key_exists("RawValue", $sitesArray[$siteIDs[$i]])){
			if($sitesArray[$siteIDs[$i]]["MostRecentDateTime"] == "Never"){
				$sitesArray[$siteIDs[$i]]["RawValue"] = "No Surveys";
			}
			else if($comparisonMetric == "occurrence"){
				$sitesArray[$siteIDs[$i]]["RawValue"] = "0%";
			}
			else{//density or mean biomass
				$sitesArray[$siteIDs[$i]]["RawValue"] = 0;
			}
		}
		if(!array_key_exists("Weight", $sitesArray[$siteIDs[$i]])){
			if($comparisonMetric == "density"){
				$sitesArray[$siteIDs[$i]]["Weight"] = $minLoggedDensity * .99;
			}
			else{//occurrence or mean biomass
				$sitesArray[$siteIDs[$i]]["Weight"] = 0;
			}
		}
	}
	$result = json_encode(array_values($sitesArray));
	if($HIGH_TRAFFIC_MODE){
		saveToDatabase($baseFileName, $result);
	}
	die($result);
?>
