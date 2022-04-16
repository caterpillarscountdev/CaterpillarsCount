<?php
  $reportsPerYear = array();
  
  $scanResults = scandir(realpath("../reports"));
  for($i = 0; $i < count($scanResults); $i++){
    $path = realpath("../reports/" . $scanResults[$i]);
    if(is_dir($path) && !in_array($scanResults[$i], array(".", ".."))){
      $reportsPerYear[$scanResults[$i]] = array();
      
      $files = scandir($path);
      for($j = 0; $j < count($files); $j++){
        if(!is_dir(realpath("../reports/" . $scanResults[$i] . "/" . $files[$j]))){
          $reportsPerYear[$scanResults[$i]][] = $files[$j];
        }
      }
    }
  }
  
  die(json_encode($reportsPerYear, true));
?>
