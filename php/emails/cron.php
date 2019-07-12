<?php
	//A CRON JOB RUNS THIS SCRIPT ONCE PER MINUTE.
	
	header('Access-Control-Allow-Origin: *');
	
	//TO PAUSE ALL EMAILS, UNCOMMENT THE FOLLOWING "die();" LINE
	//die();
	
	require_once("/opt/app-root/src/php/orm/Site.php");
	require_once("/opt/app-root/src/php/orm/User.php");
	require_once("/opt/app-root/src/php/orm/resources/Keychain.php");
	require_once("/opt/app-root/src/php/orm/resources/mailing.php");

	date_default_timezone_set('US/Eastern');
	
	//ADJUSTABLE LIMITS:
	//WARNING: If you change these limits, make sure to adjust the time that the TemporaryEmailLog clears. You want it to clear while no emails are being sent out so you dont end up with duplicate sends.
	$SUNDAY_START_HOUR = 21;
	$MONDAY_END_HOUR = 6;
	$ANNUAL_START_HOUR = 19;
	$ANNUAL_END_HOUR = 23;
	$MAX_EMAIL_SENDS = 5;//max emails send each time this script is run (to prevent timeouts)

	$emailsSent = 0;//current number of emails sent during this run
	
	//CLEAR TemporaryEmailLog TABLE IN DATABASE (during downtime):
	if(date('H:i') == "07:00" || date('H:i') == "07:01" || date('H:i') == "07:02"){
		$dbconn = (new Keychain)->getDatabaseConnection();
		mysqli_query($dbconn, "DELETE FROM TemporaryEmailLog WHERE `Date`<'" . date("Y-m-d") . "'");
		mysqli_close($dbconn);
	}

	function logSend($userIdentifier, $emailTypeIdentifier = "DEFAULT TO CURRENT DATE"){
		$date = date("Y-m-d");
		if($emailTypeIdentifier == "DEFAULT TO CURRENT DATE"){
			$emailTypeIdentifier = $date;
		}
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		mysqli_query($dbconn, "INSERT INTO TemporaryEmailLog (`UserIdentifier`, `EmailTypeIdentifier`, `Date`) VALUES ('$userIdentifier', '$emailTypeIdentifier', '$date')");
		mysqli_close($dbconn);
	}
	
	function getSends($emailTypeIdentifier = "DEFAULT TO CURRENT DATE"){
		$date = date("Y-m-d");
		if($emailTypeIdentifier == "DEFAULT TO CURRENT DATE"){
			$emailTypeIdentifier = $date;
		}
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `UserIdentifier` FROM TemporaryEmailLog WHERE `Date`='$date' AND `EmailTypeIdentifier`='$emailTypeIdentifier'");
		mysqli_close($dbconn);
		$userIdentifiers = array();
		while($row = mysqli_fetch_assoc($query)){
			$userIdentifiers[] = strval($row["UserIdentifier"]);
		}
		return $userIdentifiers;
	}

	function allEmailsHaveBeenSent($emails, $sends){
		for($i = 0; $i < count($emails); $i++){
			if(!in_array($emails[$i], $sends)){
				return false;
			}
		}
		return true;
	}
	
	function send3($minLat, $maxLat){
		global $emailsSent;
		global $MAX_EMAIL_SENDS;
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, COUNT(DISTINCT Survey.LocalDate) AS DateCount, COUNT(DISTINCT Survey.UserFKOfObserver) AS ParticipantCount, COUNT(DISTINCT Survey.PlantFK) AS PlantCount, COUNT(DISTINCT Plant.Circle) AS SurveyedCircleCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='" . (intval(date("Y")) - 1) . "' AND Plant.SiteFK<>'2' GROUP BY Plant.SiteFK");
		while($siteRow = mysqli_fetch_assoc($query)){
			if($emailsSent < $MAX_EMAIL_SENDS){
				$site = Site::findByID($siteRow["SiteFK"]);
				$sends = getSends(date("Y-m-d") . "|" . $site->getID());
				if($site->getLatitude() >= $minLat && $site->getLatitude() < $maxLat){
					$authorityEmails = $site->getAuthorityEmails();
					if(!allEmailsHaveBeenSent($authorityEmails, $sends)){
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
						for($i = 0; $i < count($authorityEmails); $i++){
							if($emailsSent < $MAX_EMAIL_SENDS && !in_array($authorityEmails[$i], $sends)){
								email3($authorityEmails[$i], "Preparing for a new Caterpillars Count! Season", $siteName, $surveyedPlantCount, $surveyedCircleCount, $currentCircles, $participantCount, $visualSurveyCount, $beatSheetSurveyCount, $dateCount, $arthropodCount, $caterpillarCount, $caterpillarOccurrence);
								logSend($authorityEmails[$i], date("Y-m-d") . "|" . $site->getID());
								$emailsSent++;
							}
						}
					}
				}
			}
		}
		mysqli_close($dbconn);
	}
	function send6(){
		global $emailsSent;
		global $MAX_EMAIL_SENDS;
		$sites = Site::findAll();
		$dbconn = (new Keychain)->getDatabaseConnection();
		for($i = 0; $i < count($sites); $i++){
			if($sites[$i]->getID() != 2){
				$sends = getSends(date("Y-m-d") . "|" . $sites[$i]->getID());
				if($emailsSent < $MAX_EMAIL_SENDS && $sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getNumberOfSurveysByYear(date("Y")) <= 2){
					$query = mysqli_query($dbconn, "SELECT COUNT(Survey.ID) AS Count FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND Survey.LocalDate>'" . date("Y") . "-06-13'");
					if(intval(mysqli_fetch_assoc($query)["Count"]) == 0){
						$emails = $sites[$i]->getAuthorityEmails();
						for($j = 0; $j < count($emails); $j++){
							if($emailsSent < $MAX_EMAIL_SENDS && !in_array($emails[$j], $sends)){
								email6($emails[$j], "Caterpillars Count! at " . $sites[$i]->getName(), $sites[$i]->getName());
								logSend($emails[$j], date("Y-m-d") . "|" . $sites[$i]->getID());
								$emailsSent++;
							}
						}
					}
				}
			}
		}
		mysqli_close($dbconn);
	}
	function send7(){
		global $emailsSent;
		global $MAX_EMAIL_SENDS;
		$sites = Site::findAll();
		$today = date("Y-m-d");
		$sundayOffset = date('w', strtotime($today));
		$monday = date("Y-m-d", strtotime($today . " -" . (6 + $sundayOffset) . " days"));
		$dbconn = (new Keychain)->getDatabaseConnection();
		for($i = 0; $i < count($sites); $i++){
			if($emailsSent < $MAX_EMAIL_SENDS && $sites[$i]->getID() != 2){
				$sends = getSends("email7|" . $sites[$i]->getID());
				$emails = $sites[$i]->getAuthorityEmails();
				if(!allEmailsHaveBeenSent($emails, $sends)){
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
						$SURVEY_SUBSTANTIATION_THRESHOLD = 5;
						$peakCaterpillarOccurrenceDate = "";
						$peakCaterpillarOccurrence = 0;
						$caterpillarOccurrenceArray = array();
						$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND YEAR(Survey.LocalDate)=YEAR('$monday') GROUP BY Survey.LocalDate ORDER BY SurveyCount DESC, Survey.LocalDate ASC");
						while($dateSurveyRow = mysqli_fetch_assoc($query)){
							$caterpillarOccurrenceArray[$dateSurveyRow["LocalDate"]] = $dateSurveyRow["SurveyCount"];
						}
						$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, Count(DISTINCT ArthropodSighting.SurveyFK) AS SurveyWithCaterpillarCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND YEAR(Survey.LocalDate)=YEAR('$monday') AND ArthropodSighting.Group='caterpillar' GROUP BY Survey.LocalDate ORDER BY SurveyWithCaterpillarCount DESC, Survey.LocalDate ASC");
						while($dateCaterpillarRow = mysqli_fetch_assoc($query)){
							$occurrence = 0;
							if(intval($caterpillarOccurrenceArray[$dateCaterpillarRow["LocalDate"]]) != 0){
								$occurrence = round((floatval($dateCaterpillarRow["SurveyWithCaterpillarCount"]) / floatval($caterpillarOccurrenceArray[$dateCaterpillarRow["LocalDate"]])) * 100, 2);
							}
							if($occurrence > $peakCaterpillarOccurrence && intval($caterpillarOccurrenceArray[$dateCaterpillarRow["LocalDate"]]) >= $SURVEY_SUBSTANTIATION_THRESHOLD){
								$peakCaterpillarOccurrence = $occurrence;
								$peakCaterpillarOccurrenceDate = $dateCaterpillarRow["LocalDate"];
							}
						}
						for($j = 0; $j < count($emails); $j++){
							if($emailsSent < $MAX_EMAIL_SENDS && !in_array($emails[$j], $sends)){
								email7($emails[$j], "This Week at " . $sites[$i]->getName() . "...", $userCount, $surveyCount, $sites[$i]->getName(), $arthropodCount, $caterpillarCount, $arthropod1, $arthropod1Count, $arthropod2, $arthropod2Count, $peakCaterpillarOccurrenceDate, $peakCaterpillarOccurrence, $sites[$i]->getID());
								logSend($emails[$j], "email7|" . $sites[$i]->getID());
								$emailsSent++;
							}
						}
					}
				}
			}
		}
		mysqli_close($dbconn);
	}
	function send8(){
		global $emailsSent;
		global $MAX_EMAIL_SENDS;
		$sends = getSends("email8");
		$today = date("Y-m-d");
		$sundayOffset = date('w', strtotime($today));
		$monday = date("Y-m-d", strtotime($today . " -" . (6 + $sundayOffset) . " days"));
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT DISTINCT Survey.UserFKOfObserver AS UserID FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.LocalDate>='$monday' AND Plant.SiteFK<>'2'");
		while($userIDRow = mysqli_fetch_assoc($query)){
			if($emailsSent < $MAX_EMAIL_SENDS){
				$userID = $userIDRow["UserID"];
				if(!in_array(strval($userID), $sends)){
					$user = User::findByID($userID);
					if(is_object($user) && get_class($user) == "User"){
						$innerQuery = mysqli_query($dbconn, "SELECT DISTINCT Plant.SiteFK AS SiteID FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.LocalDate>='$monday' AND Survey.UserFKOfObserver='" . $user->getID() . "' AND Plant.SiteFK<>'2'");
						$sites = array();
						while($siteIDRow = mysqli_fetch_assoc($innerQuery)){
							$sites[] = Site::findByID($siteIDRow["SiteID"]);
						}
						$innerQuery = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) AS ArthropodCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.LocalDate>='$monday' AND Survey.UserFKOfObserver='" . $user->getID() . "'");
						$arthropodCount = intval(mysqli_fetch_assoc($innerQuery)["ArthropodCount"]);
						$innerQuery = mysqli_query($dbconn, "SELECT SUM(ArthropodSighting.Quantity) AS CaterpillarCount FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.LocalDate>='$monday' AND Survey.UserFKOfObserver='" . $user->getID() . "' AND ArthropodSighting.Group='caterpillar'");
						$caterpillarCount = intval(mysqli_fetch_assoc($innerQuery)["CaterpillarCount"]);
						$innerQuery = mysqli_query($dbconn, "SELECT * FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `UserFKOfObserver`='" . $user->getID() . "' AND PhotoURL<>'' LIMIT 1");
						$userHasINaturalistObservations = (mysqli_num_rows($innerQuery) > 0);
						email8($user->getEmail(), "Your Caterpillars Count! weekly summary", $sites, $arthropodCount, $caterpillarCount, $user->getINaturalistObserverID(), $userHasINaturalistObservations);
						logSend($user->getID(), "email8");
						$emailsSent++;
					}
				}
			}
		}
		mysqli_close($dbconn);
	}
	function send4ToAuthorities($site){
		global $emailsSent;
		global $MAX_EMAIL_SENDS;
		if($site->getID() != 2){
			$sends = getSends("email4");
			$emails = $site->getAuthorityEmails();
			for($j = 0; $j < count($emails); $j++){
				if($emailsSent < $MAX_EMAIL_SENDS && !in_array($emails[$j], $sends)){
					$firstName = "there";
					$user = User::findByEmail($emails[$j]);
					if(is_object($user) && get_class($user) == "User"){
						$firstName = $user->getFirstName();
					}
					email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
					logSend($emails[$j], "email4");
					$emailsSent++;
				}
			}
		}
	}
	function send4ToAppAuthoritiesAnd5ToPaperAuthorities($site){
		global $emailsSent;
		global $MAX_EMAIL_SENDS;
		if($site->getID() != 2){
			$email4Sends = getSends("email4");
			$email5Sends = getSends("email5");
			$emails = $site->getAuthorityEmails();
			$dbconn = (new Keychain)->getDatabaseConnection();
			$query = mysqli_query($dbconn, "SELECT COUNT(*) AS `All`, SUM(SubmittedThroughApp) AS `App` FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $site->getID() . "' AND YEAR(LocalDate)='" . (intval(date("Y")) - 1) . "'");
			$resultRow = mysqli_fetch_assoc($query);
			$all = intval($resultRow["All"]);
			$app = intval($resultRow["App"]);
			for($j = 0; $j < count($emails); $j++){
				if($emailsSent < $MAX_EMAIL_SENDS && !(in_array($emails[$j], $email4Sends) && in_array($emails[$j], $email5Sends))){
					$firstName = "there";
					$user = User::findByEmail($emails[$j]);
					if(is_object($user) && get_class($user) == "User"){
						$firstName = $user->getFirstName();
					}

					if($all == 0 || $app > ($all / 2)){
						if(!in_array($emails[$j], $email4Sends)){
							email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
							logSend($emails[$j], "email4");
							$emailsSent++;
						}
					}
					else{
						if(!in_array($emails[$j], $email5Sends)){
							email5($emails[$j], "Need Help Submitting Caterpillars Count! Surveys?", $firstName);
							logSend($emails[$j], "email5");
							$emailsSent++;
						}
					}
				}
			}
			mysqli_close($dbconn);
		}
	}
	
	if(intval(date('H')) >= $ANNUAL_START_HOUR && intval(date('H')) <= $ANNUAL_END_HOUR){
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
				if($emailsSent < $MAX_EMAIL_SENDS && $sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getLatitude() < 36.5 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
					send4ToAuthorities($sites[$i]);
				}
			}
		}
		else if(date("m/d") == "05/27"){
			$sites = Site::findAll();
			for($i = 0; $i < count($sites); $i++){
				if($emailsSent < $MAX_EMAIL_SENDS){
					$numberOfSurveysThisYear = $sites[$i]->getNumberOfSurveysByYear(date("Y"));
					if($sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getLatitude() < 36.5 && $numberOfSurveysThisYear == 0){
						send4ToAppAuthoritiesAnd5ToPaperAuthorities($sites[$i]);
					}
					else if($sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getLatitude() >= 36.5 && $sites[$i]->getLatitude() < 40.7 && $numberOfSurveysThisYear == 0){
						send4ToAuthorities($sites[$i]);
					}
				}
			}
		}
		else if(date("m/d") == "06/03"){
			$sites = Site::findAll();
			for($i = 0; $i < count($sites); $i++){
				if($emailsSent < $MAX_EMAIL_SENDS){
					$numberOfSurveysThisYear = $sites[$i]->getNumberOfSurveysByYear(date("Y"));
					if($sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getLatitude() >= 36.5 && $sites[$i]->getLatitude() < 40.7 && $numberOfSurveysThisYear == 0){
						send4ToAppAuthoritiesAnd5ToPaperAuthorities($sites[$i]);
					}
					else if($sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getLatitude() >= 40.7 && $numberOfSurveysThisYear == 0){
						send4ToAuthorities($sites[$i]);
					}
				}
			}
		}
		else if(date("m/d") == "06/10"){
			$sites = Site::findAll();
			for($i = 0; $i < count($sites); $i++){
				if($emailsSent < $MAX_EMAIL_SENDS){
					$numberOfSurveysThisYear = $sites[$i]->getNumberOfSurveysByYear(date("Y"));
					if($sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getLatitude() < 36.5 && $numberOfSurveysThisYear == 0){
						send4ToAuthorities($sites[$i]);
					}
					else if($sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getLatitude() >= 40.7 && $numberOfSurveysThisYear == 0){
						send4ToAppAuthoritiesAnd5ToPaperAuthorities($sites[$i]);
					}
				}
			}
		}
		else if(date("m/d") == "06/17"){
			$sites = Site::findAll();
			for($i = 0; $i < count($sites); $i++){
				if($emailsSent < $MAX_EMAIL_SENDS && $sites[$i]->getWantsToReceiveEmails() && $sites[$i]->getLatitude() >= 36.5 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
					send4ToAuthorities($sites[$i]);
				}
			}
		}
		else if(date("m/d") == "06/27"){
			send6();
		}
	}

	if((date('D') == "Sun" && intval(date('H')) >= $SUNDAY_START_HOUR) || (date('D') == "Mon" && intval(date('H')) <= $MONDAY_END_HOUR)){
		send7();
		send8();
	}
?>
