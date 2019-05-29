<?php
  exec("mysqldump --user={" . getenv("HOST_USERNAME") . "} --password={" . getenv("HOST_PASSWORD") . "} --host={" . getenv("CATERPILLARSV2_SERVICE_HOST") . "} --port={" . getenv("CATERPILLARSV2_SERVICE_PORT") . "} {" . getenv("DATABASE_NAME") . "} > {../backupsManual/" . date("Y-m-d") . ".sql} 2>&1", $output);
  var_dump($output);
?>
