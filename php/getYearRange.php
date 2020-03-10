<?php
	require_once('orm/resources/Keychain.php');
	$dbconn = (new Keychain)->getDatabaseConnection();
	$siteID = mysqli_real_escape_string($dbconn, htmlentities($_GET["siteID"]));
  
	$query = mysqli_query($dbconn, "SELECT MIN(YEAR(LocalDate)) AS EarliestYear, MAX(YEAR(LocalDate)) AS LatestYear FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK LIKE '$siteID'");
	$row = mysqli_fetch_assoc($query);
	die("true|" . json_encode(array($row["EarliestYear"], $row["LatestYear"])));
?>
