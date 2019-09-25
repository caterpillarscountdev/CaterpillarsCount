<?php
	require_once('orm/resources/Keychain.php');
	require_once('resultMemory.php');

	$forceSave = false;
	if(isset($_GET["forceSave"]) && !empty($_GET["forceSave"])){
		$forceSave = filter_var($_GET["forceSave"], FILTER_VALIDATE_BOOLEAN);
	}

	$HIGH_TRAFFIC_MODE = true;
	$SAVE_TIME_LIMIT = 11 * 60;
	
	$MIN_SURVEY_REQUIREMENT = 10;
	
	$baseFileName = basename(__FILE__, '.php');
	if($HIGH_TRAFFIC_MODE && !$forceSave){
		$save = getSaveFromDatabase(basename(__FILE__, '.php'), $SAVE_TIME_LIMIT);
		if($save !== null){
			die($save);
		}
	}
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT Site.ID, Site.Name, Site.Region, Site.Latitude, Site.Longitude, Site.OpenToPublic, SUM(CASE WHEN Survey.LocalDate >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) THEN 1 ELSE 0 END) AS Week, SUM(CASE WHEN Survey.LocalDate >= STR_TO_DATE(CONCAT(DATE_FORMAT(CURDATE(),'%Y-%m'), '-01 00:00:00'), '%Y-%m-%d %T') THEN 1 ELSE 0 END) AS Month, SUM(CASE WHEN Survey.LocalDate >= STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01 00:00:00'), '%Y-%m-%d %T') THEN 1 ELSE 0 END) AS Year, Count(*) AS Total, COUNT(DISTINCT Survey.LocalDate) AS TotalUniqueDates FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK=Site.ID WHERE Site.ID<>2 GROUP BY Site.ID ORDER BY Year DESC");
		
	$rankingsArray = array();
  	$i = 1;
	while($row = mysqli_fetch_assoc($query)){
		$total = intval($row["Total"]);
		if($total >= $MIN_SURVEY_REQUIREMENT){
			$openToPublic = $row["OpenToPublic"];
			$rankingsArray[strval($row["ID"])] = array(
				"ID" => $row["ID"],
				"Name" => $row["Name"] . " (" . $row["Region"] . ")",
				"Coordinates" => $row["Latitude"] . "," . $row["Longitude"],
				"Week" => intval($row["Week"]),
				"UniqueDatesThisWeek" => 0,
				"Month" => intval($row["Month"]),
				"UniqueDatesThisMonth" => 0,
				"Year" => intval($row["Year"]),
				"UniqueDatesThisYear" => 0,
				"Total" => $total,
				"TotalUniqueDates" => intval($row["TotalUniqueDates"]),
				"Caterpillars" => "0%",
			);
		}
	}
	
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT LocalDate) AS UniqueDatesThisWeek FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (0, " . implode(", ", array_keys($rankingsArray)) . ") AND Plant.SiteFK<>2 AND Survey.LocalDate >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["SiteFK"])]["UniqueDatesThisWeek"] = intval($row["UniqueDatesThisWeek"]);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT LocalDate) AS UniqueDatesThisMonth FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (0, " . implode(", ", array_keys($rankingsArray)) . ") AND Plant.SiteFK<>2 AND Survey.LocalDate >= STR_TO_DATE(CONCAT(DATE_FORMAT(CURDATE(),'%Y-%m'), '-01 00:00:00'), '%Y-%m-%d %T') GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["SiteFK"])]["UniqueDatesThisMonth"] = intval($row["UniqueDatesThisMonth"]);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT LocalDate) AS UniqueDatesThisYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (0, " . implode(", ", array_keys($rankingsArray)) . ") AND Plant.SiteFK<>2 AND Survey.LocalDate >= STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01 00:00:00'), '%Y-%m-%d %T') GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["SiteFK"])]["UniqueDatesThisYear"] = intval($row["UniqueDatesThisYear"]);
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS Caterpillars FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (0, " . implode(", ", array_keys($rankingsArray)) . ") AND Plant.SiteFK<>2 AND ArthropodSighting.Group='caterpillar' GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["SiteFK"])]["Caterpillars"] = round(((floatval($row["Caterpillars"]) / floatval($rankingsArray[strval($row["SiteFK"])]["Total"])) * 100), 2) . "%";
	}
	
	if($MIN_SURVEY_REQUIREMENT == 0){
		$query = mysqli_query($dbconn, "SELECT `ID`, `OpenToPublic`, `Latitude`, `Longitude`, `Name`, `Region` FROM `Site`");
		while($row = mysqli_fetch_assoc($query)){
			if(intval($row["ID"]) != 2 && !array_key_exists(strval($row["ID"]), $rankingsArray)){
				$coordinates = "NONE";
				if($row["OpenToPublic"]){
					$coordinates = $row["Latitude"] . "," . $row["Longitude"];
				}
				$rankingsArray[strval($row["ID"])] = array(
					"ID" => $row["ID"],
					"Name" => $row["Name"] . " (" . $row["Region"] . ")",
					"Coordinates" => $coordinates,
					"Week" => 0,
					"UniqueDatesThisWeek" => 0,
					"Month" => 0,
					"UniqueDatesThisMonth" => 0,
					"Year" => 0,
					"UniqueDatesThisYear" => 0,
					"Total" => 0,
					"TotalUniqueDates" => 0,
					"Caterpillars" => "0%",
				);
			}
		}
	}
	mysqli_close($dbconn);
	
	$result = json_encode(array_values($rankingsArray));
	if($HIGH_TRAFFIC_MODE){
		saveToDatabase($baseFileName, $result);
	}
	die($result);
	
?>
