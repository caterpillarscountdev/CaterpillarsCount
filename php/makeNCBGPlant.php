<?php
  require_once("orm/Site.php");
  require_once("orm/Plant.php");
  
  $site = Site::findByID("78");
  Plant::create($site, "6", "D");
?>
