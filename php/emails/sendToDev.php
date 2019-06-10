<?php
  require_once("../orm/resources/mailing.php");
  require_once("../orm/Site.php");
  
  $site = Site::findByID(60);
  email7("paul.plocharczyk@ihop.com", "This Week at " . $site->getName() . "...", 3, 120, $site->getName(), 261, 0, "aphid", 74, "ant", 50, "", 0, $site->getID()); 
?>
