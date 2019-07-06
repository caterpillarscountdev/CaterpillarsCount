<?php
	require_once('orm/User.php');
  
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$score = intval($_GET["score"]);
  
  	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		if($user->submitVirtualSurveyScore($score)){
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
