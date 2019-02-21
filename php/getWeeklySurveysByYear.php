<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');
  
	$siteID = intval($_GET["siteID"]);
	$year = intval($_GET["year"]);
  
	$site = Site::findByID($siteID);
	if(is_object($site) && get_class($site) == "Site"){
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		//surveys each week, separated by year
		$query = mysqli_query($dbconn, "SELECT WEEK(\"$year-01-01\") AS StartWeek, WEEK(\"$year-12-31\") AS EndWeek");
		$row = mysqli_fetch_assoc($query);
		$startWeek = intval($row["StartWeek"]);
		$endWeek = intval($row["EndWeek"]);
		
		$surveysEachWeek = array();
		for($i = $startWeek; $i <= $endWeek; $i++){
			$surveysEachWeek[] = 0;
		}
		$query = mysqli_query($dbconn, "SELECT WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND YEAR(Survey.LocalDate)='$year' GROUP BY WEEK(Survey.LocalDate, 1)");
		while($row = mysqli_fetch_assoc($query)){
			$surveysEachWeek[intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
		}
		
		mysqli_close($dbconn);
    
		die("true|" . json_encode($surveysEachWeek));
	}
	die("false|We could not find this site.");
?>
