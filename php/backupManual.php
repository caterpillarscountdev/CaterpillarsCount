<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once('orm/resources/Keychain.php');
  
  ini_set('memory_limit', '-1');
  
  $result = exec('mysqldump --user=' . getenv("HOST_USERNAME") . ' --password=' . getenv("HOST_PASSWORD") . ' --host=' . getenv("CATERPILLARSV2_SERVICE_HOST") . ' ' . getenv("DATABASE_NAME") . ' > ../backupsManual/' . date("Y-m-d") . '.sql', $output);
  if($output==''){/* no output is good */}
  else {/* we have something to log the output here*/}
  echo $tableName . ": [" . var_dump($output) . "]";
?>
