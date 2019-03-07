<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/User.php');
	require_once('orm/Site.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$start = intval($_GET["start"]);
	$LIMIT = 500;
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$query = mysqli_query($dbconn, "SELECT `SiteFK` FROM `ManagerRequest` WHERE `UserFKOfManager`='" . $user->getID() . "' AND `Status`='Approved'");
		$siteIDs = array(0);
		while($row = mysqli_fetch_assoc($query)){
			$siteIDs[] = intval($row["SiteFK"]);
		}
		$query = mysqli_query($dbconn, "SELECT * FROM `Site` WHERE `UserFKOfCreator`='" . $user->getID() . "'");
		while($row = mysqli_fetch_assoc($query)){
			$siteID = intval($row["ID"]);
			if(!in_array($siteID, $siteIDs)){
				$siteIDs[] = $siteID;
			}
		}
		
		$userRestriction = " WHERE Site.ID IN (" . implode(", ", $siteIDs) . ")";
		if(User::isSuperUser($user)){
			$userRestriction = "";
		}
		$siteCount = intval(mysqli_fetch_assoc(mysqli_query($dbconn, "SELECT COUNT(*) AS Count FROM Site" . $userRestriction))["Count"]);
		
		$data = array();
		$query = mysqli_query($dbconn, "SELECT YEAR(MIN(Survey.LocalDate)) AS FirstSurveyYear, YEAR(MAX(Survey.LocalDate)) AS LastSurveyYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK<>'2'");
		$row = mysqli_fetch_assoc($query);
		$firstSurveyYear = $row["FirstSurveyYear"];
		$lastSurveyYear = $row["LastSurveyYear"];
		$query = mysqli_query($dbconn, "SELECT WEEK(\"" . $lastSurveyYear . "-01-01\") AS StartWeek, WEEK(\"" . $lastSurveyYear . "-12-31\") AS EndWeek");
		$row = mysqli_fetch_assoc($query);
		$startWeek = intval($row["StartWeek"]);
		$endWeek = intval($row["EndWeek"]);
		$siteIDsThisIteration = array(0);
		$lastCall = ($start + $LIMIT) >= $siteCount;
		
		if($start >= $siteCount){
			die("false|Number of sites exceeded.");
			die("true|" . json_encode(array($firstSurveyYear, $lastSurveyYear, $data, true)));
		}

		$sitesQuery = mysqli_query($dbconn, "SELECT Site.ID AS SiteID, Site.Name AS SiteName, Site.Active AS Active, Site.URL AS SiteURL, CONCAT(User.FirstName, ' ', User.LastName) AS CreatorFullName, User.Email AS CreatorEmail FROM `Site` JOIN User ON Site.UserFKOfCreator=User.ID" . $userRestriction . " LIMIT $start, $LIMIT");
		if(mysqli_num_rows($sitesQuery) > 0){
			while($siteRow = mysqli_fetch_assoc($sitesQuery)){
				if(intval($siteRow["SiteID"]) != 2){
					$surveysEachWeek = array();
					for($j = $startWeek; $j <= $endWeek; $j++){
						$surveysEachWeek[] = 0;
					}

					$siteIDsThisIteration[] = intval($siteRow["SiteID"]);

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

		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='$lastSurveyYear' AND Plant.SiteFK IN (" . implode(", ", $siteIDsThisIteration) . ") GROUP BY CONCAT(Plant.SiteFK, ' ', WEEK(Survey.LocalDate, 1))");
		if(mysqli_num_rows($query) > 0){
			while($row = mysqli_fetch_assoc($query)){
				$data[(string)$row["SiteFK"]]["surveysEachWeek"][intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
			}
		}

		//Email of managers
		$query = mysqli_query($dbconn, "SELECT CONCAT(User.FirstName, ' ', User.LastName) AS FullName, User.Email, ManagerRequest.SiteFK FROM ManagerRequest JOIN User ON ManagerRequest.UserFKOfManager=User.ID WHERE ManagerRequest.Status='Approved' AND ManagerRequest.SiteFK IN (" . implode(", ", $siteIDsThisIteration) . ")");
		if(mysqli_num_rows($query) > 0){
			while($row = mysqli_fetch_assoc($query)){
				if(array_key_exists($row["SiteFK"], $data)){
					$data[$row["SiteFK"]]["authorities"][] = array($row["FullName"], $row["Email"]);
				}
			}
		}

		//Number of survey locations at the site, number of unique users, and year of site creation
		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, MAX(LocalDate) AS MostRecentSurveyDate, COUNT(DISTINCT Plant.ID) AS PlantCount, COUNT(DISTINCT Survey.UserFKOfObserver) AS ObserverCount, YEAR(MIN(LocalDate)) AS FirstYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK IN (" . implode(", ", $siteIDsThisIteration) . ") GROUP BY Plant.SiteFK");
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
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
