<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once("../orm/Site.php");
  require_once("../orm/User.php");
  require_once("../orm/resources/Keychain.php");
  require_once("../orm/resources/mailing.php");
  
  $sites = Site::findAll();
  for($i = 0; $i < count($sites); $i++){
    if($sites[$i]->getActive() && $sites[$i]->getLatitude() < 36.5 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
      $emails = $sites[$i]->getAuthorityEmails();
      
      $dbconn = (new Keychain)->getDatabaseConnection();
      $query = mysqli_query($dbconn, "SELECT COUNT(*) AS `All`, SUM(SubmittedThroughApp) AS `App` FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $sites[$i]->getID() . "' AND YEAR(LocalDate)='" . (intval(date("Y")) - 1) . "'");
      mysqli_close($dbconn);
      $resultRow = mysqli_fetch_assoc($query);
      $all = intval($resultRow["All"]);
      $app = intval($resultRow["App"]);
      
      for($j = 0; $j < count($emails); $j++){
        $firstName = "there";
        $user = User::findByEmail($emails[$j]);
        if(is_object($user) && get_class($user) != "User"){
          $firstName = $user->getFirstName();
        }
        
        if($all == 0 || $app > ($all / 2)){
          email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
        }
        else{
          email5($emails[$j], "Need Help Submitting Caterpillars Count! Surveys?", $firstName);
        }
      }
    }
    else if($sites[$i]->getActive() && $sites[$i]->getLatitude() >= 36.5 && $sites[$i]->getLatitude() < 40.7 && $sites[$i]->getNumberOfSurveysByYear(date("Y")) == 0){
      $emails = $sites[$i]->getAuthorityEmails();
      for($j = 0; $j < count($emails); $j++){
        $firstName = "there";
        $user = User::findByEmail($emails[$j]);
        if(is_object($user) && get_class($user) != "User"){
          $firstName = $user->getFirstName();
        }
        email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
      }
    }
  }
?>
