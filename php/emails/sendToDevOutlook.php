<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once("../orm/Site.php");
  require_once("../orm/resources/mailing.php");

  email8("paul.plocharczyk@ihop.com", "Your Caterpillars Count! weekly summary", array(Site::findByID("2")), 57, 3, "hurlbert", true);
?>
