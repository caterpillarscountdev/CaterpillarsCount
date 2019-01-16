<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once("../orm/Site.php");
  require_once("../orm/User.php");
  require_once("../orm/resources/mailing.php");
  
  $sites = Site::findAll();
  for($i = 0; $i < count($sites); $i++){
    if($sites[$i]->getActive() && $sites[$i]->getLatitude() < 36.5 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
      $emails = $sites[$i]->getAuthorityEmails();
      for($j = 0; $j < count($emails); $j++){
        $firstName = "there";
        $user = User::findByEmail($email[$j]);
        if(is_object($user) && get_class($user) != "User"){
          $firstName = $user->getFirstName();
        }
        email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
      }
    }
  }
?>
