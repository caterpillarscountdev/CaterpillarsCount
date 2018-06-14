<?php
	require_once('orm/User.php');
	require_once('orm/Survey.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$selected = json_decode($_GET["selected"]);
	$unselected = json_decode($_GET["unselected"]);
	$filters = json_decode(rawurldecode($_GET["filters"]), true);

	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$successes = 0;
		$failures = 0;
		
		$userSites = $user->getSites();
		$userSiteIDs = array();
		for($i = 0; $i < count($userSites); $i++){
			$userSiteIDs[] = $userSites[$i]->getID();
		}
	
		/*
		if($selected == "all"){
			$surveys = Survey::findSurveysByUser($user, $filters, 0, "max")[1];//9999999999999999999);//this might cause a timeout
			$selected = array();
			for($i = 0; $i < count($surveys); $i++){
				$surveyID = $surveys[$i]->getID() . "";
				if(!in_array($surveyID, $unselected)){
					$selected[] = $surveyID;
				}
			}
		}
		*/
		
		if(!($surveys[$i]->getPlant()->getSite()->isAuthority($user) || $surveys[$i]->getSubmissionTimestamp() >= (time() - (2 * 7 * 24 * 60 * 60)) || $user->getEmail() == "plocharczykweb@gmail.com" || $user->getEmail() == "hurlbert@bio.unc.edu")){
			die("false|For sites that you do not own or manage, you only have 2 weeks after submitting a survey to come back and edit it. You can no longer edit this survey. If you really must edit this survey, ask your site director to do so for you.");
		}
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		mysqli_query($dbconn, "DELETE ArthropodSighting FROM ArthropodSighting JOIN Survey on ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK=Site.ID WHERE Survey.ID IN (-1, " . join(", ", $selected) . ") AND (Site.ID IN (-1, " . join(", ", $userSiteIDs) . ") OR (Survey.UserFKOfObserver='" . $user->getID() . "' AND Survey.SubmissionTimestamp>='" . (time() - (2 * 7 * 24 * 60 * 60)) . "'))");
		mysqli_query($dbconn, "DELETE Survey FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK=Site.ID WHERE Survey.ID IN (-1, " . join(", ", $selected) . ") AND (Site.ID IN (-1, " . join(", ", $userSiteIDs) . ") OR (Survey.UserFKOfObserver='" . $user->getID() . "' AND Survey.SubmissionTimestamp>='" . (time() - (2 * 7 * 24 * 60 * 60)) . "'))");
		$successes = mysqli_affected_rows($dbconn);
		$failures = intval(mysqli_fetch_assoc(mysqli_query($dbconn, "SELECT COUNT(*) AS `Count` FROM Survey WHERE Survey.ID IN (-1, " . join(", ", $selected) . ")"))["Count"]);
		
		if($failures > 0){
			if(count($surveyIDs) < 2){
				die("false|You do not have the authority to delete this survey.");
			}
			else if($successes == 1){
				die("false|Only " . $successes . " survey was successfully deleted. You do not have the authority to delete " . $failures . " of the surveys you tried to delete.");
			}
			else if($successes > 1){
				die("false|Only " . $successes . " surveys were successfully deleted. You do not have the authority to delete " . $failures . " of the surveys you tried to delete.");
			}
		}
		die("true|" . $successes);
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
