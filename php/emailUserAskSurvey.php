<?php
	require_once('orm/User.php');
	require_once('orm/resources/mailing.php');
    require_once('orm/resources/Customlogging.php');
	require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
	$email = custgetparam("email");
	$salt = custgetparam("salt");
	$emailto = custgetparam("emailto");
	$emailmessage = custgetparam("emailmessage");
    $user = User::findBySignInKey($email, $salt);
	//custom_error_log('email to ' . $emailto);
	if(is_object($user) && get_class($user) == "User"){
		if(User::isSuperUser($user)){
			if (email($emailto,"QC issue with your Survey",$emailmessage)) {
			  die("true|");	 
			} else {
		      die("false|Email failed to send.");		
			}
		}
		die("false|You do not have authority to approve this survey.");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
		
?>
