<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');
	$year = intval($_GET["year"]);
	$start = intval($_GET["start"]);
	$LIMIT = 500;
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$data = array();
	$query = mysqli_query($dbconn, "SELECT WEEK(\"" . $year . "-01-01\") AS StartWeek, WEEK(\"" . $year . "-12-31\") AS EndWeek");
	$row = mysqli_fetch_assoc($query);
	$startWeek = intval($row["StartWeek"]);
	$endWeek = intval($row["EndWeek"]);
	$siteIDs = array();
	$lastCall = ($start + $LIMIT) >= intval(mysqli_fetch_assoc(mysqli_query($dbconn, "SELECT COUNT(*) AS Count FROM Site"))["Count"]);
	
	$sitesQuery = mysqli_query($dbconn, "SELECT ID FROM Site LIMIT $start, $LIMIT");
	if(mysqli_num_rows($sitesQuery) > 0){
		while($row = mysqli_fetch_assoc($sitesQuery)){
			$surveysEachWeek = array();
			for($i = $startWeek; $i <= $endWeek; $i++){
				$surveysEachWeek[] = 0;
			}
			
			if(intval($row["ID"]) != 2){
				$siteIDs[] = $row["ID"];
				$data[(string)$row["ID"]] = $surveysEachWeek;
			}
			else if(count($sites) == 1){
				die("true|" . json_encode(array($data, true)));
			}
		}
	}

	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='$year' AND Plant.SiteFK IN (" . implode(", ", $siteIDs) . ") GROUP BY CONCAT(Plant.SiteFK, ' ', WEEK(Survey.LocalDate, 1))");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
			$data[(string)$row["SiteFK"]][intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
		}
	}
	
	die("true|" . json_encode(array($data, $lastCall)));
?>
