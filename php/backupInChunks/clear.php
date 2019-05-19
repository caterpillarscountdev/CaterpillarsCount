<?php
  $files = scandir("../backupInChunks");
  for($i = 0; $i < count($files); $i++){
    unlink("../backupInChunks/" . $files[$i]);
  }
  
  $files = scandir("../../backups");
  for($i = 0; $i < count($files); $i++){
    if(strpos($files[$i], "2019-05-19") !== false){
      unlink("../../backups/" . $files[$i]);
    }
  }
?>
