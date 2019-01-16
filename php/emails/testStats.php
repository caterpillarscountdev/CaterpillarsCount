<?php
header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	
	$site = Site::findByID("2");
	$dbconn = (new Keychain)->getDatabaseConnection();
$emails = $site->getAuthorityEmails();
     $query = mysqli_query($dbconn, "SELECT COUNT(*) AS `All`, SUM(SubmittedThroughApp) AS `App` FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE `SiteFK`='" . $site->getID() . "' AND YEAR(LocalDate)='" . (intval(date("Y")) - 1) . "'");
      mysqli_close($dbconn);
      $resultRow = mysqli_fetch_assoc($query);
      $all = intval($resultRow["All"]);
      $app = intval($resultRow["App"]);
      
      for($j = 0; $j < count($emails); $j++){
        $firstName = "there";
        $user = User::findByEmail($emails[$j]);
        if(is_object($user) && get_class($user) == "User"){
          $firstName = $user->getFirstName();
        }
        if($emails[$j] == "plocharczykweb@gmail.com"){
        if($all == 0 || $app > ($all / 2)){
          email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
        }
        else{
          email5($emails[$j], "Need Help Submitting Caterpillars Count! Surveys?", $firstName);
        }
	}
      }

?>
