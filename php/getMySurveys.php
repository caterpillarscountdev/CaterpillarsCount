<?php
	require_once('orm/User.php');
	require_once('orm/Survey.php');
	require_once('orm/Site.php');
	require_once('orm/ArthropodSighting.php');
	require_once('orm/resources/Keychain.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$page = custgetparam("page");
	$filters = json_decode(rawurldecode(custgetparam("filters")), true);
	$inQCMode = filter_var(custgetparam("inQCMode"), FILTER_VALIDATE_BOOLEAN);
	$PAGE_LENGTH = 25;
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$start = "last";
		if($page !== "last"){
			$start = ((intval($page) - 1) * $PAGE_LENGTH);
		}
		
		$surveys = $inQCMode ? Survey::findSurveysByFlagged($user, $start, $PAGE_LENGTH) : Survey::findSurveysByUser($user, $filters, $start, $PAGE_LENGTH);
		$totalCount = $surveys[0];
		$totalPages = ceil($totalCount/$PAGE_LENGTH);
		$surveys = $surveys[1];
		for($i = count($surveys) - 1; $i >= 0; $i--){
			if(!is_object($surveys[$i]) || get_class($surveys[$i]) != "Survey"){
				unset($surveys[$i]);
			}
		}
		$arthropodSightings  = ArthropodSighting::findArthropodSightingsBySurveys($surveys);
		$surveysArray = array();
		for($i = 0; $i < count($surveys); $i++){
			if(is_object($surveys[$i]) && get_class($surveys[$i]) == "Survey"){
				$arthropodSightingsArray = array();
				for($j = 0; $j < count($arthropodSightings); $j++){
					if(is_object($arthropodSightings[$j]) && get_class($arthropodSightings[$j]) == "ArthropodSighting" && $arthropodSightings[$j]->getSurvey()->getID() === $surveys[$i]->getID()){
						$arthropodSightingsArray[] = array(
							"id" => $arthropodSightings[$j]->getID(),
							"originalGroup" => $arthropodSightings[$j]->getOriginalGroup(),
							"updatedGroup" => $arthropodSightings[$j]->getUpdatedGroup(),
							"length" => $arthropodSightings[$j]->getLength(),
							"quantity" => $arthropodSightings[$j]->getQuantity(),
							"photoURL" => $arthropodSightings[$j]->getPhotoURL(),
							"notes" => $arthropodSightings[$j]->getNotes(),
							"pupa" => $arthropodSightings[$j]->getPupa(),
							"hairy" => $arthropodSightings[$j]->getHairy(),
							"rolled" => $arthropodSightings[$j]->getRolled(),
							"tented" => $arthropodSightings[$j]->getTented(),
							"originalSawfly" => $arthropodSightings[$j]->getOriginalSawfly(),
							"updatedSawfly" => $arthropodSightings[$j]->getUpdatedSawfly(),
							"originalBeetleLarva" => $arthropodSightings[$j]->getOriginalBeetleLarva(),
							"updatedBeetleLarva" => $arthropodSightings[$j]->getUpdatedBeetleLarva(),
							"iNaturalistObservationURL" => $arthropodSightings[$j]->getINaturalistObservationURL(),
						);
					}
				}
				$surveysArray[] = array(
					"id" => $surveys[$i]->getID(),
					"editable" => ($surveys[$i]->getPlant()->getSite()->isAuthority($user) || $surveys[$i]->getSubmissionTimestamp() >= (time() - (2 * 7 * 24 * 60 * 60)) || User::isSuperUser($user->getEmail())),
					"observerID" => $surveys[$i]->getObserver()->getID(),
					"observerFullName" => $surveys[$i]->getObserver()->getFullName(),
					"observerEmail" => $surveys[$i]->getObserver()->getEmail(),
					"plantCode" => $surveys[$i]->getPlant()->getCode(),
					"siteID" => $surveys[$i]->getPlant()->getSite()->getID(),
					"siteName" => $surveys[$i]->getPlant()->getSite()->getName(),
					"siteRegion" => $surveys[$i]->getPlant()->getSite()->getRegion(),
					"siteCoordinates" => $surveys[$i]->getPlant()->getSite()->getLatitude() . "," . $surveys[$i]->getPlant()->getSite()->getLongitude(),
					"circle" => $surveys[$i]->getPlant()->getCircle(),
					"orientation" => $surveys[$i]->getPlant()->getOrientation(),
					"color" => $surveys[$i]->getPlant()->getColor(),
					"localDate" => $surveys[$i]->getLocalDate(),
					"localTime" => $surveys[$i]->getLocalTime(),
					"observaionMethod" => $surveys[$i]->getObservationMethod(),
					"notes" => $surveys[$i]->getNotes(),
					"wetLeaves" => $surveys[$i]->getWetLeaves(),
					"plantSpecies" => $surveys[$i]->getPlantSpecies(),
					"numberOfLeaves" => $surveys[$i]->getNumberOfLeaves(),
					"averageLeafLength" => $surveys[$i]->getAverageLeafLength(),
					"herbivoryScore" => $surveys[$i]->getHerbivoryScore(),
					"averageNeedleLength" => $surveys[$i]->getAverageNeedleLength(),
					"linearBranchLength" => $surveys[$i]->getLinearBranchLength(),
					"submittedThroughApp" => $surveys[$i]->getSubmittedThroughApp(),
					"arthropodSightings" => $arthropodSightingsArray,
					"flags" => $surveys[$i]->getFlags()["text"]
				);
			}
		}
		$sites = $user->getSites();
		for($i = 0; $i < count($sites); $i++){
			$sites[$i] = array($sites[$i]->getID(), $sites[$i]->getName());
		}
		
		$isSiteAuthority = (count($sites) > 0);
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT Site.ID, Site.Name FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK=Site.ID WHERE Survey.UserFKOfObserver='" . $user->getID() . "' GROUP BY Plant.SiteFK");
		while($siteRow = mysqli_fetch_assoc($query)){
			$siteID = intval($siteRow["ID"]);
			$siteIsAlreadyInArray = false;
			for($i = 0; $i < count($sites); $i++){
				if($sites[$i][0] === $siteID){
					$siteIsAlreadyInArray = true;
					break;
				}
			}
			
			if(!$siteIsAlreadyInArray){
				$sites[] = array($siteID, $siteRow["Name"]);
			}
		}
		
		$sitesArray = array();
		$query = mysqli_query($dbconn, "SELECT * FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `UserFKOfObserver`='" . $user->getID() . "' AND PhotoURL<>'' AND SiteFK<>'2' LIMIT 1");
		$userHasINaturalistObservations = (mysqli_num_rows($query) > 0);
		mysqli_close($dbconn);
		for($i = 0; $i < count($sites); $i++){
			$siteName = $sites[$i][1];
			if($siteName != "Example Site"){
				$sitesArray[] = array($siteName);
			}
		}
		die("true|" . json_encode(array($totalCount, $totalPages, $surveysArray, $isSiteAuthority, $sitesArray, $user->getINaturalistObserverID(), $userHasINaturalistObservations)));
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
