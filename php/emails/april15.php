<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/User.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT Survey.LocalDate) AS DateCount, COUNT(DISTINCT Survey.UserFKOfObserver) AS ParticipantCount, COUNT(DISTINCT Survey.PlantFK) AS PlantCount, COUNT(DISTINCT Plant.Circle) AS SurveyedCircleCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='" . (intval(date("Y")) - 1) . "' AND Plant.SiteFK<>'2' GROUP BY Plant.SiteFK");
	while($siteRow = mysqli_fetch_assoc($query)){
		$site = Site::findByID($siteRow["SiteFK"]);
		if($site->getLatitude() < 36.5){
			$siteName = $site->getName();
			$surveyedPlantCount = $siteRow["PlantCount"];
			$surveyedCircleCount = $siteRow["SurveyedCircleCount"];
			$participantCount = $siteRow["ParticipantCount"];
			$dateCount = $siteRow["DateCount"];
			
			$surveyCountQuery = mysqli_query($dbconn, "SELECT COUNT(*) AS SurveyCount FROM Survey WHERE SiteFK='" . $site->getID() . "' AND YEAR(LocalDate)='" . (intval(date("Y")) - 1) . "'");
			$surveyCount = mysqli_fetch_assoc($surveyCountQuery)["SurveyCount"];
			
			$currentCirclesQuery = mysqli_query($dbconn, "SELECT COUNT(DISTINCT Circle) AS CurrentCircleCount FROM Plant WHERE SiteFK='" . $site->getID() . "'");
			$currentCircles = mysqli_fetch_assoc($currentCirclesQuery)["CurrentCircleCount"];
			
			$visualQuery = mysqli_query($dbconn, "SELECT COUNT(*) AS VisualSurveyCount FROM Survey WHERE SiteFK='" . $site->getID() . "' AND YEAR(LocalDate)='" . (intval(date("Y")) - 1) . "' AND ObservationMethod='Visual'");
			$visualSurveyCount = mysqli_fetch_assoc($visualQuery)["VisualSurveyCount"];
			
			$beatSheetSurveyCount = $surveyCount - $visualSurveyCount;
			
			$arthropodCountQuery = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . $site->getID() . "' AND YEAR(Survey.LocalDate)='" . (intval(date("Y")) - 1) . "'");
			$arthropodCount = mysqli_fetch_assoc($arthropodCountQuery)["Quantity"];
			
			$caterpillarQuery = mysqli_query($dbconn, "SELECT COUNT(DISTINCT Survey.ID) AS Occurrences, SUM(ArthropodSighting.Quantity) FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . $site->getID() . "' AND YEAR(Survey.LocalDate)='" . (intval(date("Y")) - 1) . "' AND ArthropodSighting.Group='caterpillar'");
			$caterpillarRow = $mysqli_fetch_assoc($caterpillarQuery);
			$caterpillarOccurrence = (floatval($caterpillarRow["Occurrences"]) / floatval($surveyCount)) * 100;
			$caterpillarCount = $caterpillarRow["Quantity"];
			
			$authorityEmails = $site->getAuthorityEmails();
			for($i = 0; $i < count($authorityEmails); $i++){
				email3($authorityEmails[$i], "Preparing for a new Caterpillars Count! Season", $siteName, $surveyedPlantCount, $surveyedCircleCount, $currentCircles, $participantCount, $visualSurveyCount, $beatSheetSurveyCount, $dateCount, $arthropodCount, $caterpillarCount, $caterpillarOccurrence);
			}
		}
	}
	mysqli_close($dbconn);
?>
