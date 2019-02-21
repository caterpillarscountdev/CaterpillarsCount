<?php
	header('Access-Control-Allow-Origin: *');
  
	require_once('orm/Site.php');
  
	$siteID = intval($_GET["siteID"]);
  $site = Site::findByID($siteID);
  if(is_object($site) && get_class($site) == "Site"){
    $siteArray = array(
      "name" => $site->getName(),
      "description" => $site->getDescription(),
      "url" => $site->getURL(),
      "openToPublic" => $site->getOpenToPublic(),
      "active" => $site->getActive(),
    );
    die("true|" . json_encode($siteArray));
  }
  die("false|We could not find this site.");
?>
