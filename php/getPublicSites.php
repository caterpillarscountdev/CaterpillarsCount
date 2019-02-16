<?php
	require_once("orm/Site.php");
	
	$publicSites = Site::findAllActivePublicSites();
	$publicSitesArray = array();
	for($i = 0; $i < count($publicSites); $i++){
		//send email in parts to avoid spam spiders
		$email = $publicSites[$i]->getCreator()->getEmail();
		$atIndex = strrpos($email, "@");
		$beforeAt = substr($email, 0, $atIndex);
		$afterAt = substr($email, ($atIndex + 1));
		
		$publicSitesArray[$i] = array(
			"creatorEmailParts" => array($beforeAt, $afterAt),
			"siteName" => $publicSites[$i]->getName(),
			"description" => $publicSites[$i]->getDescription(),
			"url" => $publicSites[$i]->getURL(),
			"region" => $publicSites[$i]->getRegion(),
			"latitude" => $publicSites[$i]->getLatitude(),
			"longitude" => $publicSites[$i]->getLongitude(),
		);
	}
	
	/*
	//GRANDFATHER IN OLD, INACTIVE SITE:
	$staticSites = array(
		array(
			"creatorEmailParts" => array("max.cawley", "lifeandscience.org"),
		    	"siteName" => "North Carolina Museum of Life and Science",
		    	"description" => "Our interactive science park includes a two-story science center, one of the largest butterfly conservatories on the East Coast and beautifully landscaped outdoor exhibits",
		    	"region" => "NC",
		    	"latitude" => 36.0294,
		    	"longitude" => -78.9016,
		),
	);

	$publicSitesArray = array_merge($publicSitesArray, $staticSites);
	*/

	die(json_encode($publicSitesArray));
?>
