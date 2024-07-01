<?php
        require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
        require_once('/opt/app-root/src/php/orm/resources/mailing.php');
	$dbconn = (new Keychain)->getDatabaseConnection();
	
        $duplicates = array();
        $ids = array();
        $others = array();
        
	$query = mysqli_query($dbconn, "SELECT Survey.ID, Plant.Code, Survey.LocalDate, Survey.LocalTime, CONCAT(User.FirstName, \" \", User.LastName) AS FullName FROM Survey JOIN User ON Survey.UserFKOfObserver = User.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK<>'2' AND CORRESPONDING_OLD_DATABASE_SURVEY_ID='0' AND Survey.LocalDate >= DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY UserFKOfObserver, PlantFK, LocalDate, `LocalTime`, SubmittedThroughApp, CORRESPONDING_OLD_DATABASE_SURVEY_ID HAVING COUNT(*) > 1 ORDER BY Survey.SubmittedThroughApp DESC, Survey.SubmissionTimestamp ASC");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
                  $ids[] = $row["ID"];
                  $duplicates[] = "SURVEY ID: " . $row["ID"] . "| " . $row["Code"] . " " . $row["LocalDate"] . " " . $row["LocalTime"] . " " . $row["FullName"];
		}
	}
	  
        if(count($duplicates) > 0) {

          $query = mysqli_query($dbconn, "SELECT S1.ID, S2.ID AS Duplicate FROM Survey AS S1 LEFT JOIN Survey AS S2 ON (S1.ID != S2.ID AND S1.PlantFK = S2.PlantFK AND S1.UserFKOfObserver = S2.UserFKOfObserver AND S1.LocalDate = S2.LocalDate AND S1.LocalTime = S2.LocalTime)  WHERE S1.ID IN (" . join(", ", $ids) . ") ORDER BY S1.SubmissionTimestamp ASC");
	if(mysqli_num_rows($query) > 0){
		while($row = mysqli_fetch_assoc($query)){
                  $others[] = "SURVEY ID: " . $row["ID"] . " possible duplicate: " . $row["Duplicate"];
		}
	}
          


        $body = "<html><body><p>There may have been a timeout or some other error that caused a user to have to submit the same survey twice. The following Survey IDs should be investigated:</p>" . implode("<p>", $duplicates) . "<p><p>" . implode("<p>", $others);
        email("caterpillarscount@office.unc.edu", "Duplicate survey detected", $body );
  	}
?>
