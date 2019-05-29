<?php
  header('Access-Control-Allow-Origin: *');
  
  require_once('orm/resources/Keychain.php');
  
  ini_set('memory_limit', '-1');
  
  //$result = exec('mysqldump --skip-lock-tables -h ' . getenv("CATERPILLARSV2_SERVICE_HOST") . ' -P ' . getenv("CATERPILLARSV2_SERVICE_PORT") . ' \-u ' . getenv("HOST_USERNAME") . ' --password="' . getenv("HOST_PASSWORD") . '" --all-databases > ../backupsManual/' . date("Y-m-d") . '.sql 2>&1', $output);
  exec("mysqldump --user={" . getenv("HOST_USERNAME") . "} --password={" . getenv("HOST_PASSWORD") . "} --host={" . getenv("CATERPILLARSV2_SERVICE_HOST") . "} {" . getenv("DATABASE_NAME") . "} --result-file={../backupsManual/" . date("Y-m-d") . ".sql} 2>&1", $output);
  var_dump($output);
?>
