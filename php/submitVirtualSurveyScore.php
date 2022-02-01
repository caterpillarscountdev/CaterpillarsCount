<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
  
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$score = intval($_GET["score"]);
	$findingPercentage = floatval($_GET["findingPercentage"]);
	$identifyingPercentage = floatval($_GET["identifyingPercentage"]);
	$lengthPercentage = floatval($_GET["lengthPercentage"]);
  
  	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		if($user->submitVirtualSurveyScore($score, $findingPercentage, $identifyingPercentage, $lengthPercentage)){
			$comparisons = $user->compareVirtualSurveyScore($score);
			if($comparisons === false){
				die("true|" . json_encode(array(0, 0)));
			}
			die("true|" . json_encode($comparisons));
        	}
        	die("false|We could not submit that score.");
  	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
