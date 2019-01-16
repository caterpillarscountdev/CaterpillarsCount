<?php
header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	
	$site = Site::findByID("2");
	$dbconn = (new Keychain)->getDatabaseConnection();
      $emails = $sites[$i]->getAuthorityEmails();
      for($j = 0; $j < count($emails); $j++){
        $firstName = "there";
        $user = User::findByEmail($emails[$j]);
        if(is_object($user) && get_class($user) == "User"){
          $firstName = $user->getFirstName();
        }
	      if($emails[$j] == "plocharczykweb@gmail.com"){
        email4($emails[$j], "The Caterpillars Count! Season Has Begun!", $firstName);
	      }
      }

?>
