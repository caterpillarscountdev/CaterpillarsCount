<?php
  header('Access-Control-Allow-Origin: *');
  
	require_once('../tools/resultMemory.php');
  require_once('../orm/resources/Keychain.php');

	ini_set('memory_limit', '-1');
  
  function getArrayFromTable($tableName){
    $CHUNK_SIZE = 20000;
    $baseFileName = $tableName . basename(__FILE__, '.php');
    
    $tableArray = array();
    $dbconn = (new Keychain)->getDatabaseConnection();
    
    //HEADERS
    $query = mysqli_query($dbconn, "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='CaterpillarsCount' AND `TABLE_NAME`='" . $tableName . "'");
    $colHeaders = array();
    while ($row = mysqli_fetch_assoc($query)) $colHeaders[] = $row["COLUMN_NAME"];
    $tableArray[] = $colHeaders;
    
    $iteration = 0;
    $save = getSave($baseFileName, 60 * 60);
    if($save !== null){
      $iteration = substr($save, 0, strpos($save, ";"));
      $tableArray = json_decode(substr($save, strpos($save, ";") + 1));
    }
    
    //ROWS
    $query = mysqli_query($dbconn, "SELECT COUNT(*) AS Count FROM `" . $tableName . "`");
    $rowCount = mysqli_fetch_assoc($query)["Count"];
    
    if($rowCount > (count($tableArray) - 1)){
      $query = mysqli_query($dbconn, "SELECT * FROM `" . $tableName . "` LIMIT " . $iteration * $CHUNK_SIZE . ", " . $CHUNK_SIZE);
      mysqli_close($dbconn);

      while ($row = mysqli_fetch_assoc($query)){
        $rowArray = array();
        for($i = 0; $i < count($colHeaders); $i++){
          $rowArray[] = $row[$colHeaders[$i]];
        }
        $tableArray[] = $rowArray;
      }
    }
    
    if($rowCount > ((++$iteration) * $CHUNK_SIZE)){
      save($baseFileName, $iteration . ";" . json_encode($tableArray));
      return false;
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
    if(!hasBeenBackedUpToday($tableName)){
      $tableArray = getArrayFromTable($tableName);
      if($tableArray !== false){
        createCSV($tableName, $tableArray);
      }
    }
  }

  $files = scandir("/opt/app-root/src/backups");
  function hasBeenBackedUpToday($tableName){
    global $files;
    for($i = 0; $i < count($files); $i++){
      if($files[$i] == (date("Y-m-d") . "_" . $tableName)){
        return true;
      }
    }
    return false;
  }
  
  
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
?>
