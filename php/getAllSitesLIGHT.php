<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/Site.php');
  
  $sites = Site::findAll();
  $sitesArray = array();
  for($i = 0; $i < count($sites); $i++){
    $sitesArray[$i] = array(
      "id" => $sites[$i]->getID(),
      "name" => $sites[$i]->getName(),
      "region" => $sites[$i]->getRegion(),
    );
  }
  die("true|" . json_encode($sitesArray));
?>
