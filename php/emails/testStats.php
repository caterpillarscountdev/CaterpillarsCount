<?php
header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	$monday = "2018-10-22";
     	
	$user = User::findByID("25");
	if(is_object($user) && get_class($user) == "User"){
		$query = mysqli_query($dbconn, "SELECT DISTINCT(Plant.SiteFK) AS SiteID FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.LocalDate>='$monday' AND Survey.UserFKOfObserver='" . $user->getID() . "'");
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

		email8($user->getEmail(), "Check Your Caterpillars Count! Data from This Week!", $sites, $arthropodCount, $caterpillarCount, $user->getINaturalistObserverID(), $userHasINaturalistObservations);
	}
?>
