<?php
  require_once('orm/resources/Keychain.php');
	require_once("orm/Site.php");
  
  $dbconn = (new Keychain)->getDatabaseConnection();

$siteID = 78;
	$site = Site::findByID($siteID);
	$arthropod = "%";
	$year = "2019";
  
  $peakCaterpillarOccurrenceDate = "";
						$peakCaterpillarOccurrence = 0;
						$caterpillarOccurrenceArray = array();
						$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, Count(DISTINCT ArthropodSighting.SurveyFK) AS SurveyCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $site->getID() . "' AND YEAR(Survey.LocalDate)=YEAR('$monday') GROUP BY Survey.LocalDate ORDER BY SurveyCount DESC, Survey.LocalDate ASC");
						while($dateSurveyRow = mysqli_fetch_assoc($query)){
							$caterpillarOccurrenceArray[$dateSurveyRow["LocalDate"]] = $dateSurveyRow["SurveyCount"];
						}
						$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, Count(DISTINCT ArthropodSighting.SurveyFK) AS SurveyWithCaterpillarCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $site->getID() . "' AND YEAR(Survey.LocalDate)=YEAR('$monday') AND ArthropodSighting.Group='caterpillar' GROUP BY Survey.LocalDate ORDER BY SurveyWithCaterpillarCount DESC, Survey.LocalDate ASC");
						while($dateCaterpillarRow = mysqli_fetch_assoc($query)){
							$occurrence = 0;
							if(floatval($caterpillarOccurrenceArray[$dateCaterpillarRow["LocalDate"]]) != 0){
								$occurrence = round((floatval($dateCaterpillarRow["SurveyWithCaterpillarCount"]) / floatval($caterpillarOccurrenceArray[$dateCaterpillarRow["LocalDate"]])) * 100, 2);
							}
							if($occurrence > $peakCaterpillarOccurrence){
								$peakCaterpillarOccurrence = $occurrence;
								$peakCaterpillarOccurrenceDate = $dateCaterpillarRow["LocalDate"];
							}
						}
            
            echo $peakCaterpillarOccurrenceDate . ": " . $peakCaterpillarOccurrence . "<br/><br/>";
            
            
            
            
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
    
		//finalize
		$dateWeights = array_values($dateWeights);
		for($j = (count($dateWeights) - 1); $j >= 0; $j--){
			if($dateWeights[$j][3] < 5){
				//remove data from dates with fewer than 5 surveys
				array_splice($dateWeights, $j, 1);
			}
			else{
				//divide
        echo "</br/>" . $dateWeights[$j][0] . ": " . round((($dateWeights[$j][1] / $dateWeights[$j][3]) * 100), 2);
				//$dateWeights[$j] = array($dateWeights[$j][0], round((($dateWeights[$j][1] / $dateWeights[$j][3]) * 100), 2), round(($dateWeights[$j][2] / $dateWeights[$j][3]), 2));
			}
		}
?>
