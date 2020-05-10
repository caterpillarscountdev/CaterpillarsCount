<?php
  require_once('orm/User.php');
  
  $unverifiedEmails = json_decode($_GET["unverifiedEmails"]);
  
  $actuallyUnverifiedEmails = array();
  for($i = 0; $i < count($unverifiedEmails); $i++){
    $user = User::findByEmail($unverifiedEmails[$i]);
    if(!is_object($user) || get_class($user) !== "User" || !(User::emailIsUnvalidated($unverifiedEmails[$i]))){
      $actuallyUnverifiedEmails[] = filter_var(rawurldecode($unverifiedEmails[$i]), FILTER_SANITIZE_EMAIL);
    }
  }
  die(json_encode($actuallyUnverifiedEmails));
?>
