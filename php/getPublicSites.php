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

	die(json_encode($publicSitesArray));
?>
