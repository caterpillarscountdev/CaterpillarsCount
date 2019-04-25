<?php
  require_once('orm/resources/Keychain.php');
  
  $i = intval(file_get_contents("iteration.txt"));
  
  $plants = array(array('5713', '178', '4', 'A', '3541', 'Red maple'),
array('5714', '178', '4', 'B', '3542', 'Red oak'),
array('5715', '178', '4', 'C', '3543', 'Red oak'),
array('5716', '178', '4', 'D', '3544', 'American chestnut'),
array('5717', '178', '4', 'E', '3545', 'American chestnut'),
array('5718', '178', '5', 'A', '3546', 'American chestnut'),
array('5719', '178', '5', 'B', '3547', 'American chestnut'),
array('5720', '178', '5', 'C', '3548', 'Red maple'),
array('5721', '178', '5', 'D', '3549', 'Red oak'),
array('5722', '178', '5', 'E', '3550', 'Red oak'));

	$dbconn = (new Keychain)->getDatabaseConnection();
  	for($j = 0; $j < 10; $j++){
    		$id = (1000003540 + $i + $j);
    		mysqli_query($dbconn, "INSERT INTO Plant (`ID`, `SiteFK`, `Circle`, `Orientation`, `Code`, `Species`) VALUES ('" . $id . "', '" . $plants[$i + $j][1] . "', '" . $plants[$i + $j][2] . "', '" . $plants[$i + $j][3] . "', '" . IDToCode($id) . "', '" . $plants[$i + $j][5] . "')");
	}
  
	$myfile = fopen("iteration.txt", "w");
	fwrite($myfile, (($i + 20) . ""));
	fclose($myfile);
  
  
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
