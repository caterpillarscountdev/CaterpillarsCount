<?php
  require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
  
  $dbconn = (new Keychain)->getDatabaseConnection();
  
  $iNaturalistStatsByUserID = array();
  $query = mysqli_query($dbconn, "SELECT Survey.UserFKOfObserver AS UserID, COUNT(*) AS Total FROM `ArthropodSighting` JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID WHERE ArthropodSighting.INaturalistID<>'' GROUP BY Survey.UserFKOfObserver;");
  while($row = mysqli_fetch_assoc($query)){
    $iNaturalistStatsByUserID[strval($row["UserID"])] = array(
      "total" => intval($row["Total"]),
      "supportingTotal" => 0,
      "overturnedTotal" => 0
    );
  }

  $query = mysqli_query($dbconn, "SELECT Survey.UserFKOfObserver AS UserID, COUNT(*) AS SupportingTotal FROM `ExpertIdentification` JOIN ArthropodSighting ON ExpertIdentification.ArthropodSightingFK=ArthropodSighting.ID JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID WHERE (ExpertIdentification.OriginalGroup=ExpertIdentification.StandardGroup OR (ExpertIdentification.OriginalGroup IN ('other', 'unidentified') AND (ExpertIdentification.StandardGroup NOT IN ('ant', 'aphid', 'bee', 'beetle', 'caterpillar', 'daddylonglegs', 'fly', 'grasshopper', 'leafhopper', 'moths', 'spider', 'truebugs')))) GROUP BY Survey.UserFKOfObserver;");
  while($row = mysqli_fetch_assoc($query)){
    $iNaturalistStatsByUserID[strval($row["UserID"])]["supportingTotal"] = intval($row["SupportingTotal"]);
  }

  $query = mysqli_query($dbconn, "SELECT Survey.UserFKOfObserver AS UserID, COUNT(*) AS ExpertTotal FROM `ExpertIdentification` JOIN ArthropodSighting ON ExpertIdentification.ArthropodSightingFK=ArthropodSighting.ID JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID GROUP BY Survey.UserFKOfObserver;");
  while($row = mysqli_fetch_assoc($query)){
    $iNaturalistStatsByUserID[strval($row["UserID"])]["overturnedTotal"] = intval($row["ExpertTotal"]) - $iNaturalistStatsByUserID[strval($row["UserID"])]["SupportingTotal"];
  }

  $updateMySQL = "";
  foreach($iNaturalistStatsByUserID as $userID => $statsArray){
    $updateMySQL .= "UPDATE `User` SET `INaturalistSubmissions`='" . $statsArray["total"] . "', `SupportedINaturalistSubmissions`='" . $statsArray["supportingTotal"] . "', `OverturnedINaturalistSubmissions`='" . $statsArray["overturnedTotal"] . "', `INaturalistSubmissionsLastUpdated`=NOW() WHERE `ID`='$userID';";
  }

  if($updateMySQL != ""){
    $query = mysqli_multi_query($dbconn, $updateMySQL);
    while(mysqli_more_results($dbconn)){$temp = mysqli_next_result($dbconn);}
  }
  
  mysqli_close($dbconn);
?>
