<?php
	require_once('orm/Site.php');
	require_once('orm/Plant.php');
	require_once('orm/Survey.php');
	require_once('orm/ArthropodSighting.php');
	require_once('orm/ManagerRequest.php');
	require_once('orm/resources/Keychain.php');

	$dbconn = (new Keychain)->getDatabaseConnection();

	echo "DELETING PLANTS from sites that no longer exist...<br/>";
	Plant::permanentDeleteAllLooseEnds();

	echo "DONE. <br/><br/>DELETING SURVEYS from plants that no longer exist...<br/>";
	Survey::permanentDeleteAllLooseEnds();

	echo "DONE. <br/><br/>DELETING ARTHROPOD SIGHTINGS from surveys that no longer exist...<br/>";
	ArthropodSighting::permanentDeleteAllLooseEnds();

	echo "DONE. <br/><br/>DELETING MANAGER REQUESTS from sites and users that no longer exist...<br/>";
	ManagerRequest::permanentDeleteAllLooseEnds();

	echo "DONE. <br/><br/>DELETING OBSERVATION METHOD PRESETS from sites and users that no longer exist...<br/>";
	$query = mysqli_query($dbconn, "SELECT `SiteUserPreset`.`ID` FROM `SiteUserPreset` LEFT JOIN `Site` ON `SiteUserPreset`.`SiteFK`=`Site`.`ID` LEFT JOIN `User` ON `SiteUserPreset`.`UserFK`=`User`.`ID` WHERE `Site`.`ID` IS NULL OR `User`.`ID` IS NULL");
	$idsToDelete = array();
	while($row = mysqli_fetch_assoc($query)){
		$idsToDelete[] = $row["ID"];
	}

	if(count($idsToDelete) > 0){
		mysqli_query($dbconn, "DELETE FROM `SiteUserPreset` WHERE `ID` IN ('" . implode("', '", $idsToDelete) . "')");
	}

	echo "DONE. <br/><br/>DELETING AUTOMATIC SITE VALIDATIONS from sites and users that no longer exist...<br/>";
	$query = mysqli_query($dbconn, "SELECT `SiteUserValidation`.`ID` FROM `SiteUserValidation` LEFT JOIN `Site` ON `SiteUserValidation`.`SiteFK`=`Site`.`ID` LEFT JOIN `User` ON `SiteUserValidation`.`UserFK`=`User`.`ID` WHERE `Site`.`ID` IS NULL OR `User`.`ID` IS NULL");
	$idsToDelete = array();
	while($row = mysqli_fetch_assoc($query)){
		$idsToDelete[] = $row["ID"];
	}

	if(count($idsToDelete) > 0){
		mysqli_query($dbconn, "DELETE FROM `SiteUserValidation` WHERE `ID` IN ('" . implode("', '", $idsToDelete) . "')");
	}

	echo "DONE. <br/><br/>DELETING DISPUTED IDENTIFICATIONS from arthropod sightings that no longer exist...<br/>";
	$query = mysqli_query($dbconn, "SELECT `DisputedIdentification`.`ID` FROM `DisputedIdentification` LEFT JOIN `ArthropodSighting` ON `DisputedIdentification`.`ArthropodSightingFK`=`ArthropodSighting`.`ID` WHERE `ArthropodSighting`.`ID` IS NULL");
	$idsToDelete = array();
	while($row = mysqli_fetch_assoc($query)){
		$idsToDelete[] = $row["ID"];
	}

	if(count($idsToDelete) > 0){
		mysqli_query($dbconn, "DELETE FROM `DisputedIdentification` WHERE `ID` IN ('" . implode("', '", $idsToDelete) . "')");
	}

	echo "DONE. <br/><br/>DELETING EXPERT IDENTIFICATIONS from arthropod sightings that no longer exist...<br/>";
	$query = mysqli_query($dbconn, "SELECT `ExpertIdentification`.`ID` FROM `ExpertIdentification` LEFT JOIN `ArthropodSighting` ON `ExpertIdentification`.`ArthropodSightingFK`=`ArthropodSighting`.`ID` WHERE `ArthropodSighting`.`ID` IS NULL");
	$idsToDelete = array();
	while($row = mysqli_fetch_assoc($query)){
		$idsToDelete[] = $row["ID"];
	}

	if(count($idsToDelete) > 0){
		mysqli_query($dbconn, "DELETE FROM `ExpertIdentification` WHERE `ID` IN ('" . implode("', '", $idsToDelete) . "')");
	}

	echo "DONE. <br/><br/>DELETING TEMPORARY EXPERT IDENTIFICATION CHANGE LOGS from arthropod sightings that no longer exist...<br/>";
	$query = mysqli_query($dbconn, "SELECT `TemporaryExpertIdentificationChangeLog`.`ID` FROM `TemporaryExpertIdentificationChangeLog` LEFT JOIN `ArthropodSighting` ON `TemporaryExpertIdentificationChangeLog`.`ArthropodSightingFK`=`ArthropodSighting`.`ID` WHERE `ArthropodSighting`.`ID` IS NULL");
	$idsToDelete = array();
	while($row = mysqli_fetch_assoc($query)){
		$idsToDelete[] = $row["ID"];
	}

	if(count($idsToDelete) > 0){
		mysqli_query($dbconn, "DELETE FROM `TemporaryExpertIdentificationChangeLog` WHERE `ID` IN ('" . implode("', '", $idsToDelete) . "')");
	}

	mysqli_close($dbconn);

	echo "DONE. <br/><br/>All data from sites that no longer exist has been deleted.";
?>
