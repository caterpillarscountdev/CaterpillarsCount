<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
    require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$score = intval(custgetparam("score"));
	$findingPercentage = floatval(custgetparam("findingPercentage"));
	$identifyingPercentage = floatval(custgetparam("identifyingPercentage"));
	$lengthPercentage = floatval(custgetparam("lengthPercentage"));
  
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
