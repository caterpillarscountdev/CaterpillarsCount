<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once("../orm/Site.php");
	require_once("../orm/User.php");
	require_once("../orm/resources/Keychain.php");
	require_once("../orm/resources/mailing.php");
	
	$dbconn = (new Keychain)->getDatabaseConnection();
	$query = mysqli_query($dbconn, "SELECT DISTINCT Plant.SiteFK FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='" . date("Y") . "'");
	while($siteRow = mysqli_fetch_assoc($query)){
		$site = Site::findByID($siteRow["SiteFK"]);
		echo $site->getName() . "<br/>";
	}
	mysqli_close($dbconn);
?>
