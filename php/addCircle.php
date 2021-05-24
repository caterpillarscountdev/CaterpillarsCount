<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Plant.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$samplePlantCode = $_GET["samplePlantCode"];
	$appVersion = intval(preg_replace("/[^0-9]/", "", isset($_GET["appVersion"]) ? $_GET["appVersion"] : "0"));
  
	$plant = Plant::findByCode($samplePlantCode);
	if(is_object($plant) && get_class($plant) == "Plant"){
		$site = $plant->getSite();
		if(is_object($site) && get_class($site) == "Site"){
			$user = User::findBySignInKey($email, $salt);
			if(is_object($user) && get_class($user) == "User"){
				$sites = $user->getSites();
				for($i = 0; $i < count($sites); $i++){
					$sites[$i] = $sites[$i]->getID();
				}
				if(in_array($site->getID(), $sites)){
					$newPlants = $site->addCircle();
					if($newPlants !== false){
						$newPlants = array();
						if($appVersion < 150){
							$newPlants = array(
								array($newPlants[0]->getOrientation(), $newPlants[0]->getCode(), $newPlants[0]->getSpecies()),
								array($newPlants[1]->getOrientation(), $newPlants[1]->getCode(), $newPlants[1]->getSpecies()),
								array($newPlants[2]->getOrientation(), $newPlants[2]->getCode(), $newPlants[2]->getSpecies()),
								array($newPlants[3]->getOrientation(), $newPlants[3]->getCode(), $newPlants[3]->getSpecies()),
								array($newPlants[4]->getOrientation(), $newPlants[4]->getCode(), $newPlants[4]->getSpecies()),
							);
						}
						else{
							$newPlants = array(
								array($newPlants[0]->getOrientation(), $newPlants[0]->getCode(), $newPlants[0]->getSpecies(), $newPlants[0]->getIsConifer()),
								array($newPlants[1]->getOrientation(), $newPlants[1]->getCode(), $newPlants[1]->getSpecies(), $newPlants[1]->getIsConifer()),
								array($newPlants[2]->getOrientation(), $newPlants[2]->getCode(), $newPlants[2]->getSpecies(), $newPlants[2]->getIsConifer()),
								array($newPlants[3]->getOrientation(), $newPlants[3]->getCode(), $newPlants[3]->getSpecies(), $newPlants[3]->getIsConifer()),
								array($newPlants[4]->getOrientation(), $newPlants[4]->getCode(), $newPlants[4]->getSpecies(), $newPlants[4]->getIsConifer()),
							);
						}
						die("true|" . json_encode($newPlants));
					}
					die("false|You cannot have more than 25 circles at a single site.");
				}
				die("false|You do not have permission to add a circle to this site.");
			}
			die("false|Your log in dissolved. Maybe you logged in on another device.");
		}
		die("false|The sample plant we extracted from this page does not correspond to an existing site.");
	}
	die("false|The sample plant code we extracted from this page did not correspond to an existing plant.");
?>
