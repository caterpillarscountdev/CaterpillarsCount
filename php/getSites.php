<?php
	require_once('orm/resources/Keychain.php');
	require_once('resultMemory.php');
	
	$dbconn = (new Keychain)->getDatabaseConnection();

	$includeWetLeaves = filter_var($_GET["includeWetLeaves"], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
	$occurrenceInsteadOfDensity = filter_var($_GET["occurrenceInsteadOfDensity"], FILTER_VALIDATE_BOOLEAN);
	$observationMethod = mysqli_real_escape_string($dbconn, $_GET["observationMethod"]);
	$monthStart = sprintf('%02d', intval($_GET["monthStart"]));
	$monthEnd = sprintf('%02d', intval($_GET["monthEnd"]));
	$yearStart = intval($_GET["yearStart"]);
	$yearEnd = intval($_GET["yearEnd"]);
	$arthropod = mysqli_real_escape_string($dbconn, rawurldecode($_GET["arthropod"]));//% if all
	$minSize = intval($_GET["minSize"]);
	$plantSpecies = mysqli_real_escape_string($dbconn, rawurldecode($_GET["plantSpecies"]));//% if all

	$HIGH_TRAFFIC_MODE = true;
	$SAVE_TIME_LIMIT = 20;
	
	$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $includeWetLeaves . ($occurrenceInsteadOfDensity ? 1 : 0) . str_replace("%", "all", $observationMethod) . $monthStart . $monthEnd . $yearStart . $yearEnd . str_replace("%", "all", $arthropod) . $minSize . str_replace("%", "all", $plantSpecies));
	if($HIGH_TRAFFIC_MODE){
		$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
		if($save !== null){
			die($save);
		}
	}
	$sitesArray = array();
	$query = mysqli_query($dbconn, "SELECT `ID`, `Name`, `Latitude`, `Longitude`, `Description` FROM `Site`");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["ID"])] = array(
			"ID" => intval($row["ID"]),
			"Name" => $row["Name"],
			"Coordinates" => $row["Latitude()"] . "," . $row["Longitude"],
			"Description" => $row["Description"],
		);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Plant.ID=Survey.PlantFK GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["SurveyCount"] = intval($row["SurveyCount"]);
	}
	
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(*) AS FilteredSurveyCount FROM Survey JOIN Plant ON Plant.ID=Survey.PlantFK WHERE MONTH(Survey.LocalDate)>=$monthStart AND MONTH(Survey.LocalDate)<=$monthEnd AND YEAR(Survey.LocalDate)>=$yearStart AND YEAR(Survey.LocalDate)<=$yearEnd AND (Plant.Species LIKE '$plantSpecies' OR (Plant.Species='N/A' AND Survey.PlantSpecies LIKE '$plantSpecies')) AND Survey.WetLeaves IN (0, $includeWetLeaves) AND Survey.ObservationMethod LIKE '$observationMethod' GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["FilteredSurveyCount"] = intval($row["FilteredSurveyCount"]);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT Survey.UserFKOfObserver) AS UserCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["UserCount"] = intval($row["UserCount"]);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT ArthropodSighting.Group) AS ArthropodGroupCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID JOIN ArthropodSighting ON Survey.ID=ArthropodSighting.SurveyFK GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["ArthropodGroupCount"] = intval($row["ArthropodGroupCount"]);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID JOIN ArthropodSighting ON Survey.ID=ArthropodSighting.SurveyFK GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["ArthropodCount"] = intval($row["ArthropodCount"]);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, SUM(ArthropodSighting.Quantity) AS CaterpillarCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID JOIN ArthropodSighting ON Survey.ID=ArthropodSighting.SurveyFK WHERE ArthropodSighting.Group='caterpillar' GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["CaterpillarCount"] = intval($row["CaterpillarCount"]);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS Caterpillars FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE ArthropodSighting.Group='caterpillar' GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["Caterpillars"] = round(((floatval($row["Caterpillars"]) / floatval($sitesArray[strval($row["SiteFK"])]["SurveyCount"])) * 100), 2) . "%";
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, MAX(STR_TO_DATE(CONCAT(Survey.LocalDate, ' ', Survey.LocalTime), '%Y-%m-%d %T')) AS MostRecentDateTime FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$sitesArray[strval($row["SiteFK"])]["MostRecentDateTime"] = $row["MostRecentDateTime"];
	}
	
	$minLoggedDensity = 9999;
	if($occurrenceInsteadOfDensity){
		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS Arthropods FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE MONTH(Survey.LocalDate)>=$monthStart AND MONTH(Survey.LocalDate)<=$monthEnd AND YEAR(Survey.LocalDate)>=$yearStart AND YEAR(Survey.LocalDate)<=$yearEnd AND ArthropodSighting.Group LIKE '$arthropod' AND (Plant.Species LIKE '$plantSpecies' OR (Plant.Species='N/A' AND Survey.PlantSpecies LIKE '$plantSpecies')) AND Survey.WetLeaves IN (0, $includeWetLeaves) AND ArthropodSighting.Length>=$minSize AND Survey.ObservationMethod LIKE '$observationMethod' GROUP BY Plant.SiteFK");
		while($row = mysqli_fetch_assoc($query)){
			$sitesArray[strval($row["SiteFK"])]["RawValue"] = round(((floatval($row["Arthropods"]) / floatval($sitesArray[strval($row["SiteFK"])]["SurveyCount"])) * 100), 2) . "%";
			$sitesArray[strval($row["SiteFK"])]["Weight"] = round(((floatval($row["Arthropods"]) / floatval($sitesArray[strval($row["SiteFK"])]["SurveyCount"])) * 100), 2);
		}
	}
	else{
		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, SUM(ArthropodSighting.Quantity) AS Arthropods FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID JOIN ArthropodSighting ON Survey.ID=ArthropodSighting.SurveyFK WHERE MONTH(Survey.LocalDate)>=$monthStart AND MONTH(Survey.LocalDate)<=$monthEnd AND YEAR(Survey.LocalDate)>=$yearStart AND YEAR(Survey.LocalDate)<=$yearEnd AND ArthropodSighting.Group LIKE '$arthropod' AND (Plant.Species LIKE '$plantSpecies' OR (Plant.Species='N/A' AND Survey.PlantSpecies LIKE '$plantSpecies')) AND Survey.WetLeaves IN (0, $includeWetLeaves) AND ArthropodSighting.Length>=$minSize AND Survey.ObservationMethod LIKE '$observationMethod' GROUP BY Plant.SiteFK");
		while($row = mysqli_fetch_assoc($query)){
			$sitesArray[strval($row["SiteFK"])]["RawValue"] = round(((floatval($row["Arthropods"]) / floatval($sitesArray[strval($row["SiteFK"])]["SurveyCount"])) * 1), 2);
			$loggedDensity = round(log10(((floatval($row["Arthropods"]) / floatval($sitesArray[strval($row["SiteFK"])]["SurveyCount"])) + 0.000000000000000000000000000000000000000000000000001)), 2);
			$sitesArray[strval($row["SiteFK"])]["Weight"] = $loggedDensity;
			if($loggedDensity < $minLoggedDensity || $minLoggedDensity == 9999){
				$minLoggedDensity = $loggedDensity;
			}
		}
	}
	mysqli_close($dbconn);

	for($i = 0; $i < count($sites); $i++){
		if(!array_key_exists("SurveyCount", $sitesArray[strval($sites[$i]->getID())])){
			$sitesArray[strval($sites[$i]->getID())]["SurveyCount"] = 0;
		}
		if(!array_key_exists("FilteredSurveyCount", $sitesArray[strval($sites[$i]->getID())])){
			$sitesArray[strval($sites[$i]->getID())]["FilteredSurveyCount"] = 0;
		}
		if(!array_key_exists("UserCount", $sitesArray[strval($sites[$i]->getID())])){
			$sitesArray[strval($sites[$i]->getID())]["UserCount"] = 0;
		}
		if(!array_key_exists("ArthropodGroupCount", $sitesArray[strval($sites[$i]->getID())])){
			$sitesArray[strval($sites[$i]->getID())]["ArthropodGroupCount"] = 0;
		}
		if(!array_key_exists("ArthropodCount", $sitesArray[strval($sites[$i]->getID())])){
			$sitesArray[strval($sites[$i]->getID())]["ArthropodCount"] = 0;
		}
		if(!array_key_exists("CaterpillarCount", $sitesArray[strval($sites[$i]->getID())])){
			$sitesArray[strval($sites[$i]->getID())]["CaterpillarCount"] = 0;
		}
		if(!array_key_exists("Caterpillars", $sitesArray[strval($sites[$i]->getID())])){
			$sitesArray[strval($sites[$i]->getID())]["Caterpillars"] = "0%";
		}
		if(!array_key_exists("MostRecentDateTime", $sitesArray[strval($sites[$i]->getID())])){
			$sitesArray[strval($sites[$i]->getID())]["MostRecentDateTime"] = "Never";
		}
		if(!array_key_exists("RawValue", $sitesArray[strval($sites[$i]->getID())])){
			if($sitesArray[strval($sites[$i]->getID())]["MostRecentDateTime"] == "Never"){
				$sitesArray[strval($sites[$i]->getID())]["RawValue"] = "No Surveys";
			}
			else if($occurrenceInsteadOfDensity){
				$sitesArray[strval($sites[$i]->getID())]["RawValue"] = "0%";
			}
			else{
				$sitesArray[strval($sites[$i]->getID())]["RawValue"] = 0;
			}
		}
		if(!array_key_exists("Weight", $sitesArray[strval($sites[$i]->getID())])){
			if($occurrenceInsteadOfDensity){
				$sitesArray[strval($sites[$i]->getID())]["Weight"] = 0;
			}
			else{
				$sitesArray[strval($sites[$i]->getID())]["Weight"] = $minLoggedDensity * .99;
			}
		}
	}
	$result = json_encode(array_values($sitesArray));
	if($HIGH_TRAFFIC_MODE){
		save($baseFileName, $result);
	}
	die($result);
?>
