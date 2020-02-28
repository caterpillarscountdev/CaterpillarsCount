<?php
  require_once('orm/resources/Keychain.php');
  
  $dbconn = (new Keychain)->getDatabaseConnection();
  
  $date = date("Y-m-d");
  $time = date("H:i:s");
  $ip = $_SERVER['REMOTE_ADDR'];
  $page = mysqli_real_escape_string($dbconn, trim(htmlentities(rawurldecode($_GET["page"]))));
  $file = mysqli_real_escape_string($dbconn, trim(htmlentities(rawurldecode($_GET["file"]))));
  $filters = mysqli_real_escape_string($dbconn, trim(htmlentities(rawurldecode($_GET["filters"]))));
  
  mysqli_query($dbconn, "INSERT INTO Download (`Date`, `UTCTime`, `IP`, `Page`, `File`, `Filters`) VALUES ('$date', '$time', '$ip', '$page', '$file', '$filters')");
  mysqli_close($dbconn);
?>
