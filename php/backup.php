<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once('orm/resources/Keychain.php');

  ini_set('memory_limit', '-1');

  function createCSV($tableName) {
    $dbconn = (new Keychain)->getDatabaseConnection();
    
    $headerSQLString = "";
    $query = mysqli_query($dbconn, "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='CaterpillarsCount' AND `TABLE_NAME`='" . $tableName . "'");
    $colHeaders = array();
    while ($row = mysqli_fetch_assoc($query)) $colHeaders[] = $row["COLUMN_NAME"];
    if(count($colHeaders) > 0){
      $headerSQLString = "SELECT " . implode(", ", $colHeaders) . " UNION ALL ";
    }
    
    $directory = "backupsManual";
    if($tableName == "User"){
      return false;
      $directory = getenv("USER_BACKUPS");
    }
    
    mysqli_query($dbconn, $headerSQLString . "SELECT * INTO OUTFILE '../" . $directory . "/" . date("Y-m-d") . "_" . $tableName . ".csv' FROM `" . $tableName . "`");
    mysqli_close($dbconn);
  }

  //backup
  $dbconn = (new Keychain)->getDatabaseConnection();
  $query = mysqli_query($dbconn, "SELECT table_name FROM information_schema.tables WHERE table_schema='CaterpillarsCount' AND table_name<>'TemporaryEmailLog'");
  mysqli_close($dbconn);  
  while($row = mysqli_fetch_assoc($query)){
    createCSV($row["table_name"]);
  }
?>
