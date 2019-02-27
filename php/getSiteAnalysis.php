<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');

	$start = intval($_GET["start"]);
	$LIMIT = 50;
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$data = array();
	$sites = Site::findAll($start, $LIMIT);
	$query = mysqli_query($dbconn, "SELECT YEAR(MIN(Survey.LocalDate)) AS FirstSurveyYear, YEAR(MAX(Survey.LocalDate)) AS LastSurveyYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK<>'2'");
	$row = mysqli_fetch_assoc($query);
	$firstSurveyYear = $row["FirstSurveyYear"];
	$lastSurveyYear = $row["LastSurveyYear"];
	$query = mysqli_query($dbconn, "SELECT WEEK(\"" . $lastSurveyYear . "-01-01\") AS StartWeek, WEEK(\"" . $lastSurveyYear . "-12-31\") AS EndWeek");
	$row = mysqli_fetch_assoc($query);
	$startWeek = intval($row["StartWeek"]);
	$endWeek = intval($row["EndWeek"]);
	$siteIDs = array();
	$lastCall = ($start + $LIMIT) >= intval(mysqli_fetch_assoc(mysqli_query($dbconn, "SELECT COUNT(*) AS Count FROM Site"))["Count"]);
	for($i = 0; $i < count($sites); $i++){
		if($sites[$i]->getID() != 2){
			$surveysEachWeek = array();
			for($j = $startWeek; $j <= $endWeek; $j++){
				$surveysEachWeek[] = 0;
			}
			$query = mysqli_query($dbconn, "SELECT WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . $sites[$i]->getID() . "' AND YEAR(Survey.LocalDate)='$lastSurveyYear' GROUP BY WEEK(Survey.LocalDate, 1)");
			if(mysqli_num_rows($query) > 0){
				while($row = mysqli_fetch_assoc($query)){
					$surveysEachWeek[intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
				}
			}
			
			$siteIDs[] = $sites[$i]->getID();

			$data[(string)$sites[$i]->getID()] = array(
				"name" => $sites[$i]->getName(),
				"url" => $sites[$i]->getURL(),
				"authorities" => array($sites[$i]->getCreator()->getFullName(), $sites[$i]->getCreator()->getEmail()),
				"surveysEachWeek" => $surveysEachWeek,
				"firstSurveyYear" => "N/A",
				"plantCount" => 0,
				"observerCount" => 0,
				"mostRecentSurveyDate" => "N/A"
			);
		}
		else if(count($sites) == 1){
			die("true|" . json_encode(array($firstSurveyYear, $lastSurveyYear, $data)));
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
	
	//Year of site creation
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, YEAR(MIN(LocalDate)) AS FirstYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (" . implode(", ", $siteIDs) . ") GROUP BY Plant.SiteFK");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			if(array_key_exists($row["SiteFK"], $data)){
				$data[$row["SiteFK"]]["firstSurveyYear"] = $row["FirstYear"];
			}
		}
	}
	
	//Number of survey locations at the site
	$query = mysqli_query($dbconn, "SELECT SiteFK, COUNT(*) AS PlantCount FROM Plant WHERE Circle>'0' AND SiteFK IN (" . implode(", ", $siteIDs) . ") GROUP BY SiteFK");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			if(array_key_exists($row["SiteFK"], $data)){
				$data[$row["SiteFK"]]["plantCount"] = $row["PlantCount"];
			}
		}
	}
	
	//Number of unique users
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT Survey.UserFKOfObserver) AS ObserverCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (" . implode(", ", $siteIDs) . ") GROUP BY Plant.SiteFK");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			if(array_key_exists($row["SiteFK"], $data)){
				$data[$row["SiteFK"]]["observerCount"] = $row["ObserverCount"];
			}
		}
	}
	
	//Most recent survey date
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, MAX(LocalDate) AS MostRecentSurveyDate FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (" . implode(", ", $siteIDs) . ") GROUP BY Plant.SiteFK");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			if(array_key_exists($row["SiteFK"], $data)){
				$data[$row["SiteFK"]]["mostRecentSurveyDate"] = $row["MostRecentSurveyDate"];
			}
		}
	}

	die("true|" . json_encode(array($firstSurveyYear, $lastSurveyYear, $data, $lastCall)));
?>
