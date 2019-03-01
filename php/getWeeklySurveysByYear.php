<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');
	$year = intval($_GET["year"]);
	$start = intval($_GET["start"]);
	$LIMIT = 50;
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
	$data = array();
	$sites = Site::findAll($start, $LIMIT);
	$query = mysqli_query($dbconn, "SELECT WEEK(\"" . $year . "-01-01\") AS StartWeek, WEEK(\"" . $year . "-12-31\") AS EndWeek");
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
			$query = mysqli_query($dbconn, "SELECT WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . $sites[$i]->getID() . "' AND YEAR(Survey.LocalDate)='$year' GROUP BY WEEK(Survey.LocalDate, 1)");
			if(mysqli_num_rows($query) > 0){
				while($row = mysqli_fetch_assoc($query)){
					$surveysEachWeek[intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
				}
			}
			
			$siteIDs[] = $sites[$i]->getID();
			$data[(string)$sites[$i]->getID()] = $surveysEachWeek;
		}
		else if(count($sites) == 1){
			die("true|" . json_encode(array($data, true)));
		}
	}
	die("true|" . json_encode(array($data, $lastCall)));
?>
