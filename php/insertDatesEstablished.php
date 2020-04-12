<?php
  require_once('orm/resources/Keychain.php');

  $dbconn = (new Keychain)->getDatabaseConnection();
  
  $query = mysqli_query($dbconn, "SELECT ID FROM `Site`");
  while($row = mysqli_fetch_assoc($query)){
    $innerQuery = mysqli_query($dbconn, "SELECT LocalDate AS EarliestDate FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . intval($row["ID"]) . "' ORDER BY LocalDate ASC LIMIT 1");
    if(mysqli_num_rows($innerQuery) == 1){
      mysqli_query($dbconn, "UPDATE Site SET `DateEstablished`='" . mysqli_fetch_assoc($innerQuery)["EarliestDate"] . "' WHERE `ID`='" . $row["ID"] . "'");
    }
  }
?>
