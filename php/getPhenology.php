<?php
	require_once('orm/resources/Keychain.php');
	require_once('resultMemory.php');
	require_once('tools/biomassCalculator.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$HIGH_TRAFFIC_MODE = true;
	$SAVE_TIME_LIMIT = 15 * 60;

	$dbconn = (new Keychain)->getDatabaseConnection();
	
  	$lines = json_decode(custgetparam("lines"), true);
	$readableArthropods = array(
		"%" => "All arthropods",
		"ant" => "Ants",
		"aphid" => "Aphids and psyllids",
		"bee" => "Bees, wasps, sawflies",
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
		$query = mysqli_query($dbconn, "SELECT `Name` FROM `Site` WHERE `ID`='$siteID' LIMIT 1");
		if(mysqli_num_rows($query) == 0){
			continue;
		}
		$siteName = mysqli_fetch_assoc($query)["Name"];
		$arthropod = mysqli_real_escape_string($dbconn, htmlentities($lines[$i]["arthropod"]));
		$year = intval($lines[$i]["year"]);
                $weekly = intval($lines[$i]["weekly"] || '');
		
		//CHECK FOR SAVE
		$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $siteID . str_replace('%', 'all', $arthropod) . $year . $weekly);
		if($HIGH_TRAFFIC_MODE){
			$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
			if($save !== null){
				$weightedLines[$readableArthropods[$arthropod] . " at " . $siteName . " in " . $year] = json_decode($save);
				continue;
			}
		}

                $dateStr = 'Survey.LocalDate AS SurveyDate';
                if ($weekly) {
                  // Cast all dates to the Monday of the week they are in
                  $dateStr = 'DATE_ADD(Survey.LocalDate, INTERVAL(-WEEKDAY(Survey.LocalDate)) DAY) AS SurveyDate';
                }

    		$dateWeights = array();
    
		//get survey counts each day
		$query = mysqli_query($dbconn, "SELECT $dateStr, COUNT(*) AS SurveyCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND YEAR(Survey.LocalDate)='$year' GROUP BY SurveyDate ORDER BY SurveyDate");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["SurveyDate"]] = array($row["SurveyDate"], 0, 0, 0, intval($row["SurveyCount"]));
		}
    		
		//occurrence
		//get [survey with specified arthropod] counts each day
		$query = mysqli_query($dbconn, "SELECT $dateStr, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS SurveysWithArthropodsCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND YEAR(Survey.LocalDate)='$year' GROUP BY SurveyDate ORDER BY SurveyDate");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["SurveyDate"]][1] = intval($row["SurveysWithArthropodsCount"]);
		}
		
		//density
		//get arthropod counts each day
		$query = mysqli_query($dbconn, "SELECT $dateStr, SUM(ArthropodSighting.Quantity) AS ArthropodSightings FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND YEAR(Survey.LocalDate)='$year' GROUP BY SurveyDate ORDER BY SurveyDate");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["SurveyDate"]][2] = intval($row["ArthropodSightings"]);
		}
		
		//mean biomass
		//get total biomass each day
		$query = mysqli_query($dbconn, "SELECT $dateStr, ArthropodSighting.UpdatedGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND YEAR(Survey.LocalDate)='$year' GROUP BY SurveyDate, ArthropodSighting.UpdatedGroup, ArthropodSighting.Length");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["SurveyDate"]][3] += (getBiomass($row["UpdatedGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
		}
    
		//finalize
		$dateWeights = array_values($dateWeights);
		for($j = (count($dateWeights) - 1); $j >= 0; $j--){
			if($dateWeights[$j][4] < 5){
				//remove data from dates with fewer than 5 surveys
                          array_splice($dateWeights, $j, 1);
			}
			else{
				//divide
				$dateWeights[$j] = array(
                                  $dateWeights[$j][0],
                                  round((($dateWeights[$j][1] / $dateWeights[$j][4]) * 100), 2),
                                  round(($dateWeights[$j][2] / $dateWeights[$j][4]), 2),
                                  round(($dateWeights[$j][3] / $dateWeights[$j][4]), 2)
                                  );
			}
		}
    		
		//SAVE RESULT
		if($HIGH_TRAFFIC_MODE){
			save($baseFileName, json_encode($dateWeights));
		}
    		$weightedLines[$readableArthropods[$arthropod] . " at " . $siteName . " in " . $year] = $dateWeights;
  	}
  	die("true|" . json_encode($weightedLines));//in the form of: [LABEL: [[LOCAL_DATE, OCCURRENCE, DENSITY, MEAN BIOMASS]]] //example: ["All arthropods at Example Site in 2018": [[2018-08-09, 30, 2.51, 9.7], [2018-08-12, 25, 3.1, 25.2]], [[2018-08-15, 21.3, 0.12, 7.7], [2018-09-02, 70, 0.7, 3.12]]]
?>
