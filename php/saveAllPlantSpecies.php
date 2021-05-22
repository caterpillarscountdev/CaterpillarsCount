<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	require_once('orm/Plant.php');
	
	$email = $_POST["email"];
	$salt = $_POST["salt"];
	$plantData = json_decode($_POST["plantData"]);
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		if(count($plantData) < 1){
			die("false|No data provided.");
		}
		if(count($plantData[0]) < 1){
			die("false|Improperly formatted data provided.");
		}
		$plant = Plant::findByCode($plantData[0][0]);
		if(!is_object($plant) || get_class($plant) != "Plant"){
			die("false|Plant with code \"" . $plantData[0][0] . "\" could not be found.");
		}
		$site = $plant->getSite();
		if(!is_object($site) || get_class($site) != "Site"){
			die("false|Could not find site associated with these plants.");
		}
		$sites = $user->getSites();
		for($i = 0; $i < count($sites); $i++){
			$sites[$i] = $sites[$i]->getID();
		}
		if(!in_array($site->getID(), $sites)){
			die("false|You do not have permission to edit the plants in this site.");
		}
		
		$plants = $site->getPlants();
		$associativePlants = array();
		for($i = 0; $i < count($plants); $i++){
			$associativePlants[$plants[$i]->getCode()] = $plants[$i];
		}
		
		for($i = 0; $i < count($plantData); $i++){
			if(count($plantData[$i]) == 2){
				$plantData[$i][2] = false;
			}
			
			if(count($plantData[$i]) > 2){
				if(array_key_exists($plantData[$i][0], $associativePlants)){
					$plant = $associativePlants[$plantData[$i][0]];
					if(is_object($plant) && get_class($plant) == "Plant"){
						if($plantData[$i][2]){
							$circle = $plant->getCircle();
							$plant->setCircle(-1 * $circle);
							$newPlant = Plant::create($plant->getSite(), $circle, $plant->getOrientation());
							if(!is_object($newPlant) || get_class($newPlant) != "Plant"){
								die("false|" . $newPlant);
							}
							$code = $plant->getCode();
							$plant->setCode($newPlant->getCode());
							$newPlant->setCode($code);
							$newPlant->setSpecies($plantData[$i][1]);
							$newPlant->setIsConifer(count($plantData[$i]) > 3 ? $plantData[$i][3] : $plant->getIsConifer());
						}
						else{
							$plant->setSpecies($plantData[$i][1]);
							$plant->setIsConifer($plantData[$i][3]);
						}
					}
					else{die("false|Plant with code \"" . $plantData[$i][0] . "\" could not be found in the \"" . $site->getName() . "\" site.");}
				}
				else{die("false|Plant with code \"" . $plantData[$i][0] . "\" could not be found in the \"" . $site->getName() . "\" site.");}
			}
			else{die("false|Improperly formatted data provided.");}
		}
		die("true|");
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
