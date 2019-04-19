<?php

require_once('resources/mailing.php');
require_once('resources/Keychain.php');
require_once('User.php');
require_once('ManagerRequest.php');
require_once('Plant.php');

class Site
{
//PRIVATE VARS
	private $id;							//INT
	private $creator;							//User object
	private $name;					//STRING			email that has been signed up for but not necessarilly verified
	private $description;
	private $url;
	private $latitude;
	private $longitude;
	private $region;
	private $saltedPasswordHash;			//STRING			salted hash of password
	private $salt;							//STRING
	private $openToPublic;
	private $active;
	
	private $deleted;

//FACTORY
	public static function create($creator, $name, $description, $url, $latitude, $longitude, $zoom, $region, $password, $openToPublic) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
		
		$validNameFormat = self::validNameFormat($dbconn, $name);
		$name = self::validName($dbconn, $name);
		$description = self::validDescription($dbconn, $description);
		$url = self::validURL($dbconn, $url);
		$latitude = self::validLatitude($dbconn, $latitude);
		$longitude = self::validLongitude($dbconn, $longitude);
		$zoom = self::validZoom($dbconn, $zoom);
		$region = self::validRegion($dbconn, $region);
		$isWater = self::validLandPoint($dbconn, $latitude, $longitude, $zoom);
		$password = self::validPassword($dbconn, $password);
		$openToPublic = filter_var($openToPublic, FILTER_VALIDATE_BOOLEAN);
		
		$failures = "";
		
		if($name === false){
			if($validNameFormat === false){
				$failures .= "Enter a site name. ";
			}
			else{
				$failures .= "That site name is already in use. Choose a different one. ";
			}
		}
		if($description === false){
			$failures .= "Site description must be between 1 and 255 characters. ";
		}
		if($latitude === false){
			$failures .= "Latitude is invalid. ";
		}
		if($longitude === false){
			$failures .= "Longitude is invalid. ";
		}
		if($zoom === false){
			$failures .= "Zoom in more on map to select a more accurate location. ";
		}
		if(!($latitude === false || $longitude === false || $zoom === false) && !$isWater){
			$failures .= "Site location must be on land. ";
		}
		if($password === false){
			$failures .= "Password must be at least 4 characters with no spaces. ";
		}
		if($creator->passwordIsCorrect($password)){
			$failures .= "Password cannot be the same as your Caterpillars Count! account password because you may be sharing it with vistors at this site. ";
		}
		
		if($failures != ""){
			return $failures;
		}
		
		$salt = mysqli_real_escape_string($dbconn, hash("sha512", rand() . rand() . rand()));
		$saltedPasswordHash = mysqli_real_escape_string($dbconn, hash("sha512", $salt . $password));
		
		mysqli_query($dbconn, "INSERT INTO Site (`UserFKOfCreator`, `Name`, `Description`, `URL`, `Latitude`, `Longitude`, `Region`, `Salt`, `SaltedPasswordHash`, `OpenToPublic`, `Active`) VALUES ('" . $creator->getID() . "', '$name', '$description', '$url', '$latitude', '$longitude', '$region', '$salt', '$saltedPasswordHash', '$openToPublic', '1')");
		$id = intval(mysqli_insert_id($dbconn));
		mysqli_close($dbconn);
		
		$publicPrivateString = "Private";
		if($openToPublic){$publicPrivateString = "Public";}
		email("caterpillarscount@gmail.com", "New Caterpillars Count! Site Created!", "<html> <head> <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"> <style> body{ margin:0px; } table{ font-size:20px; } td{ padding:20px; box-sizing:border-box; word-break: break-word; } td:nth-of-type(2){ color:#777; } @media only screen and (max-width: 500px){ table{ font-size:16px; } } @media only screen and (max-width: 400px){ table{ font-size:13px; } } </style> </head> <body> <div style=\"padding:20px;text-align:center;border-radius:5px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"> <div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\"> <table style=\"width:100%;table-layout: fixed;border-collapse:collapse;background:#f7f7f7;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;color:#555;\"> <tr> <td colspan=\"2\" style=\"background:#70c6ff;text-align:center;\"><img src=\"https://caterpillarscount.unc.edu/images/newSite.png\" style=\"height:150px;\"/></td> </tr> <tr> <td>Name: </td> <td>" . $name . "</td> </tr> <tr> <td>Description: </td> <td>" . $description . "</td> </tr> <tr> <td>Region: </td> <td>" . $region . "</td> </tr> <tr> <td>Coordinates: </td> <td><a href=\"https://www.google.com/maps/search/?api=1&query=" . $latitude . "," . $longitude . "\" style=\"color:#70c6ff;\">" . $latitude . "," . $longitude . "</a></td> </tr> <tr> <td>Visibility: </td> <td>" . $publicPrivateString . "</td> </tr> <tr> <td>Created by: </td> <td>" . $creator->getFullName() . "</td> </tr> <tr> <td>Email: </td> <td><a href=\"mailto:" . $creator->getEmail() . "\" style=\"color:#70c6ff;\">" . $creator->getEmail() . "</a></td> </tr> </table> </div> </div> </body></html>");
		email("caterpilarscountalerts@gmail.com", "New Caterpillars Count! Site Created!", "<html> <head> <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"> <style> body{ margin:0px; } table{ font-size:20px; } td{ padding:20px; box-sizing:border-box; word-break: break-word; } td:nth-of-type(2){ color:#777; } @media only screen and (max-width: 500px){ table{ font-size:16px; } } @media only screen and (max-width: 400px){ table{ font-size:13px; } } </style> </head> <body> <div style=\"padding:20px;text-align:center;border-radius:5px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"> <div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\"> <table style=\"width:100%;table-layout: fixed;border-collapse:collapse;background:#f7f7f7;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;color:#555;\"> <tr> <td colspan=\"2\" style=\"background:#70c6ff;text-align:center;\"><img src=\"https://caterpillarscount.unc.edu/images/newSite.png\" style=\"height:150px;\"/></td> </tr> <tr> <td>Name: </td> <td>" . $name . "</td> </tr> <tr> <td>Description: </td> <td>" . $description . "</td> </tr> <tr> <td>Region: </td> <td>" . $region . "</td> </tr> <tr> <td>Coordinates: </td> <td><a href=\"https://www.google.com/maps/search/?api=1&query=" . $latitude . "," . $longitude . "\" style=\"color:#70c6ff;\">" . $latitude . "," . $longitude . "</a></td> </tr> <tr> <td>Visibility: </td> <td>" . $publicPrivateString . "</td> </tr> <tr> <td>Created by: </td> <td>" . $creator->getFullName() . "</td> </tr> <tr> <td>Email: </td> <td><a href=\"mailto:" . $creator->getEmail() . "\" style=\"color:#70c6ff;\">" . $creator->getEmail() . "</a></td> </tr> </table> </div> </div> </body></html>");
		return new Site($id, $creator, $name, $description, $url, $latitude, $longitude, $region, $salt, $saltedPasswordHash, $openToPublic, true);
	}
	private function __construct($id, $creator, $name, $description, $url, $latitude, $longitude, $region, $salt, $saltedPasswordHash, $openToPublic, $active) {
		$this->id = intval($id);
		$this->creator = $creator;
		$this->name = $name;
		$this->description = $description;
		$this->url = $url;
		$this->latitude = $latitude;
		$this->region = $region;
		$this->longitude = $longitude;
		$this->salt = $salt;
		$this->saltedPasswordHash = $saltedPasswordHash;
		$this->openToPublic = $openToPublic;
		$this->active = $active;
		
		$this->deleted = false;
	}

//FINDERS
	public static function findByID($id) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = mysqli_real_escape_string($dbconn, $id);
		$query = mysqli_query($dbconn, "SELECT * FROM `Site` WHERE `ID`='$id' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$siteRow = mysqli_fetch_assoc($query);
		
		$creator = User::findByID($siteRow["UserFKOfCreator"]);
		$name = $siteRow["Name"];
		$description = $siteRow["Description"];
		$url = $siteRow["URL"];
		$latitude = $siteRow["Latitude"];
		$longitude = $siteRow["Longitude"];
		$region = $siteRow["Region"];
		$salt = $siteRow["Salt"];
		$saltedPasswordHash = $siteRow["SaltedPasswordHash"];
		$openToPublic = $siteRow["OpenToPublic"];
		$active = filter_var($siteRow["Active"], FILTER_VALIDATE_BOOLEAN);
		
		return new Site($id, $creator, $name, $description, $url, $latitude, $longitude, $region, $salt, $saltedPasswordHash, $openToPublic, $active);
	}
	
	public static function findByName($name) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$name = self::validNameFormat($dbconn, $name);
		if($name === false){
			return null;
		}
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `Site` WHERE `Name`='$name' LIMIT 1");
		mysqli_close($dbconn);
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		return self::findByID(intval(mysqli_fetch_assoc($query)["ID"]));
	}
	
	public static function findSitesByCreator($creator){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `Site` WHERE `UserFKOfCreator`='" . $creator->getID() . "'");
		mysqli_close($dbconn);
		
		$sitesArray = array();
		while($siteRow = mysqli_fetch_assoc($query)){
			$id = $siteRow["ID"];
			$name = $siteRow["Name"];
			$description = $siteRow["Description"];
			$url = $siteRow["URL"];
			$latitude = $siteRow["Latitude"];
			$longitude = $siteRow["Longitude"];
			$region = $siteRow["Region"];
			$salt = $siteRow["Salt"];
			$saltedPasswordHash = $siteRow["SaltedPasswordHash"];
			$openToPublic = $siteRow["OpenToPublic"];
			$active = filter_var($siteRow["Active"], FILTER_VALIDATE_BOOLEAN);
			$site = new Site($id, $creator, $name, $description, $url, $latitude, $longitude, $region, $salt, $saltedPasswordHash, $openToPublic, $active);
			
			array_push($sitesArray, $site);
		}
		return $sitesArray;
	}
	
	public static function findManagedSitesByManager($manager){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `SiteFK` FROM `ManagerRequest` WHERE `UserFKOfManager`='" . $manager->getID() . "' AND `Status`='Approved'");
		mysqli_close($dbconn);
		
		$sitesArray = array();
		while($managerRow = mysqli_fetch_assoc($query)){
			$siteFK = $managerRow["SiteFK"];
			$site = self::findByID($siteFK);
			if(is_object($site) && get_class($site) == "Site"){
				array_push($sitesArray, $site);
			}
		}
		return $sitesArray;
	}
	
	public static function findAllActivePublicSites(){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `Site` WHERE `OpenToPublic`='1'");
		mysqli_close($dbconn);
		
		$sitesArray = array();
		while($siteRow = mysqli_fetch_assoc($query)){
			$id = $siteRow["ID"];
			$creator = User::findByID($siteRow["UserFKOfCreator"]);
			$name = $siteRow["Name"];
			$description = $siteRow["Description"];
			$url = $siteRow["URL"];
			$latitude = $siteRow["Latitude"];
			$longitude = $siteRow["Longitude"];
			$region = $siteRow["Region"];
			$salt = $siteRow["Salt"];
			$saltedPasswordHash = $siteRow["SaltedPasswordHash"];
			$active = filter_var($siteRow["Active"], FILTER_VALIDATE_BOOLEAN);
			
			$site = new Site($id, $creator, $name, $description, $url, $latitude, $longitude, $region, $salt, $saltedPasswordHash, true, $active);
			
			if($active){
				array_push($sitesArray, $site);
			}
		}
		return $sitesArray;
	}
	
	public static function findAll($start = 0, $limit = 0){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$limitSQL = " LIMIT $start, $limit";
		if($limit == 0){
			$limitSQL = "";
		}
		$query = mysqli_query($dbconn, "SELECT * FROM `Site`" . $limitSQL);
		mysqli_close($dbconn);
		
		$sitesArray = array();
		while($siteRow = mysqli_fetch_assoc($query)){
			$id = $siteRow["ID"];
			$creator = User::findByID($siteRow["UserFKOfCreator"]);
			$name = $siteRow["Name"];
			$description = $siteRow["Description"];
			$url = $siteRow["URL"];
			$latitude = $siteRow["Latitude"];
			$longitude = $siteRow["Longitude"];
			$region = $siteRow["Region"];
			$salt = $siteRow["Salt"];
			$saltedPasswordHash = $siteRow["SaltedPasswordHash"];
			$active = filter_var($siteRow["Active"], FILTER_VALIDATE_BOOLEAN);
			
			$site = new Site($id, $creator, $name, $description, $url, $latitude, $longitude, $region, $salt, $saltedPasswordHash, true, $active);
			array_push($sitesArray, $site);
		}
		return $sitesArray;
	}

//GETTERS
	public function getID() {
		if($this->deleted){return null;}
		return intval($this->id);
	}
	
	public function getCreator() {
		if($this->deleted){return null;}
		return $this->creator;
	}
	
	public function isAuthority($user){
		if(is_object($user) && get_class($user) == "User"){
			if($user == $this->getCreator()){
				return true;
			}
			$managerRequest = ManagerRequest::findByManagerAndSite($user, $this);
			if(is_object($managerRequest) && get_class($managerRequest) == "ManagerRequest"){
				return ($managerRequest->getStatus() == "Approved");
			}
		}
		return false;
	}
	
	public function getName() {
		if($this->deleted){return null;}
		return $this->name;
	}
	
	public function getDescription() {
		if($this->deleted){return null;}
		return $this->description;
	}
	
	public function getURL() {
		if($this->deleted){return null;}
		return $this->url;
	}
	
	public function getLatitude() {
		if($this->deleted){return null;}
		return $this->latitude;
	}
	
	public function getLongitude() {
		if($this->deleted){return null;}
		return $this->longitude;
	}
	
	public function getRegion() {
		if($this->deleted){return null;}
		return $this->region;
	}
	
	public function getPlants(){
		if($this->deleted){return null;}
		return Plant::FindPlantsBySite($this);
	}
	
	public function getSaltedHash($password){
		if($this->deleted){return null;}
		if($this->passwordIsCorrect($password)){
			return $this->saltedPasswordHash;
		}
		return false;
	}
	
	public function getOpenToPublic(){
		if($this->deleted){return null;}
		return filter_var($this->openToPublic, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getActive(){
		if($this->deleted){return null;}
		return filter_var($this->active, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getObservationMethodPreset($user){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `ObservationMethod` FROM `SiteUserPreset` WHERE `UserFK`='" . intval($user->getID()) . "' AND `SiteFK`='" . $this->id . "' LIMIT 1");
		mysqli_close($dbconn);
		if(mysqli_num_rows($query) == 0){
			return "";
		}
		return mysqli_fetch_assoc($query)["ObservationMethod"];
	}
	
	public function getValidationStatus($user){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `SiteUserValidation` WHERE `UserFK`='" . intval($user->getID()) . "' AND `SiteFK`='" . $this->id . "' AND `SaltedSitePasswordHash`='" . $this->saltedPasswordHash . "' LIMIT 1");
		mysqli_close($dbconn);
		if(mysqli_num_rows($query) == 0){
			return false;
		}
		return true;
	}
	
	public function getNumberOfSurveysByYear($year){
		$year = intval($year);
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT COUNT(Survey.ID) AS Count FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID WHERE Plant.SiteFK='" . $this->id . "' AND YEAR(Survey.LocalDate)='$year'");
		mysqli_close($dbconn);
		return intval(mysqli_fetch_assoc($query)["Count"]);
	}
	
	public function getAuthorityEmails(){
		$authorityEmails = array($this->creator->getEmail());
		$managerRequests = ManagerRequest::findManagerRequestsBySite($this);
		for($i = 0; $i < count($managerRequests); $i++){
			if($managerRequests[$i]->getStatus() == "Approved"){
				$authorityEmails[] = $managerRequests[$i]->getManager()->getEmail();
			}
		}
		return $authorityEmails;
	}
	
//SETTERS
	public function setCreator($manager, $demotedPosition){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$managerRequest = ManagerRequest::findByManagerAndSite($manager, $this);
			if(is_object($managerRequest) && get_class($managerRequest) == "ManagerRequest" && $managerRequest->getStatus() == "Approved"){
				//delete manager request
				$managerRequest->permanentDelete();
				
				//set manager as creator
				mysqli_query($dbconn, "UPDATE Site SET UserFKOfCreator='" . $manager->getID() . "' WHERE ID='" . $this->id . "'");
				$lastCreator = $this->creator;
				$this->creator = $manager;
				
				//create a new approved manager request with complete authority
				if($demotedPosition != "unaffiliated"){
					mysqli_query($dbconn, "INSERT INTO ManagerRequest (`UserFKOfManager`, `SiteFK`, `HasCompleteAuthority`, `Status`) VALUES ('" . $lastCreator->getID() . "', '" . $this->id . "', '" . ($demotedPosition == "highManagement") . "', 'Approved')");
				}
				
				mysqli_close($dbconn);
				
				$message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">" . $lastCreator->getFullName() . " has resigned and promoted you to the position of Owner of the \"" . $this->name . "\" Caterpillars Count! site in " . $this->region . ". Please sign in to your <a href='https://caterpillarscount.unc.edu/manageMySites'>Manage My Sites</a> page using this email address (" . $manager->getEmail() . ") to maintain this site as needed.</div><a href='https://caterpillarscount.unc.edu/manageMySites'><button style=\"border:0px none transparent;background:#fed136; border-radius:5px;padding:20px 40px;font-size:20px;color:#fff;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;font-weight:bold;cursor:pointer;\">MANAGE MY SITES</button></a><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"></div></div>";
				email($manager->getEmail(), "You are now the owner of " . $this->name . " for Caterpillars Count!", $message);
				
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setName($name){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$name = self::validName($dbconn, $name);
			if($name !== false){
				mysqli_query($dbconn, "UPDATE Site SET Name='$name' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->name = $name;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setDescription($description){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$description = self::validDescription($dbconn, $description);
			if($description !== false){
				mysqli_query($dbconn, "UPDATE Site SET Description='$description' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->description = $description;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setURL($url){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$url = self::validURL($dbconn, $url);
			if($url !== false){
				mysqli_query($dbconn, "UPDATE Site SET URL='$url' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->url = $url;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setOpenToPublic($openToPublic){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$openToPublic = filter_var($openToPublic, FILTER_VALIDATE_BOOLEAN);
			mysqli_query($dbconn, "UPDATE Site SET OpenToPublic='$openToPublic' WHERE ID='" . $this->id . "'");
			mysqli_close($dbconn);
			$this->openToPublic = $openToPublic;
			return true;
		}
		return false;
	}
	
	public function setActive($active){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
			mysqli_query($dbconn, "UPDATE Site SET `Active`='$active' WHERE ID='" . $this->id . "'");
			mysqli_close($dbconn);
			$this->active = $active;
			return true;
		}
		return false;
	}
	
	public function setPassword($password) {
		if(!$this->deleted)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			$password = self::validPassword($dbconn, $password);
			if($password != false){
				$saltedPasswordHash = mysqli_real_escape_string($dbconn, hash("sha512", $this->salt . $password));
				mysqli_query($dbconn, "UPDATE Site SET SaltedPasswordHash='$saltedPasswordHash' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->saltedPasswordHash = $saltedPasswordHash;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setObservationMethodPreset($user, $observationMethod){
		if($this->deleted || ($observationMethod != "Visual" && $observationMethod != "Beat sheet")){return false;}
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `SiteUserPreset` WHERE `UserFK`='" . intval($user->getID()) . "' AND `SiteFK`='" . $this->id . "' LIMIT 1");
		if(mysqli_num_rows($query) == 1){
			$query = mysqli_query($dbconn, "UPDATE `SiteUserPreset` SET `ObservationMethod`='" . $observationMethod . "' WHERE `UserFK`='" . intval($user->getID()) . "' AND `SiteFK`='" . $this->id . "'");
			mysqli_close($dbconn);
			return true;
		}
		mysqli_query($dbconn, "INSERT INTO `SiteUserPreset`(`UserFK`, `SiteFK`, `ObservationMethod`) VALUES ('" . intval($user->getID()) . "', '" . $this->id . "', '" . $observationMethod . "')");
		mysqli_close($dbconn);
		return true;
	}
	
	public function addCircle(){
		if(!$this->deleted)
		{
			$numberOfPreexistingCircles = (count($this->getPlants()) / 5);
			if($numberOfPreexistingCircles < 25){
				$a = Plant::create($this, ($numberOfPreexistingCircles + 1), "A");
				$b = Plant::create($this, ($numberOfPreexistingCircles + 1), "B");
				$c = Plant::create($this, ($numberOfPreexistingCircles + 1), "C");
				$d = Plant::create($this, ($numberOfPreexistingCircles + 1), "D");
				$e = Plant::create($this, ($numberOfPreexistingCircles + 1), "E");
				return array($a, $b, $c, $d, $e);
			}
		}
		return false;
	}
	
	public function validateUser($user, $password){
		if($this->deleted){return false;}
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `SiteUserValidation` WHERE `UserFK`='" . intval($user->getID()) . "' AND `SiteFK`='" . $this->id . "' AND `SaltedSitePasswordHash`='" . $this->saltedPasswordHash . "' LIMIT 1");
		if(mysqli_num_rows($query) == 1){
			mysqli_close($dbconn);
			return true;
		}
		else if($this->passwordIsCorrect($password)){
			mysqli_query($dbconn, "INSERT INTO `SiteUserValidation`(`UserFK`, `SiteFK`, `SaltedSitePasswordHash`) VALUES ('" . intval($user->getID()) . "', '" . $this->id . "', '" . $this->saltedPasswordHash . "')");
			mysqli_close($dbconn);
			return true;
		}
		mysqli_close($dbconn);
		
		if(User::isSuperUser($user)){
			return true;
		}
		return false;
	}
	
	
//REMOVER
	public function permanentDelete()
	{
		if(!$this->deleted)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			mysqli_query($dbconn, "DELETE FROM `Site` WHERE `ID`='" . $this->id . "'");
			$this->deleted = true;
			mysqli_close($dbconn);
			return true;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//validity ensurance
	public static function validName($dbconn, $name){
		$name = self::validNameFormat($dbconn, $name);
		if($name === false || is_object(self::findByName($name))){
			return false;
		}
		return $name;
	}
	
	public static function validNameFormat($dbconn, $name){
		$name = trim(mysqli_real_escape_string($dbconn, rawurldecode($name)));
		
		if(preg_replace('/\s+/', '', $name) == ""){
			return false;
		}
		return $name;
	}
	
	public static function validDescription($dbconn, $description){
		$description = preg_replace("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", "[URL Removed]", rawurldecode($description));
		$description = mysqli_real_escape_string($dbconn, $description);
		
		if(strlen($description) == 0 || strlen($description) > 255){
			return false;
		}
		return $description;
	}
	
	public static function validURL($dbconn, $url){
		return mysqli_real_escape_string($dbconn, rawurldecode(trim($url)));
	}
	
	public static function validLatitude($dbconn, $latitude){
		$latitude = floatval(mysqli_real_escape_string($dbconn, preg_replace("/[^0-9.-]/", "", trim(rawurldecode((string)$latitude)))));
		if(abs($latitude) > 90){
			return false;
		}
		return $latitude;
	}
	
	public static function validLongitude($dbconn, $longitude){
		$longitude = floatval(mysqli_real_escape_string($dbconn, preg_replace("/[^0-9.-]/", "", trim(rawurldecode((string)$longitude)))));
		if(abs($longitude) > 180){
			return false;
		}
		return $longitude;
	}
	
	public static function validZoom($dbconn, $zoom){
		$zoom = intval(mysqli_real_escape_string($dbconn, preg_replace("/[^0-9]/", "", trim(rawurldecode((string)$zoom)))));
		if($zoom < 10){
			return false;
		}
		return $zoom;
	}
	
	public static function validRegion($dbconn, $region){
		$region = mysqli_real_escape_string($dbconn, rawurldecode($region));
		return $region;
	}
	
	public static function validLandPoint($dbconn, $latitude, $longitude, $zoom){
		$latitude = self::validLatitude($dbconn, $latitude);
		$longitude = self::validLongitude($dbconn, $longitude);
		$zoom = self::validZoom($dbconn, $zoom);
		
		if($latitude === false || $longitude === false || $zoom === false){
			return false;
		}
		
		$imgurl = "http://maps.googleapis.com/maps/api/staticmap?center=" . $latitude . "," . $longitude . "&zoom=" . $zoom . "&size=99x99&maptype=roadmap&sensor=false&style=element:labels|visibility:off&style=element:geometry.stroke|visibility:off&style=feature:landscape|element:geometry|saturation:-100&style=feature:water|saturation:-100|invert_lightness:true";
		$pathname = "images/map/lastCall.png";
		copy($imgurl, $pathname);
		return (imagecolorat(imagecreatefrompng($pathname) , 50, 50) > 7);
	}
	
	public static function validPassword($dbconn, $password){
		$spacelessPassword = mysqli_real_escape_string($dbconn, preg_replace('/ /', '', rawurldecode((string)$password)));
		
		if(strlen($password) != strlen($spacelessPassword) || strlen($spacelessPassword) < 4){
			return false;
		}
		return $password;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//FUNCTIONS
	public function sendSignUpEmailToCreator(){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$plants = $this->getPlants();
		$numberOfCircles = (count($plants) / 5);
		$root = (new Keychain)->getRoot();
		$message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">Congratulations on creating your new Caterpillars Count! site, \"" . $this->name . "\". Once you have located and identified your survey branches, you can click the \"Edit Survey Plants\" button on the <a href=\"" . $root . "/manageMySites\" style=\"color:#70c6ff;\">Manage My Sites</a> page of the website to enter the tree species names for each survey.<br/><br/>You can then <a href=\"" . $root . "/php/printPlantCodes.php?q=" . $this->id . "\" style=\"color:#70c6ff;\">click here</a> to print the Branch Survey Code tags to hang on each survey branch. (We recommend printing in color, then \"laminating\" by folding each tag in a strip of packing tape, then hanging by twist tie or other means.)<br/><br/>If you have entered the plant species names for each survey using the web link above, they will appear on the tags. Otherwise, the plant species will be listed as \"N/A\".</div><a href=\"" . $root . "/manageMySites\"><button style=\"border:0px none transparent;background:#fed136; border-radius:5px;padding:20px 40px;font-size:20px;color:#fff;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;font-weight:bold;cursor:pointer;\">MANAGE MY SITES</button></a><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"><div>Alternatively, use this link and then click \"Edit Survey Plants\" to enter the tree species names for each survey: <a href=\"" . $root . "/manageMySites\" style=\"color:#70c6ff;\">" . $root . "/manageMySites</a></div><div>And then use this link to print the tags for your new site: <a href=\"" . $root . "/php/printPlantCodes.php?q=" . $this->id . "\" style=\"color:#70c6ff;\">" . $root . "/php/printPlantCodes.php?q=" . $this->id . "</a></div></div></div>";
		email($this->creator->getEmail(), "Your new Caterpillars Count! site", $message);//, $headers);
		
		mysqli_close($dbconn);
		return true;
	}
	
	public function sendPrintTagsEmailTo($user){
		$sites = $user->getSites();
		for($i = 0; $i < count($sites); $i++){
			$sites[$i] = $sites[$i]->getID();
		}
		if(in_array(intval($this->id), $sites)){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$plants = $this->getPlants();
			$numberOfCircles = (count($plants) / 5);
			$root = (new Keychain)->getRoot();
			$message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">Here's what you need to know to set up your \"" . $this->name . "\" Caterpillars Count! site!<br/><br/>If you haven't done so yet, locate and identify your survey branches first. Then click the \"Edit Survey Plants\" button on the <a href=\"" . $root . "/manageMySites\" style=\"color:#70c6ff;\">Manage My Sites</a> page of the website to enter the tree species names for each survey.<br/><br/>You can then proceed to <a href=\"" . $root . "/php/printPlantCodes.php?q=" . $this->id . "\" style=\"color:#70c6ff;\">click here</a> to print the Branch Survey Code tags to hang on each survey branch. (We recommend printing in color, then \"laminating\" by folding each tag in a strip of packing tape, then hanging by twist tie or other means.)<br/><br/>If you have entered the plant species names for each survey using the web link above, they will appear on the tags. Otherwise, the plant species will be listed as \"N/A\".</div><a href=\"" . $root . "/manageMySites\"><button style=\"border:0px none transparent;background:#fed136; border-radius:5px;padding:20px 40px;font-size:20px;color:#fff;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;font-weight:bold;cursor:pointer;\">MANAGE MY SITES</button></a><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"><div>Alternatively, use this link and then click \"Edit Survey Plants\" to enter the tree species names for each survey: <a href=\"" . $root . "/manageMySites\" style=\"color:#70c6ff;\">" . $root . "/manageMySites</a></div><div>And then use this link to print the tags for your new site: <a href=\"" . $root . "/php/printPlantCodes.php?q=" . $this->id . "\" style=\"color:#70c6ff;\">" . $root . "/php/printPlantCodes.php?q=" . $this->id . "</a></div></div></div>";
			email($user->getEmail(), "Print tags for your \"" . $this->name . "\" Caterpillars Count! site", $message);//, $headers);
		
			mysqli_close($dbconn);
			return true;
		}
		return false;
	}
	
	public function passwordIsCorrect($password){
		$password = rawurldecode($password);
		$dbconn = (new Keychain)->getDatabaseConnection();
		$testSaltedPasswordHash = mysqli_real_escape_string($dbconn, hash("sha512", $this->salt . $password));
		if($testSaltedPasswordHash == $this->saltedPasswordHash){
			mysqli_close($dbconn);
			return true;
		}
		mysqli_close($dbconn);
		return false;
	}
	
	public function hasCreatorPermissions($user){
		if(is_object($user) && get_class($user) == "User"){
			if($user->getEmail() == $this->getCreator()->getEmail() || User::isSuperUser($user->getEmail())){
				return true;
			}
			
			$managerRequest = ManagerRequest::findByManagerAndSite($user, $this);
			if(get_class($managerRequest) == "ManagerRequest"){
				return $managerRequest->getHasCompleteAuthority();
			}
		}
		return false;
	}
}		
?>
