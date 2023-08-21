<?php
  require_once('orm/resources/Keychain.php');
  require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
  $dbconn = (new Keychain)->getDatabaseConnection();
  
  $date = date("Y-m-d");
  $time = date("H:i:s");
  $ip = $_SERVER['REMOTE_ADDR'];
  $page = mysqli_real_escape_string($dbconn, trim(htmlentities(rawurldecode(custgetparam("page")))));
  $file = mysqli_real_escape_string($dbconn, trim(htmlentities(rawurldecode(custgetparam("file")))));
  $filters = mysqli_real_escape_string($dbconn, trim(htmlentities(rawurldecode(custgetparam("filters")))));
  
  mysqli_query($dbconn, "INSERT INTO Download (`Date`, `UTCTime`, `IP`, `Page`, `File`, `Filters`) VALUES ('$date', '$time', '$ip', '$page', '$file', '$filters')");
  mysqli_close($dbconn);
?>
