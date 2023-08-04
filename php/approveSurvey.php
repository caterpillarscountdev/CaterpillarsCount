<?php
	require_once('orm/User.php');
	require_once('orm/Survey.php');
    require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email"); // new function to simplify handling if param exists or not for php 8
	$salt = custgetparam("salt");
	$surveyID = custgetparam("surveyID");
	$approvedLevel = custgetparam("approvedLevel");
	$qccomment = custgetparam("qccomment");
    if (!empty($approvedLevel)) {
		$user = User::findBySignInKey($email, $salt);
		if(is_object($user) && get_class($user) == "User"){
			if(User::isSuperUser($user)){
				$survey = Survey::findByID($surveyID);
				if(is_object($survey) && get_class($survey) == "Survey"){
					if($survey->setReviewedAndApproved($approvedLevel, $qccomment)){
						die("true|");
					}
					die("false|Could not approve survey. Please try again.");
				}
				die("false|Could not find survey. Please refresh the page and try again.");
			}
			die("false|You do not have authority to approve this survey.");
		}
		die("false|Your log in dissolved. Maybe you logged in on another device.");
		}
	die("false|No approval level was specified");
?>
