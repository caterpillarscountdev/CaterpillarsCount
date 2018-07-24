<?php
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');
	$siteID = intval($_GET["siteID"]);
	$siteRestriction = "<>2";
	if($siteID > 1){$siteRestriction = "=" . $siteID;}
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT User.ID, CONCAT(User.FirstName, ' ', User.LastName) AS FullName, User.HiddenFromLeaderboards, SUM(CASE WHEN Survey.LocalDate >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) THEN 1 ELSE 0 END) AS Week, SUM(CASE WHEN Survey.LocalDate >= STR_TO_DATE(CONCAT(DATE_FORMAT(CURDATE(),'%Y-%m'), '-01 00:00:00'), '%Y-%m-%d %T') THEN 1 ELSE 0 END) AS Month, SUM(CASE WHEN Survey.LocalDate >= STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01 00:00:00'), '%Y-%m-%d %T') THEN 1 ELSE 0 END) AS Year, Count(*) AS Total, COUNT(DISTINCT Survey.LocalDate) AS TotalUniqueDates FROM `Survey` JOIN User ON Survey.UserFKOfObserver=User.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK" . $siteRestriction . " GROUP BY User.ID ORDER BY Year DESC");
	
	$rankingsArray = array();
  	$i = 1;
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["ID"])] = array(
			"UserID" => $row["ID"],
      			"Name" => $row["FullName"],
      			"HiddenFromLeaderboards" => $row["HiddenFromLeaderboards"],
      			"Week" => intval($row["Week"]),
			"UniqueDatesThisWeek" => 0,
      			"Month" => intval($row["Month"]),
			"UniqueDatesThisMonth" => 0,
      			"Year" => intval($row["Year"]),
			"UniqueDatesThisYear" => 0,
      			"Total" => intval($row["Total"]),
      			"TotalUniqueDates" => intval($row["TotalUniqueDates"]),
    		);
	}
	
	$query = mysqli_query($dbconn, "SELECT UserFKOfObserver, COUNT(DISTINCT LocalDate) AS UniqueDatesThisWeek FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK" . $siteRestriction . " AND Survey.LocalDate >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) GROUP BY UserFKOfObserver");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["UserFKOfObserver"])]["UniqueDatesThisWeek"] = intval($row["UniqueDatesThisWeek"]);
	}
	
	$query = mysqli_query($dbconn, "SELECT UserFKOfObserver, COUNT(DISTINCT LocalDate) AS UniqueDatesThisMonth FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK" . $siteRestriction . " AND Survey.LocalDate >= STR_TO_DATE(CONCAT(DATE_FORMAT(CURDATE(),'%Y-%m'), '-01 00:00:00'), '%Y-%m-%d %T') GROUP BY UserFKOfObserver");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["UserFKOfObserver"])]["UniqueDatesThisMonth"] = intval($row["UniqueDatesThisMonth"]);
	}

	$query = mysqli_query($dbconn, "SELECT UserFKOfObserver, COUNT(DISTINCT LocalDate) AS UniqueDatesThisYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK" . $siteRestriction . " AND Survey.LocalDate >= STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01 00:00:00'), '%Y-%m-%d %T') GROUP BY UserFKOfObserver");
	while($row = mysqli_fetch_assoc($query)){
		$rankingsArray[strval($row["UserFKOfObserver"])]["UniqueDatesThisYear"] = intval($row["UniqueDatesThisYear"]);
	}
	mysqli_close($dbconn);
	
	$allUsers = User::findAll();
	for($j = 0; $j < count($allUsers); $j++){
		if(is_object($allUsers[$j]) && get_class($allUsers[$j]) == "User" && !array_key_exists(strval($allUsers[$j]->getID()), $rankingsArray)){
			$rankingsArray[strval($allUsers[$j]->getID())] = array(
				"UserID" => $allUsers[$j]->getID(),
				"Name" => $allUsers[$j]->getFullName(),
      				"HiddenFromLeaderboards" => $allUsers[$j]->getHiddenFromLeaderboards(),
				"Week" => 0,
				"UniqueDatesThisWeek" => 0,
				"Month" => 0,
				"UniqueDatesThisMonth" => 0,
				"Year" => 0,
				"UniqueDatesThisYear" => 0,
				"Total" => 0,
      				"TotalUniqueDates" => 0,
			);
		}
	}

	die(json_encode(array_values($rankingsArray)));
	
?>