<?php
	require_once('orm/resources/Keychain.php');
	require_once('resultMemory.php');
	require_once('tools/biomassCalculator.php');
	
	$HIGH_TRAFFIC_MODE = true;
	$SAVE_TIME_LIMIT = 15 * 60;

	$dbconn = (new Keychain)->getDatabaseConnection();
	
  	$lines = json_decode($_GET["lines"], true);
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
		$startYear = intval($lines[$i]["startYear"]);
		$endYear = intval($lines[$i]["endYear"]);
		
		//CHECK FOR SAVE
		$baseFileName = str_replace(' ', '__SPACE__', basename(__FILE__, '.php') . $siteID . str_replace('%', 'all', $arthropod) . $year);
		if($HIGH_TRAFFIC_MODE){
			$save = getSave($baseFileName, $SAVE_TIME_LIMIT);
			if($save !== null){
				$weightedLines[$readableArthropods[$arthropod] . " at " . $siteName] = json_decode($save);
				continue;
			}
		}
    
    		$dateWeights = array();
    
		//get survey counts each day
		$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, COUNT(*) AS YearlySurveyCount FROM `Survey` JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND (YEAR(Survey.LocalDate)>='$startYear' OR YEAR(Survey.LocalDate)<='$endYear') GROUP BY YEAR(Survey.LocalDate) ORDER BY YEAR(Survey.LocalDate)");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["Year"]] = array($row["Year"], 0, 0, 0, intval($row["YearlySurveyCount"]));
		}
    		
		//occurrence
		//get [survey with specified arthropod] counts each day
		$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS SurveysWithArthropodsCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND (YEAR(Survey.LocalDate)>='$startYear' OR YEAR(Survey.LocalDate)<='$endYear') GROUP BY YEAR(Survey.LocalDate) ORDER BY YEAR(Survey.LocalDate)");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["Year"]][1] = intval($row["SurveysWithArthropodsCount"]);
		}
		
		//density
		//get arthropod counts each day
		$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, SUM(ArthropodSighting.Quantity) AS YearlyArthropodSightings FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND (YEAR(Survey.LocalDate)>='$startYear' OR YEAR(Survey.LocalDate)<='$endYear') GROUP BY YEAR(Survey.LocalDate) ORDER BY YEAR(Survey.LocalDate)");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["Year"]][2] = intval($row["YearlyArthropodSightings"]);
		}
		
		//mean biomass
		//get total biomass each day
		$query = mysqli_query($dbconn, "SELECT YEAR(Survey.LocalDate) AS Year, ArthropodSighting.UpdatedGroup, ArthropodSighting.Length, SUM(ArthropodSighting.Quantity) AS TotalQuantity FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='$siteID' AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' AND (YEAR(Survey.LocalDate)>='$startYear' OR YEAR(Survey.LocalDate)<='$endYear') GROUP BY YEAR(Survey.LocalDate), ArthropodSighting.UpdatedGroup, ArthropodSighting.Length");
		while($row = mysqli_fetch_assoc($query)){
			$dateWeights[$row["Year"]][3] += (getBiomass($row["UpdatedGroup"], $row["Length"]) * floatval($row["TotalQuantity"]));
		}
    
		//finalize
		$dateWeights = array_values($dateWeights);
		for($j = (count($dateWeights) - 1); $j >= 0; $j--){
			if($dateWeights[$j][4] < 5){
				//remove data from years with fewer than 5 surveys
				array_splice($dateWeights, $j, 1);
			}
			else{
				//divide
				$dateWeights[$j] = array($dateWeights[$j][0], round((($dateWeights[$j][1] / $dateWeights[$j][4]) * 100), 2), round(($dateWeights[$j][2] / $dateWeights[$j][4]), 2), round(($dateWeights[$j][3] / $dateWeights[$j][4]), 2));
			}
		}
    		
		//SAVE RESULT
		if($HIGH_TRAFFIC_MODE){
			save($baseFileName, json_encode($dateWeights));
		}
    		$weightedLines[$readableArthropods[$arthropod] . " at " . $siteName] = $dateWeights;
  	}
  	mysqli_close($dbconn);
  	die("true|" . json_encode($weightedLines));//in the form of: [LABEL: [[YEAR, OCCURRENCE, DENSITY, MEAN BIOMASS]]] //example: ["All arthropods at Example Site": [[2018, 30, 2.51, 9.7], [2019, 25, 3.1, 25.2]], [[2020, 21.3, 0.12, 7.7], [2021, 70, 0.7, 3.12]]]
?>
