<?php
	require_once('orm/resources/Keychain.php');
	require_once('orm/User.php');
	require_once('resultMemory.php');
	
	$siteID = intval($_GET["siteID"]);
	$cron = true;
	if(isset($_GET["cron"]) && !empty($_GET["cron"])){
		$cron = filter_var($_GET["cron"], FILTER_VALIDATE_BOOLEAN);
	}

	$HIGH_TRAFFIC_MODE = true;
	$SAVE_TIME_LIMIT = 15 * 60;
	
	$MIN_SURVEY_REQUIREMENT = 10;//only for the global leaderboard- doesn't apply to the site-specific leaderboard
	
	$baseFileName = basename(__FILE__, '.php') . $siteID;
	if($HIGH_TRAFFIC_MODE && !$cron){
		$save = getSaveFromDatabase($baseFileName, $SAVE_TIME_LIMIT);
		if($save !== null){
			die($save);
		}
	}

	$siteRestriction = "<>2";
	if($siteID > 0){
		$siteRestriction = "=" . $siteID;
	}
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT User.ID, CONCAT(User.FirstName, ' ', User.LastName) AS FullName, User.Hidden, SUM(CASE WHEN Survey.LocalDate >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) THEN 1 ELSE 0 END) AS Week, SUM(CASE WHEN Survey.LocalDate >= STR_TO_DATE(CONCAT(DATE_FORMAT(CURDATE(),'%Y-%m'), '-01 00:00:00'), '%Y-%m-%d %T') THEN 1 ELSE 0 END) AS Month, SUM(CASE WHEN Survey.LocalDate >= STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01 00:00:00'), '%Y-%m-%d %T') THEN 1 ELSE 0 END) AS Year, Count(*) AS Total, COUNT(DISTINCT Survey.LocalDate) AS TotalUniqueDates FROM `Survey` JOIN User ON Survey.UserFKOfObserver=User.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK" . $siteRestriction . " GROUP BY User.ID");
	
	$rankingsArray = array();
  	$i = 1;
	while($row = mysqli_fetch_assoc($query)){
		$total = intval($row["Total"]);
		if($siteID > 0 || $total >= $MIN_SURVEY_REQUIREMENT){
			$name = $row["FullName"];
			if(filter_var($row["Hidden"], FILTER_VALIDATE_BOOLEAN)){
				$name = "(anonymous user)";
			}
			$rankingsArray[strval($row["ID"])] = array(
				"ID" => $row["ID"],
				"Name" => $name,
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
	
	$query = mysqli_query($dbconn, "SELECT UserFKOfObserver, COUNT(DISTINCT LocalDate) AS UniqueDatesThisWeek FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE UserFKOfObserver IN (0, " . implode(", ", array_keys($rankingsArray)) . ") AND Plant.SiteFK" . $siteRestriction . " AND Survey.LocalDate >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) GROUP BY UserFKOfObserver");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["UserFKOfObserver"])]["UniqueDatesThisWeek"] = intval($row["UniqueDatesThisWeek"]);
	}
	
	$query = mysqli_query($dbconn, "SELECT UserFKOfObserver, COUNT(DISTINCT LocalDate) AS UniqueDatesThisMonth FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE UserFKOfObserver IN (0, " . implode(", ", array_keys($rankingsArray)) . ") AND Plant.SiteFK" . $siteRestriction . " AND Survey.LocalDate >= STR_TO_DATE(CONCAT(DATE_FORMAT(CURDATE(),'%Y-%m'), '-01 00:00:00'), '%Y-%m-%d %T') GROUP BY UserFKOfObserver");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["UserFKOfObserver"])]["UniqueDatesThisMonth"] = intval($row["UniqueDatesThisMonth"]);
	}

	$query = mysqli_query($dbconn, "SELECT UserFKOfObserver, COUNT(DISTINCT LocalDate) AS UniqueDatesThisYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE UserFKOfObserver IN (0, " . implode(", ", array_keys($rankingsArray)) . ") AND Plant.SiteFK" . $siteRestriction . " AND Survey.LocalDate >= STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01 00:00:00'), '%Y-%m-%d %T') GROUP BY UserFKOfObserver");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["UserFKOfObserver"])]["UniqueDatesThisYear"] = intval($row["UniqueDatesThisYear"]);
	}

	$query = mysqli_query($dbconn, "SELECT Survey.UserFKOfObserver, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS Caterpillars FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE UserFKOfObserver IN (0, " . implode(", ", array_keys($rankingsArray)) . ") AND Plant.SiteFK" . $siteRestriction . " AND ArthropodSighting.UpdatedGroup='caterpillar' GROUP BY Survey.UserFKOfObserver");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["UserFKOfObserver"])]["Caterpillars"] = round(((floatval($row["Caterpillars"]) / floatval($rankingsArray[strval($row["UserFKOfObserver"])]["Total"])) * 100), 2) . "%";
	}
	mysqli_close($dbconn);
	
	if($siteID <= 0 && $MIN_SURVEY_REQUIREMENT == 0){
		$allUsers = User::findAll();
		for($j = 0; $j < count($allUsers); $j++){
			if(is_object($allUsers[$j]) && get_class($allUsers[$j]) == "User" && !array_key_exists(strval($allUsers[$j]->getID()), $rankingsArray)){
				$name = $allUsers[$j]->getFullName();
				if($allUsers[$j]->getHidden()){
					$name = "(anonymous user)";
				}
				$rankingsArray[strval($allUsers[$j]->getID())] = array(
					"ID" => $allUsers[$j]->getID(),
					"Name" => $name,
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
	
	$result = "true|" . json_encode(array_values($rankingsArray));
	if($HIGH_TRAFFIC_MODE){
		saveToDatabase($baseFileName, $result);
	}
	die($result);
?>
