<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');

	$start = intval($_GET["start"]);
	$LIMIT = 500;
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$siteCount = intval(mysqli_fetch_assoc(mysqli_query($dbconn, "SELECT COUNT(*) AS Count FROM Site"))["Count"]);
	if($start >= $siteCount){
		die("false|Number of sites exceeded.");
	}

	$data = array();
	$query = mysqli_query($dbconn, "SELECT YEAR(MIN(Survey.LocalDate)) AS FirstSurveyYear, YEAR(MAX(Survey.LocalDate)) AS LastSurveyYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK<>'2'");
	$row = mysqli_fetch_assoc($query);
	$firstSurveyYear = $row["FirstSurveyYear"];
	$lastSurveyYear = $row["LastSurveyYear"];
	$query = mysqli_query($dbconn, "SELECT WEEK(\"" . $lastSurveyYear . "-01-01\") AS StartWeek, WEEK(\"" . $lastSurveyYear . "-12-31\") AS EndWeek");
	$row = mysqli_fetch_assoc($query);
	$startWeek = intval($row["StartWeek"]);
	$endWeek = intval($row["EndWeek"]);
	$siteIDs = array(0);
	$lastCall = ($start + $LIMIT) >= $siteCount;

	$sitesQuery = mysqli_query($dbconn, "SELECT Site.ID AS SiteID, Site.Name AS SiteName, Site.Active AS Active, Site.URL AS SiteURL, CONCAT(User.FirstName, ' ', User.LastName) AS CreatorFullName, User.Email AS CreatorEmail FROM `Site` JOIN User ON Site.UserFKOfCreator=User.ID LIMIT $start, $LIMIT");
	if(mysqli_num_rows($sitesQuery) > 0){
		while($siteRow = mysqli_fetch_assoc($sitesQuery)){
			if(intval($siteRow["SiteID"]) != 2){
				$surveysEachWeek = array();
				for($j = $startWeek; $j <= $endWeek; $j++){
					$surveysEachWeek[] = 0;
				}
				/*
				$query = mysqli_query($dbconn, "SELECT WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . intval($siteRow["SiteID"]) . "' AND YEAR(Survey.LocalDate)='$lastSurveyYear' GROUP BY WEEK(Survey.LocalDate, 1)");
				if(mysqli_num_rows($query) > 0){
					while($row = mysqli_fetch_assoc($query)){
						$surveysEachWeek[intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
					}
				}
				*/

				$siteIDs[] = intval($siteRow["SiteID"]);

				$data[(string)$siteRow["SiteID"]] = array(
					"name" => $siteRow["SiteName"],
					"url" => $siteRow["SiteURL"],
					"authorities" => array(array($siteRow["CreatorFullName"], $siteRow["CreatorEmail"])),
					"surveysEachWeek" => $surveysEachWeek,
					"firstSurveyYear" => "N/A",
					"plantCount" => 0,
					"observerCount" => 0,
					"mostRecentSurveyDate" => "N/A",
					"active" => filter_var($siteRow["Active"], FILTER_VALIDATE_BOOLEAN)
				);
			}
			else if(mysqli_num_rows($sitesQuery) == 1){
				die("true|" . json_encode(array($firstSurveyYear, $lastSurveyYear, $data, true)));
			}
		}
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='$lastSurveyYear' AND Plant.SiteFK IN (" . implode(", ", $siteIDs) . ") GROUP BY CONCAT(Plant.SiteFK, ' ', WEEK(Survey.LocalDate, 1))");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			$data[(string)$row["SiteFK"]]["surveysEachWeek"][intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
		}
	}
	
	//Email of managers
	$query = mysqli_query($dbconn, "SELECT CONCAT(User.FirstName, ' ', User.LastName) AS FullName, User.Email, ManagerRequest.SiteFK FROM ManagerRequest JOIN User ON ManagerRequest.UserFKOfManager=User.ID WHERE ManagerRequest.Status='Approved' AND ManagerRequest.SiteFK IN (" . implode(", ", $siteIDs) . ")");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			if(array_key_exists($row["SiteFK"], $data)){
				$data[$row["SiteFK"]]["authorities"][] = array($row["FullName"], $row["Email"]);
			}
		}
	}
	
	//Number of survey locations at the site, number of unique users, and year of site creation
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, MAX(LocalDate) AS MostRecentSurveyDate, COUNT(DISTINCT Plant.ID) AS PlantCount, COUNT(DISTINCT Survey.UserFKOfObserver) AS ObserverCount, YEAR(MIN(LocalDate)) AS FirstYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (" . implode(", ", $siteIDs) . ") GROUP BY Plant.SiteFK");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			if(array_key_exists($row["SiteFK"], $data)){
				$data[$row["SiteFK"]]["firstSurveyYear"] = $row["FirstYear"];
				$data[$row["SiteFK"]]["observerCount"] = $row["ObserverCount"];
				$data[$row["SiteFK"]]["plantCount"] = $row["PlantCount"];
				$data[$row["SiteFK"]]["mostRecentSurveyDate"] = $row["MostRecentSurveyDate"];
			}
		}
	}

	die("true|" . json_encode(array($firstSurveyYear, $lastSurveyYear, $data, $lastCall)));
?>
