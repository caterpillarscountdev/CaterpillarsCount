<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$data = array();
	$sites = Site::findAll();
	$query = mysqli_query($dbconn, "SELECT WEEK(\"" . substr($mostRecentSurveyDate, 0, 4) . "-01-01\") AS StartWeek, WEEK(\"" . substr($mostRecentSurveyDate, 0, 4) . "-12-31\") AS EndWeek");
	$row = mysqli_fetch_assoc($query);
	$startWeek = intval($row["StartWeek"]);
	$endWeek = intval($row["EndWeek"]);
	$query = mysqli_query($dbconn, "SELECT YEAR(MIN(Survey.LocalDate)) AS FirstSurveyYear, YEAR(MAX(Survey.LocalDate)) AS LastSurveyYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK<>'2'");
	$row = mysqli_fetch_assoc($query);
	$firstSurveyYear = $row["FirstSurveyYear"];
	$lastSurveyYear = $row["LastSurveyYear"];
	for($i = 0; $i < count($sites); $i++){
		if($sites[$i]->getID() != 2){
			$surveysEachWeek = array();
			for($i = $startWeek; $i <= $endWeek; $i++){
				$surveysEachWeek[] = 0;
			}
			$query = mysqli_query($dbconn, "SELECT WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . $sites[$i]->getID() . "' AND YEAR(Survey.LocalDate)='$lastSurveyYear' GROUP BY WEEK(Survey.LocalDate, 1)");
			while($row = mysqli_fetch_assoc($query)){
				$surveysEachWeek[intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
			}

			$data[(string)$sites[$i]->getID()] = array(
				"name" => $sites[$i]->getName(),
				"url" => $sites[$i]->getURL(),
				"authorities" => array($sites[$i]->getCreator()->getFullName(), $sites[$i]->getCreator()->getEmail()),
				"surveysEachWeek" => $surveysEachWeek
			);
		}
	}
	
	//Email of managers
	$query = mysqli_query($dbconn, "SELECT CONCAT(User.FirstName, " ", User.LastName) AS FullName, User.Email, ManagerRequest.SiteFK FROM ManagerRequest JOIN User ON ManagerRequest.UserFKOfManager=User.ID WHERE ManagerRequest.Status='Approved'");
	while($row = mysqli_fetch_assoc($query)){
		if(array_key_exists($row["SiteFK"], $data)){
			$data[$row["SiteFK"]]["authorities"][] = array($row["FullName"], $row["Email"]);
		}
	}
	
	//Year of site creation
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, YEAR(MIN(LocalDate)) AS FirstYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		if(array_key_exists($row["SiteFK"], $data)){
			$data[$row["SiteFK"]]["firstSurveyYear"] = $row["FirstYear"];
		}
	}
	
	//Number of survey locations at the site
	$query = mysqli_query($dbconn, "SELECT SiteFK, COUNT(*) AS PlantCount FROM Plant WHERE Circle>'0' GROUP BY SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		if(array_key_exists($row["SiteFK"], $data)){
			$data[$row["SiteFK"]]["plantCount"] = $row["PlantCount"];
		}
	}
	
	//Number of unique users
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT Survey.UserFKOfObserver) AS ObserverCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		if(array_key_exists($row["SiteFK"], $data)){
			$data[$row["SiteFK"]]["observerCount"] = $row["ObserverCount"];
		}
	}
	
	//Most recent survey date
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, MAX(LocalDate) AS MostRecentSurveyDate FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID GROUP BY Plant.SiteFK");
	while($row = mysqli_fetch_assoc($query)){
		if(array_key_exists($row["SiteFK"], $data)){
			$data[$row["SiteFK"]]["mostRecentSurveyDate"] = $row["MostRecentSurveyDate"];
		}
	}

	die("true|" . json_encode(array($firstSurveyYear, $lastSurveyYear, $siteArray)));
?>
