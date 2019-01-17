<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/User.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	set_time_limit(0);
$time_start = microtime(true); 
	$sites = Site::findAll();
	$today = "2018-04-22";//date("Y-m-d");
	$sundayOffset = date('w', strtotime($today));
    	$monday = date("Y-m-d", strtotime($today . " -" . (6 + $sundayOffset) . " days"));
	$dbconn = (new Keychain)->getDatabaseConnection();
	for($i = 0; $i < count($sites); $i++){
		$query = mysqli_query($dbconn, "SELECT COUNT(Survey.ID) AS SurveyCount, COUNT(DISTINCT Survey.UserFKOfObserver) AS UserCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>='$monday'");
		$row = mysqli_fetch_assoc($query);
		$surveyCount = intval($row["SurveyCount"]);
		$userCount = intval($row["UserCount"]);
		if($surveyCount > 0){
			//site with surveys since monday email
			$query = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>='$monday'");
			$arthropodCount = intval(mysqli_fetch_assoc($query)["ArthropodCount"]);
			
			$query = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) AS CaterpillarCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>='$monday' AND ArthropodSighting.Group='caterpillar'");
			$caterpillarCount = intval(mysqli_fetch_assoc($query)["CaterpillarCount"]);
			
			$arthropod1 = "";
			$arthropod1Count = "";
			$arthropod2 = "";
			$arthropod2Count = "";
			$query = mysqli_query($dbconn, "SELECT ArthropodSighting.`Group`, SUM(ArthropodSighting.Quantity) AS Count FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>='$monday' GROUP BY ArthropodSighting.`Group` ORDER BY Count DESC LIMIT 3");
			while($row = mysqli_fetch_assoc($query)){
				if($row["Group"] != "caterpillar"){
					if($arthropod1Count == ""){
						$arthropod1 = str_replace("leafhopper", "leaf hopper", str_replace("daddylonglegs", "daddy longleg", str_replace("moths", "moth", str_replace("truebugs", "true bug", $row["Group"]))));
						$arthropod1Count = $row["Count"];
					}
					else if($arthropod2Count == ""){
						$arthropod2 = str_replace("leafhopper", "leaf hopper", str_replace("daddylonglegs", "daddy longleg", str_replace("moths", "moth", str_replace("truebugs", "true bug", $row["Group"]))));
						$arthropod2Count = $row["Count"];
					}
				}
			}
			
			$peakCaterpillarOccurrenceDate = "";
			$peakCaterpillarOccurrence = 0;
			$caterpillarOccurrenceArray = array();
			$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, Count(DISTINCT ArthropodSighting.SurveyFK) AS SurveyCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>='$monday' GROUP BY Survey.LocalDate ORDER BY SurveyCount DESC, Survey.LocalDate ASC");
			while($dateSurveyRow = mysqli_fetch_assoc($query)){
				$caterpillarOccurrenceArray[$dateSurveyRow["LocalDate"]] = $dateSurveyRow["SurveyCount"];
			}
			$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, Count(DISTINCT ArthropodSighting.SurveyFK) AS SurveyWithCaterpillarCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>='$monday' AND ArthropodSighting.Group='caterpillar' GROUP BY Survey.LocalDate ORDER BY SurveyWithCaterpillarCount DESC, Survey.LocalDate ASC");
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
			
			$emails = $sites[$i]->getAuthorityEmails();
			for($j = 0; $j < count($emails); $j++){
				//email7($emails[$j], "This Week at " . $sites[$i]->getName() . "...", $userCount, $surveyCount, $sites[$i]->getName(), $arthropodCount, $caterpillarCount, $arthropod1, $arthropod1Count, $arthropod2, $arthropod2Count, $peakCaterpillarOccurrenceDate, $peakCaterpillarOccurrence, $sites[$i]->getID());
				echo $emails[$j] . "This Week at " . $sites[$i]->getName() . "..." . $userCount . $surveyCount . $sites[$i]->getName() . $arthropodCount . $caterpillarCount . $arthropod1 . $arthropod1Count . $arthropod2 . $arthropod2Count . $peakCaterpillarOccurrenceDate . $peakCaterpillarOccurrence . $sites[$i]->getID() . "<br/>";
			}
		}
	}
	$query = mysqli_query($dbconn, "SELECT DISTINCT Survey.UserFKOfObserver AS UserID FROM Survey WHERE Survey.LocalDate>='$monday'");
	while($userIDRow = mysqli_fetch_assoc($query)){
		$user = User::findByID($userIDRow["UserID"]);
		if(is_object($user) && get_class($user) == "User"){
			$query = mysqli_query($dbconn, "SELECT DISTINCT Plant.SiteFK AS SiteID FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.LocalDate>='$monday' AND Survey.UserFKOfObserver='" . $user->getID() . "'");
			$sites = array();
			while($siteIDRow = mysqli_fetch_assoc($query)){
				$sites[] = Site::findByID($siteIDRow["SiteID"]);
			}
			
			$query = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.LocalDate>='$monday' AND Survey.UserFKOfObserver='" . $user->getID() . "'");
			$arthropodCount = intval(mysqli_fetch_assoc($query)["ArthropodCount"]);
			
			$query = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) AS CaterpillarCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.LocalDate>='$monday' AND Survey.UserFKOfObserver='" . $user->getID() . "' AND ArthropodSighting.Group='caterpillar'");
			$caterpillarCount = intval(mysqli_fetch_assoc($query)["CaterpillarCount"]);
			
			$query = mysqli_query($dbconn, "SELECT * FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `UserFKOfObserver`='" . $user->getID() . "' AND PhotoURL<>'' LIMIT 1");
			$userHasINaturalistObservations = (mysqli_num_rows($query) > 0);
			
			//email8($user->getEmail(), "Your Caterpillars Count! weekly summary", $sites, $arthropodCount, $caterpillarCount, $user->getINaturalistObserverID(), $userHasINaturalistObservations);
			echo $user->getEmail() . "Your Caterpillars Count! weekly summary" . $sites . $arthropodCount . $caterpillarCount . $user->getINaturalistObserverID() . $userHasINaturalistObservations . "<br/>";
		}
	}
	mysqli_close($dbconn);
echo "<br/>" . round(((microtime(true) - $time_start)/60)*100) . "% of resources used.";
?>
