<?php
	require_once('orm/User.php');
	require_once('orm/Survey.php');

	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$surveyID = $_GET["surveyID"];

	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		if(User::isSuperUser($user)){
			$survey = Survey::findByID($surveyID);
			if(is_object($survey) && get_class($survey) == "Survey"){
				if($survey->setReviewedAndApproved(true)){
					die("true|");
				}
				die("false|Could not approve survey. Please try again.");
			}
			die("false|Could not find survey. Please refresh the page and try again.");
		}
		die("false|You do not have authority to approve this survey.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>