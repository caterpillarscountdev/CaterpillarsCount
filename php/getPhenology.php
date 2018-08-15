<?php
	require_once('orm/resources/Keychain.php');
	require_once('orm/Site.php');
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	
  	$lines = json_decode($_GET["lines"], true);
	$readableArthropods = array(
		"%" => "All arthropods",
		"ant" => "Ants",
		"aphid" => "Aphids and psyllids",
		"bee" => "Bees and wasps",
		"beetle" => "Beetles",
		"caterpillar" => "Caterpillars",
		"daddylonglegs" => "Daddy longlegs",
		"fly" => "Flies",
		"grasshopper" => "Grasshoppers and crickets",
		"leafhopper" => "Leaf hoppers and cicadas",
		"moths" => "Butterflies and moths",
		"spider" => "Spiders",
		"truebugs" => "True bugs",
		"other" => "Other arthropods",
		"unidentified" => "Unidentified arthropods"
	);
  
  	$weightedLines = array();
  	for($i = 0; $i < count($lines); $i++){
		$siteID = intval($lines[$i]["siteID"]);
		$site = Site::findByID($siteID);
		if(!is_object($site) || get_class($site) != "Site"){continue;}
		$arthropod = mysqli_real_escape_string($dbconn, $lines[$i]["arthropod"]);
		$year = intval($lines[$i]["year"]);
    
    		$dateWeights = array();
    
		//get survey counts each day
		$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, COUNT(*) AS DailySurveyCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND YEAR(Survey.LocalDate)='$year' GROUP BY Survey.LocalDate ORDER BY Survey.LocalDate");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["LocalDate"]] = array($row["LocalDate"], 0, 0, intval($row["DailySurveyCount"]));
		}
    		
		//occurrence
		//get [survey with specified arthropod] counts each day
		$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS SurveysWithArthropodsCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND ArthropodSighting.Group LIKE '$arthropod' AND YEAR(Survey.LocalDate)='$year' GROUP BY Survey.LocalDate ORDER BY Survey.LocalDate");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["LocalDate"]][1] = intval($row["SurveysWithArthropodsCount"]);
		}
		
		//density
		//get arthropod counts each day
		$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, SUM(ArthropodSighting.Quantity) AS DailyArthropodSightings FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND ArthropodSighting.Group LIKE '$arthropod' AND YEAR(Survey.LocalDate)='$year' GROUP BY Survey.LocalDate ORDER BY Survey.LocalDate");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["LocalDate"]][2] = intval($row["DailyArthropodSightings"]);
		}
    
		//divide
		$dateWeights = array_values($dateWeights);
		for($j = 0; $j < count($dateWeights); $j++){
			$dateWeights[$j] = array($dateWeights[$j][0], round((($dateWeights[$j][1] / $dateWeights[$j][3]) * 100), 2), round(($dateWeights[$j][2] / $dateWeights[$j][3]), 2));
		}
    
    		$weightedLines[$readableArthropods[$arthropod] . " at " . $site->getName() . " in " . $year] = $dateWeights;
  	}
  	mysqli_close($dbconn);
  	die("true|" . json_encode($weightedLines));//in the form of: [LABEL: [[LOCAL_DATE, OCCURRENCE, DENSITY]]] //example: ["All arthropods at Example Site in 2018": [[2018-08-09, 30, 2.51], [2018-08-12, 25, 3.1]], [[2018-08-15, 21.3, 0.12], [2018-09-02, 70, 0.7]]]
?>
