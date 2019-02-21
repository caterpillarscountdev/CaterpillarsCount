<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');
  
	$siteID = intval($_GET["siteID"]);
	$site = Site::findByID($siteID);
	if(is_object($site) && get_class($site) == "Site"){
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		//Email of site owner and managers
		$creator = $site->getCreator();
		$authorities = array(array($creator->getFullName(), $creator->getEmail()));
		$managerRequests = ManagerRequest::findManagerRequestsBySite($site);
		for($i = 0; $i < count($managerRequests); $i++){
			if($managerRequests[$i]->getStatus() == "Approved"){
				$manager = $managerRequests[$i]->getManager();
				$authorities[] = array($manager->getFullName(), $manager->getEmail());
			}
		}
		
		//Year of site creation
		$firstSurveyYear = "N/A";
		$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' ORDER BY YEAR(Survey.LocalDate) ASC LIMIT 1");
		if(mysqli_num_rows($query) > 0){
			$firstSurveyYear = mysqli_fetch_assoc($query)["Year"];
		}
		
		//Number of survey locations at the site
		$plantCount = 0;
		$query = mysqli_query($dbconn, "SELECT COUNT(*) AS PlantCount FROM Plant WHERE SiteFK='$siteID' AND Circle>'0'");
		if(mysqli_num_rows($query) > 0){
			$plantCount = mysqli_fetch_assoc($query)["PlantCount"];
		}
		
		//Number of unique users
		$observerCount = 0;
		$query = mysqli_query($dbconn, "SELECT COUNT(DISTINCT Survey.UserFKOfObserver) AS ObserverCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID'");
		if(mysqli_num_rows($query) > 0){
			$observerCount = mysqli_fetch_assoc($query)["ObserverCount"];
		}
		
		//Most recent survey date
		$mostRecentSurveyDate = "N/A";
		$query = mysqli_query($dbconn, "SELECT Survey.LocalDate AS MostRecentSurveyDate FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' ORDER BY Survey.LocalDate DESC LIMIT 1");
		if(mysqli_num_rows($query) > 0){
			$mostRecentSurveyDate = mysqli_fetch_assoc($query)["MostRecentSurveyDate"];
		}
		
		//surveys each week, separated by year
		$query = mysqli_query($dbconn, "SELECT WEEK(\"" . substr($mostRecentSurveyDate, 0, 4) . "-01-01\") AS StartWeek, WEEK(\"" . substr($mostRecentSurveyDate, 0, 4) . "-12-31\") AS EndWeek");
		$row = mysqli_fetch_assoc($query);
		$startWeek = intval($row["StartWeek"]);
		$endWeek = intval($row["EndWeek"]);
		
		$surveysEachWeek = array();
		for($i = $startWeek; $i <= $endWeek; $i++){
			$surveysEachWeek[] = 0;
		}
		if($mostRecentSurveyDate != "N/A"){
			$query = mysqli_query($dbconn, "SELECT WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND YEAR(Survey.LocalDate)=YEAR('$mostRecentSurveyDate') GROUP BY WEEK(Survey.LocalDate, 1)");
			while($row = mysqli_fetch_assoc($query)){
				$surveysEachWeek[intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
			}
		}
		
		//available years
		$availableYears = array();
		$query = mysqli_query($dbconn, "SELECT DISTINCT YEAR(Survey.LocalDate) AS Year FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' ORDER BY YEAR(Survey.LocalDate) ASC");
		if(mysqli_num_rows($query) > 0){
			while($row = mysqli_fetch_assoc($query)){
				$availableYears[] = $row["Year"];
			}
		}
		
		mysqli_close($dbconn);
		
		$siteArray = array(
			"name" => $site->getName(),
			"url" => $site->getURL(),
			"authorities" => $authorities,
			"firstSurveyYear" => $firstSurveyYear,
			"plantCount" => $plantCount,
			"observerCount" => $observerCount,
			"mostRecentSurveyDate" => $mostRecentSurveyDate,
			"surveysEachWeek" => $surveysEachWeek,
			"availableYears" => $availableYears
		);
		die("true|" . json_encode($siteArray));
	}
	die("false|We could not find this site.");
?>