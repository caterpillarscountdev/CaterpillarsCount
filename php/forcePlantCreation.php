<?php
  require_once('orm/resources/Keychain.php');

$dbconn = (new Keychain)->getDatabaseConnection();

mysqli_query($dbconn, "INSERT INTO Plant (`ID`, `SiteFK`, `Circle`, `Orientation`, `Code`, `Species`) VALUES ('1000003550', '176', '260', 'D', '" . IDToCode(1000003550) . "', 'N/A')");
mysqli_query($dbconn, "INSERT INTO Plant (`ID`, `SiteFK`, `Circle`, `Orientation`, `Code`, `Species`) VALUES ('1000003551', '176', '260', 'E', '" . IDToCode(1000003551) . "', 'N/A')");
mysqli_query($dbconn, "INSERT INTO Plant (`ID`, `SiteFK`, `Circle`, `Orientation`, `Code`, `Species`) VALUES ('1000003552', '177', '336', 'C', '" . IDToCode(1000003552) . "', 'N/A')");
mysqli_query($dbconn, "INSERT INTO Plant (`ID`, `SiteFK`, `Circle`, `Orientation`, `Code`, `Species`) VALUES ('1000003553', '177', '336', 'D', '" . IDToCode(1000003553) . "', 'N/A')");
mysqli_query($dbconn, "INSERT INTO Plant (`ID`, `SiteFK`, `Circle`, `Orientation`, `Code`, `Species`) VALUES ('1000003554', '177', '336', 'E', '" . IDToCode(1000003554) . "', 'N/A')");
  
  
  function IDToCode($id){
		$chars = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
		
		//get the length of the code we will be returning
		$codeLength = 0;
		$previousIterations = 0;
		while(true){
			$nextIterations = pow(count($chars), ++$codeLength);
			if($id <= $previousIterations + $nextIterations){
				break;
			}
			$previousIterations += $nextIterations;
		}
		
		//and, for every character that will be in the code...
		$code = "";
		$index = $id - 1;
		$iterationsFromPreviousSets = 0;
		for($i = 0; $i < $codeLength; $i++){
			//generate the character from the id
			if($i > 0){
				$iterationsFromPreviousSets += pow(count($chars), $i);
			}
			$newChar = $chars[floor(($index - $iterationsFromPreviousSets) / pow(count($chars), $i)) % count($chars)];
			
			//and add it to the code
			$code = $newChar . $code;
		}
		
		//then, return a sanitized version of the full code that is safe to use with a MySQL query
		$dbconn = (new Keychain)->getDatabaseConnection();
		$code = mysqli_real_escape_string($dbconn, $code);
		mysqli_close($dbconn);
		return $code;
	}
?>
