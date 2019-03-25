<?php
  function save($baseFileName, $result){
    $saveFilePath = $baseFileName . ".txt";
		$saveFile = fopen($saveFilePath, "w");
		fwrite($saveFile, time() . "|" . $result);
		fclose($saveFile);
  }
  
  function getSave($baseFileName, $timeLimit){
    $saveFilePath = $baseFileName . ".txt";
    if(file_exists($saveFilePath)){
      $contents = file_get_contents($saveFilePath);
      if($contents !== false){
        $savedTime = intval(substr($contents, 0, strpos($contents, "|")));
        if(time() - $savedTime <= $timeLimit){
          die(substr($contents, (strpos($contents, "|") + 1)));
        }
      }
    }
  }
?>
