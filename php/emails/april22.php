<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/User.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	
	function send3($minLat, $maxLat){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT Survey.LocalDate) AS DateCount, COUNT(DISTINCT Survey.UserFKOfObserver) AS ParticipantCount, COUNT(DISTINCT Survey.PlantFK) AS PlantCount, COUNT(DISTINCT Plant.Circle) AS SurveyedCircleCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='" . (intval(date("Y")) - 1) . "' AND Plant.SiteFK<>'2' GROUP BY Plant.SiteFK");
		while($siteRow = mysqli_fetch_assoc($query)){
			$site = Site::findByID($siteRow["SiteFK"]);
			if($site->getLatitude() >= $minLat && $site->getLatitude() < $maxLat){
				$siteName = $site->getName();
				$surveyedPlantCount = $siteRow["PlantCount"];
				$surveyedCircleCount = $siteRow["SurveyedCircleCount"];
				$participantCount = $siteRow["ParticipantCount"];
				$dateCount = $siteRow["DateCount"];

				$surveyCount = $site->getNumberOfSurveysByYear((intval(date("Y")) - 1));

				$currentCirclesQuery = mysqli_query($dbconn, "SELECT COUNT(DISTINCT Circle) AS CurrentCircleCount FROM Plant WHERE SiteFK='" . $site->getID() . "'");
				$currentCircles = mysqli_fetch_assoc($currentCirclesQuery)["CurrentCircleCount"];

				$visualQuery = mysqli_query($dbconn, "SELECT COUNT(*) AS VisualSurveyCount FROM Survey JOIN Plant ON Survey.PlantFK = Plant.ID WHERE SiteFK='" . $site->getID() . "' AND YEAR(LocalDate)='" . (intval(date("Y")) - 1) . "' AND ObservationMethod='Visual'");
				$visualSurveyCount = mysqli_fetch_assoc($visualQuery)["VisualSurveyCount"];

				$beatSheetSurveyCount = $surveyCount - $visualSurveyCount;

				$arthropodCountQuery = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) AS Quantity FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . $site->getID() . "' AND YEAR(Survey.LocalDate)='" . (intval(date("Y")) - 1) . "'");
				$arthropodCount = mysqli_fetch_assoc($arthropodCountQuery)["Quantity"];

				$caterpillarQuery = mysqli_query($dbconn, "SELECT COUNT(DISTINCT Survey.ID) AS Occurrences, SUM(ArthropodSighting.Quantity) AS Quantity FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . $site->getID() . "' AND YEAR(Survey.LocalDate)='" . (intval(date("Y")) - 1) . "' AND ArthropodSighting.Group='caterpillar'");
				$caterpillarRow = mysqli_fetch_assoc($caterpillarQuery);
				$caterpillarOccurrence = (floatval($caterpillarRow["Occurrences"]) / floatval($surveyCount)) * 100;
				$caterpillarCount = $caterpillarRow["Quantity"];

				$authorityEmails = $site->getAuthorityEmails();
				for($i = 0; $i < count($authorityEmails); $i++){
					//email3($authorityEmails[$i], "Preparing for a new Caterpillars Count! Season", $siteName, $surveyedPlantCount, $surveyedCircleCount, $currentCircles, $participantCount, $visualSurveyCount, $beatSheetSurveyCount, $dateCount, $arthropodCount, $caterpillarCount, $caterpillarOccurrence);
				echo $authorityEmails[$i] . "Preparing for a new Caterpillars Count! Season" . $siteName . $surveyedPlantCount . $surveyedCircleCount . $currentCircles . $participantCount . $visualSurveyCount . $beatSheetSurveyCount . $dateCount . $arthropodCount . $caterpillarCount . $caterpillarOccurrence . "<br/>";
				}
			}
		}
		mysqli_close($dbconn);
	}

	function send6(){
		$sites = Site::findAll();
		$dbconn = (new Keychain)->getDatabaseConnection();
		for($i = 0; $i < count($sites); $i++){
			if($site->getActive() && $sites[$i]->getNumberOfSurveysByYear(date("Y")) <= 2){
				$query = mysqli_query($dbconn, "SELECT COUNT(Survey.ID) AS Count FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>'" . date("Y") . "-06-13'");
				if(intval(mysqli_fetch_assoc($query)["Count"]) == 0){
					$emails = $sites[$i]->getAuthorityEmails();
					for($j = 0; $j < count($emails); $j++){
						//email6($emails[$j], "Caterpillars Count! at " . $sites[$i]->getName(), $sites[$i]->getName());
						echo $emails[$j] . "Caterpillars Count! at " . $sites[$i]->getName() . $sites[$i]->getName() . "<br/>";
					}
				}
			}
		}
		mysqli_close($dbconn);
	}

	function send7(){
		$sites = Site::findAll();
		$today = "2018-04-22";//TODO: date("Y-m-d");
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
		mysqli_close($dbconn);
	}

	function send8(){
		$today = "2018-04-22";//TODO: date("Y-m-d");
		$sundayOffset = date('w', strtotime($today));
		$monday = date("Y-m-d", strtotime($today . " -" . (6 + $sundayOffset) . " days"));
		$dbconn = (new Keychain)->getDatabaseConnection();
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
	}

	function send4ToAuthorities($site){
		$emails = $site->getAuthorityEmails();
		for($j = 0; $j < count($emails); $j++){
			$firstName = "there";
			$user = User::findByEmail($emails[$j]);
			if(is_object($user) && get_class($user) == "User"){
				$firstName = $user->getFirstName();
			}
			//email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
			echo $emails[$j] . "The Caterpillars Count! Season Has Begun!" . $firstName . "<br/>";
		}
	}

	function send4ToAppAuthoritiesAnd5ToPaperAuthorities($site){
		$emails = $site->getAuthorityEmails();
      
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT COUNT(*) AS `All`, SUM(SubmittedThroughApp) AS `App` FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND YEAR(LocalDate)='" . (intval(date("Y")) - 1) . "'");
		mysqli_close($dbconn);
		$resultRow = mysqli_fetch_assoc($query);
		$all = intval($resultRow["All"]);
		$app = intval($resultRow["App"]);

		for($j = 0; $j < count($emails); $j++){
			$firstName = "there";
			$user = User::findByEmail($emails[$j]);
			if(is_object($user) && get_class($user) == "User"){
				$firstName = $user->getFirstName();
			}

			if($all == 0 || $app > ($all / 2)){
				//email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
				echo $emails[$j] . "The Caterpillars Count! Season Has Begun!" . $firstName . "<br/>";
			}
			else{
				//email5($emails[$j], "Need Help Submitting Caterpillars Count! Surveys?", $firstName);
				echo $emails[$j] . "Need Help Submitting Caterpillars Count! Surveys?" . $firstName . "<br/>";
			}
		}
		mysqli_close($dbconn);
	}
	
	if(date("m/d") == "04/15"){
		send3(-9999, 36.5);
	}
	else if(date("m/d") == "04/22"){
		send3(36.5, 40.7);
	}
	else if(date("m/d") == "04/29"){
		send3(40.7, 9999);
	}
	else if(date("m/d") == "05/20"){
		$sites = Site::findAll();
		for($i = 0; $i < count($sites); $i++){
			if($sites[$i]->getActive() && $sites[$i]->getLatitude() < 36.5 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
				send4ToAuthorities($sites[$i]);
			}
		}
	}
	else if(date("m/d") == "05/27"){
		$sites = Site::findAll();
		for($i = 0; $i < count($sites); $i++){
			if($sites[$i]->getActive() && $sites[$i]->getLatitude() < 36.5 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
				send4ToAppAuthoritiesAnd5ToPaperAuthorities($sites[$i]);
			}
			else if($sites[$i]->getActive() && $sites[$i]->getLatitude() >= 36.5 && $sites[$i]->getLatitude() < 40.7 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
				send4ToAuthorities($sites[$i]);
			}
		}
	}
	else if(date("m/d") == "06/03"){
		$sites = Site::findAll();
		for($i = 0; $i < count($sites); $i++){
			if($sites[$i]->getActive() && $sites[$i]->getLatitude() >= 36.5 && $sites[$i]->getLatitude() < 40.7 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
				send4ToAppAuthoritiesAnd5ToPaperAuthorities($sites[$i]);
			}
			else if($sites[$i]->getActive() && $sites[$i]->getLatitude() >= 40.7 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
				send4ToAuthorities($sites[$i]);
			}
		}
	}
	else if(date("m/d") == "06/10"){
		$sites = Site::findAll();
		for($i = 0; $i < count($sites); $i++){
			$numberOfSurveysThisYear = $sites[$i]->getNumberOfSurveysByYear(date("Y"));
			if($sites[$i]->getActive() && $sites[$i]->getLatitude() < 36.5 && $numberOfSurveysThisYear == 0){
				send4ToAuthorities($sites[$i]);
			}
			else if($sites[$i]->getActive() && $sites[$i]->getLatitude() >= 40.7 && $numberOfSurveysThisYear == 0){
				send4ToAppAuthoritiesAnd5ToPaperAuthorities($sites[$i]);
			}
		}
	}
	else if(date("m/d") == "06/17"){
		$sites = Site::findAll();
		for($i = 0; $i < count($sites); $i++){
			if($sites[$i]->getActive() && $sites[$i]->getLatitude() >= 36.5 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
				send4ToAuthorities($sites[$i]);
			}
		}
	}
	else if(date("m/d") == "06/27"){
		send6();
	}
	
	date_default_timezone_set('US/Eastern');
	$hour = intval(date('H'));
	if(date('D') == "Sun" && $hour > 17 && $hour < 20){
		send7();
		send8();
	}
?>
