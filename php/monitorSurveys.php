<?php                                           \
        define('__PATH__', dirname(__FILE__));
        require_once(__PATH__.'/orm/resources/Keychain.php');
        require_once(__PATH__.'/orm/resources/mailing.php');
	require_once(__PATH__.'/orm/Site.php');
        
	$dbconn = (new Keychain)->getDatabaseConnection();
        $root = (new Keychain)->getRoot();

        $duplicates = array();
        $duplicatesObj = array();

	$query = mysqli_query($dbconn, "SELECT Survey.ID, Plant.Code, Survey.LocalDate, Survey.LocalTime, User.Email, CONCAT(User.FirstName, \" \", User.LastName) AS FullName, Plant.SiteFK FROM Survey JOIN User ON Survey.UserFKOfObserver = User.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Survey.DuplicateDetected IS NULL AND Plant.SiteFK<>'2' AND CORRESPONDING_OLD_DATABASE_SURVEY_ID='0' AND Survey.LocalDate >= DATE_SUB(NOW(), INTERVAL 1 WEEK) GROUP BY UserFKOfObserver, PlantFK, LocalDate, `LocalTime`, SubmittedThroughApp, CORRESPONDING_OLD_DATABASE_SURVEY_ID HAVING COUNT(*) > 1 ORDER BY Survey.SubmittedThroughApp DESC, Survey.SubmissionTimestamp ASC");
        //if(!$query) {
        //  custom_error_log("Query Error: " . mysqli_error($dbconn));
        //  return;
        //}
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
                  $row["duplicates"] = array();
                  $duplicatesObj[$row["ID"]] = $row;
		}
	}
	  
        if(count($duplicatesObj) > 0) {

          $query = mysqli_query($dbconn, "SELECT S1.ID, S2.ID AS Duplicate FROM Survey AS S1 LEFT JOIN Survey AS S2 ON (S1.ID != S2.ID AND S1.PlantFK = S2.PlantFK AND S1.UserFKOfObserver = S2.UserFKOfObserver AND S1.LocalDate = S2.LocalDate AND S1.LocalTime = S2.LocalTime)  WHERE S1.ID IN (" . join(", ", array_keys($duplicatesObj)) . ") ORDER BY S1.SubmissionTimestamp ASC");
          if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
                  
                  $duplicatesObj[$row["ID"]]["duplicates"][] = $row["Duplicate"];
		}
          }

          foreach ($duplicatesObj as $row){
            $row["duplicates"][] = $row["ID"];

            $message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">We recently noticed there were two surveys submitted for branch " . $row["Code"] . " on " . $date . " that were potential duplicates. Would you mind taking a look using the <a href=\"" . $root . "/manageMySurveys\" style=\"color:#70c6ff;\">Manage My Surveys</a>. page, where you can use the filters to specify the branch code " . $row["Code"] . "? If one of them was a mistake, you can simply delete that survey by clicking the small trash icon on the right side, or you can edit the survey to modify the branch code or any other survey details.<br/><br/>Thank you for your participation in the project!</div><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"><div>Potential duplicate IDs: ". $row["ID"] . ","  . implode(", ", $row["duplicates"]) . "</div></div>";

            $site = Site::findByID($row["SiteFK"]);
            $ccs = array_merge(array("caterpillarscount@office.unc.edu"), $site->getAuthorityEmails());

            emailAndCC($row["Email"], $ccs, "Duplicate surveys found", $message );

            $query = mysqli_query($dbconn, "UPDATE Survey SET DuplicateDetected = NOW() WHERE ID IN ('" . implode("', '", $row["duplicates"]) . "')");

          }

  	}
?>
