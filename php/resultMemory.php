<?php
	require_once("orm/resources/Keychain.php");
	
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
					return substr($contents, (strpos($contents, "|") + 1));
				}
			}
		}
		return null;
	}

	function saveToDatabase($baseFileName, $result){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM CachedResult WHERE `Name`='$baseFileName' LIMIT 1");
		
		$result = mysqli_real_escape_string($dbconn, $result);
		if(mysqli_num_rows($query) <= 0){
			//insert
			mysqli_query($dbconn, "INSERT INTO `CachedResult` (`Name`, `Timestamp`, `Result`) VALUES ('$baseFileName', '" . time() . "', '$result')");
		}
		else{
			//update
			mysqli_query($dbconn, "UPDATE `CachedResult` SET `Timestamp`='" . time() . "', `Result`='$result' WHERE `Name`='$baseFileName'");
		}
		
		mysqli_close($dbconn);
	}
  
	function getSaveFromDatabase($baseFileName, $timeLimit){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM CachedResult WHERE `Name`='$baseFileName' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) > 0){
			$row = mysqli_fetch_assoc($query);
			$savedTime = intval($row["Timestamp"]);
			if(time() - $savedTime <= $timeLimit){
				return $row["Result"];
			}
		}
		return null;
	}
?>
