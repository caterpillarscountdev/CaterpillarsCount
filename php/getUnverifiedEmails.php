<?php
  require_once('orm/User.php');
  require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
  $unverifiedEmails = json_decode(custgetparam("unverifiedEmails"));
  
  $actuallyUnverifiedEmails = array();
  if (is_array($unverifiedEmails)) {
  for($i = 0; $i < count($unverifiedEmails); $i++){
    $user = User::findByEmail($unverifiedEmails[$i]);
    if(!(is_object($user) && get_class($user) == "User") && User::emailIsUnvalidated($unverifiedEmails[$i])){
      $actuallyUnverifiedEmails[] = filter_var(rawurldecode($unverifiedEmails[$i]), FILTER_SANITIZE_EMAIL);
    }
  }
  die(json_encode($actuallyUnverifiedEmails));
  } else {
	 die('false|no emails specified');  
  }
?>
