<?php
  header('Access-Control-Allow-Origin: *');

  require_once('orm/resources/Keychain.php');

  ini_set('memory_limit', '-1');

  function getArrayFromTable($tableName){
    $tableArray = array();

    $dbconn = (new Keychain)->getDatabaseConnection();
    
    //HEADERS
    $query = mysqli_query($dbconn, "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='CaterpillarsCount' AND `TABLE_NAME`='" . $tableName . "'");
    $colHeaders = array();
    while ($row = mysqli_fetch_assoc($query)) $colHeaders[] = $row["COLUMN_NAME"];
    $tableArray[] = $colHeaders;
    
    //ROWS
    $query = mysqli_query($dbconn, "SELECT * FROM `" . $tableName . "`");
    mysqli_close($dbconn);
    
    while ($row = mysqli_fetch_assoc($query)){
      $rowArray = array();
      for($i = 0; $i < count($colHeaders); $i++){
        $rowArray[] = $row[$colHeaders[$i]];
      }
      $tableArray[] = $rowArray;
    }
    return $tableArray;
  }

  function createCSV($tableName, $tableArray) {
    $directory = "backups";
    if($tableName == "User"){
      $directory = getenv("USER_BACKUPS");
    }
    if(!$fp = fopen("/opt/app-root/src/" . $directory . "/" . date("Y-m-d") . "_" . $tableName . ".csv", 'w')) return false;
    foreach ($tableArray as $line) fputcsv($fp, $line);
  }

  function backup($tableName){
    $tableArray = getArrayFromTable($tableName);
    createCSV($tableName, $tableArray);
  }
  
  $files = scandir("/opt/app-root/src/backups");
  $backedUpToday = false;
  for($i = 0; $i < count($files); $i++){
    if(strpos($files[$i], date("Y-m-d")) !== false){
      $backedUpToday = true;
    }
  }

  if(!$backedUpToday){
    //backup
    $dbconn = (new Keychain)->getDatabaseConnection();
    $query = mysqli_query($dbconn, "SELECT table_name FROM information_schema.tables WHERE table_schema='CaterpillarsCount' AND table_name<>'TemporaryEmailLog'");
    mysqli_close($dbconn);  
    while($row = mysqli_fetch_assoc($query)){
      backup($row["table_name"]);
    }
    
    //delete older files
    $acceptableDates = array(
      date("Y-m-d"), //today
      date("Y-m-d", time() - 60 * 60 * 24 * 1), //yesterday
      date("Y-m-d", time() - 60 * 60 * 24 * 2), //etc...
      date("Y-m-d", time() - 60 * 60 * 24 * 3),
      date("Y-m-d", time() - 60 * 60 * 24 * 4),
      date("Y-m-d", time() - 60 * 60 * 24 * 5),
      date("Y-m-d", time() - 60 * 60 * 24 * 6),
    );
    
    for($i = 0; $i < count($files); $i++){
      $dateIsAcceptable = false;
      for($j = 0; $j < count($acceptableDates); $j++){
        if(strpos($files[$i], $acceptableDates[$j]) !== false){
          $dateIsAcceptable = true;
        }
      }
      
      if(!$dateIsAcceptable && strlen(str_replace(".", "", $files[$i])) > 0){
        unlink("/opt/app-root/src/backups/" . $files[$i]);
      }
    }
    
    //delete from user backups too
    $files = scandir("/opt/app-root/src/" . getenv("USER_BACKUPS"));
    for($i = 0; $i < count($files); $i++){
      $dateIsAcceptable = false;
      for($j = 0; $j < count($acceptableDates); $j++){
        if(strpos($files[$i], $acceptableDates[$j]) !== false){
          $dateIsAcceptable = true;
        }
      }
      
      if(!$dateIsAcceptable && strlen(str_replace(".", "", $files[$i])) > 0){
        unlink("/opt/app-root/src/" . getenv("USER_BACKUPS") . "/" . $files[$i]);
      }
    }
  }
?>
