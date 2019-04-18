<?php
  require_once('orm/resources/Keychain.php');
  
  //purposeful error
  sdfsdf
  
  $dbconn = (new Keychain)->getDatabaseConnection();
  $query1 = mysqli_query($dbconn, "SELECT * FROM `ArthropodSighting`");
  while($row1 = mysqli_fetch_assoc($query1)){
    $query2 = mysqli_query($dbconn, "SELECT * FROM `Survey` WHERE ID='" . $row1["SurveyFK"] . "'");
    $row2 = mysqli_fetch_assoc($query2);
    $query3 = mysqli_query($dbconn, "SELECT * FROM `Plant` WHERE ID='" . $row2["PlantFK"] . "'");
    $row3 = mysqli_fetch_assoc($query3);
    $query4 = mysqli_query($dbconn, "SELECT * FROM `Site` WHERE ID='" . $row3["SiteFK"] . "'");
    $row4 = mysqli_fetch_assoc($query4);
    
    $query5 = mysqli_query($dbconn, "SELECT * FROM `User`");
    while($row5 = mysqli_fetch_assoc($query5)){
      $query6 = mysqli_query($dbconn, "SELECT * FROM `Survey` WHERE UserFKOfObserver='" . $row5["ID"] . "'");
      while($row6 = mysqli_fetch_assoc($query6)){
        $val = $row6["ID"];
      }
    }
  }
?>
