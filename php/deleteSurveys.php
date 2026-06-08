<?php
	require_once('orm/User.php');
	require_once('orm/Survey.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$selected = json_decode(custgetparam("selected"));
	$unselected = json_decode(custgetparam("unselected"));
	$filters = json_decode(rawurldecode(custgetparam("filters")), true);

	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$userSites = $user->getSites();
		$userSiteIDs = array();
		for($i = 0; $i < count($userSites); $i++){
			$userSiteIDs[] = $userSites[$i]->getID();
		}
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		$tryingToDeleteCount = (count($selected) - count($unselected));
		$selected[] = "-1";
		$userSiteIDs[] = "-1";
		mysqli_query($dbconn, "DELETE ArthropodSighting FROM ArthropodSighting JOIN Survey on ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK=Site.ID WHERE Survey.ID IN (" . join(", ", $selected) . ") AND (Site.ID IN (" . join(", ", $userSiteIDs) . ") OR (Survey.UserFKOfObserver='" . $user->getID() . "' AND Survey.SubmissionTimestamp>='" . (time() - (2 * 7 * 24 * 60 * 60)) . "'))");
		mysqli_query($dbconn, "DELETE Survey FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK=Site.ID WHERE Survey.ID IN (" . join(", ", $selected) . ") AND (Site.ID IN (" . join(", ", $userSiteIDs) . ") OR (Survey.UserFKOfObserver='" . $user->getID() . "' AND Survey.SubmissionTimestamp>='" . (time() - (2 * 7 * 24 * 60 * 60)) . "'))");
		$successes = mysqli_affected_rows($dbconn);
		
		if($successes < $tryingToDeleteCount){
			if(count($tryingToDeleteCount) < 2){
				die("false|You do not have the authority to delete this survey.");
			}
			else if($successes == 1){
				die("false|Only " . $successes . " survey was successfully deleted. You do not have the authority to delete " . ($tryingToDeleteCount - $successes) . " of the surveys you tried to delete.");
			}
			else if($successes > 1){
				die("false|Only " . $successes . " surveys were successfully deleted. You do not have the authority to delete " . ($tryingToDeleteCount - $successes) . " of the surveys you tried to delete.");
			}
		}
		die("true|" . $successes);
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
