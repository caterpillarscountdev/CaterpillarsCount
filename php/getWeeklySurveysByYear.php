<?php
	header('Access-Control-Allow-Origin: *');
  	
	require_once('orm/resources/Keychain.php');
	require_once('orm/User.php');
	require_once('orm/Site.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$year = intval($_GET["year"]);
	$start = intval($_GET["start"]);
	$LIMIT = 500;
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$query = mysqli_query($dbconn, "SELECT `SiteFK` FROM `ManagerRequest` WHERE `UserFKOfManager`='" . $user->getID() . "' AND `Status`='Approved'");
		$siteIDs = array(0);
		while($row = mysqli_fetch_assoc($query)){
			$siteIDs[] = intval($row["SiteFK"]);
		}
		$query = mysqli_query($dbconn, "SELECT * FROM `Site` WHERE `UserFKOfCreator`='" . $user->getID() . "'");
		while($row = mysqli_fetch_assoc($query)){
			$siteID = intval($row["ID"]);
			if(!in_array($siteID, $siteIDs)){
				$siteIDs[] = $siteID;
			}
		}

		$userRestriction = " WHERE ID IN (" . implode(", ", $siteIDs) . ")";
		if(User::isSuperUser($user)){
			$userRestriction = "";
		}
		
		$data = array();
		$query = mysqli_query($dbconn, "SELECT WEEK(\"" . $year . "-01-01\") AS StartWeek, WEEK(\"" . $year . "-12-31\") AS EndWeek");
		$row = mysqli_fetch_assoc($query);
		$startWeek = intval($row["StartWeek"]);
		$endWeek = intval($row["EndWeek"]);
		$siteIDsThisIteration = array();
		$lastCall = ($start + $LIMIT) >= intval(mysqli_fetch_assoc(mysqli_query($dbconn, "SELECT COUNT(*) AS Count FROM Site" . $userRestriction))["Count"]);

		$sitesQuery = mysqli_query($dbconn, "SELECT ID FROM Site" . $userRestriction . " LIMIT $start, $LIMIT");
		if(mysqli_num_rows($sitesQuery) > 0){
			while($row = mysqli_fetch_assoc($sitesQuery)){
				$surveysEachWeek = array();
				for($i = $startWeek; $i <= $endWeek; $i++){
					$surveysEachWeek[] = 0;
				}

				if(intval($row["ID"]) != 2){
					$siteIDsThisIteration[] = $row["ID"];
					$data[(string)$row["ID"]] = $surveysEachWeek;
				}
				else if(count($sites) == 1){
					die("true|" . json_encode(array($data, true)));
				}
			}
		}

		$query = mysqli_query($dbconn, "SELECT Plant.SiteFK, WEEK(Survey.LocalDate, 1) AS Week, COUNT(*) AS SurveyCount FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE YEAR(Survey.LocalDate)='$year' AND Plant.SiteFK IN (" . implode(", ", $siteIDsThisIteration) . ") GROUP BY CONCAT(Plant.SiteFK, ' ', WEEK(Survey.LocalDate, 1))");
		if(mysqli_num_rows($query) > 0){
			while($row = mysqli_fetch_assoc($query)){
				$data[(string)$row["SiteFK"]][intval($row["Week"]) - $startWeek] = intval($row["SurveyCount"]);
			}
		}

		die("true|" . json_encode(array($data, $lastCall)));
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
