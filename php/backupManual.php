<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once('orm/resources/Keychain.php');
  
  ini_set('memory_limit', '-1');
  
  function createCSV($tableName) {
    $directory = "backupsManual";
    if($tableName == "User"){
      return false;
      $directory = getenv("USER_BACKUPS");
    }
    $result = exec("mysqldump " . getenv("DATABASE_NAME") . " --password=" . getenv("HOST_PASSWORD") . " --user=" . getenv("HOST_USERNAME") . " --single-transaction >../" . $directory . "/" . date("Y-m-d") . "_" . $tableName . ".csv", $output);
    if($output==''){/* no output is good */}
    else {/* we have something to log the output here*/}
    echo $tableName . ": [" . var_dump($output) . "]";
  }
  
  //backup
  $dbconn = (new Keychain)->getDatabaseConnection();
  $query = mysqli_query($dbconn, "SELECT table_name FROM information_schema.tables WHERE table_schema='CaterpillarsCount' AND table_name<>'TemporaryEmailLog'");
  mysqli_close($dbconn);  
  while($row = mysqli_fetch_assoc($query)){
    createCSV($row["table_name"]);
  }
?>
