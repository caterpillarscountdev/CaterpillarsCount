<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once('orm/resources/Keychain.php');
  
  ini_set('memory_limit', '-1');
  
  $result = exec("mysqldump " . getenv("DATABASE_NAME") . " --password=" . getenv("HOST_PASSWORD") . " --user=root --single-transaction >../backupsManual/" . date("Y-m-d") . ".sql", $output);
  if($output==''){/* no output is good */}
  else {/* we have something to log the output here*/}
  echo $tableName . ": [" . var_dump($output) . "]";
?>
