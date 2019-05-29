<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once('orm/resources/Keychain.php');
  
  ini_set('memory_limit', '-1');
  
  $result = exec('sudo mysqldump --opt -u ' . getenv("HOST_USERNAME") . ' -p' . getenv("HOST_PASSWORD") . ' -h ' . getenv("CATERPILLARSV2_SERVICE_HOST") . ' ' . getenv("DATABASE_NAME") . ' > ../backupsManual/' . date("Y-m-d") . '.sql 2>&1', $output);
  if($output==''){/* no output is good */}
  else {/* we have something to log the output here*/}
  echo $tableName . ": [" . var_dump($output) . "]";
?>
