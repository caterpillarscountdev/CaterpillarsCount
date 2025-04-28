<?php

require_once('resources/Keychain.php');
require_once('Site.php');


class Plant
{
//PRIVATE VARS
	private $id;							//INT
	private $site;							//Site object
	private $circle;
	private $orientation;					//STRING			email that has been signed up for but not necessarilly verified
	private $code;
	private $species;
	private $isConifer;
        private $latitude;
        private $longitude;

	private $deleted;

//FACTORY
	public static function create($site, $circle, $orientation) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
		
		$site = self::validSite($dbconn, $site);
		$circle = self::validCircleFormat($dbconn, $circle);
		$orientation = self::validOrientationFormat($dbconn, $orientation);
		
		$failures = "";
		
		if($site === false){
			$failures .= "Invalid site. ";
		}
		if($circle === false){
			$failures .= "Enter a circle. ";
		}
		if($orientation === false){
			$failures .= "Enter an orientation. ";
		}
		if($failures == "" && is_object(self::findBySiteAndPosition($site, $circle, $orientation))){
			$failures .= "Enter a unique circle/orientation set for this site. ";
		}
		
		if($failures != ""){
			return $failures;
		}
		
		//DETERMINE ID MANUALLY TO FILL IN THE CRACKS OF DELETED CODES:
		$MIN_ID = 703;//corresponds to "AAA"
		$id = $MIN_ID;
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `Plant` ORDER BY `ID` ASC LIMIT 1");
		if(mysqli_num_rows($query) == 1){
			$query = mysqli_query($dbconn, "SELECT t1.ID+1 AS NextID FROM `Plant` AS t1 LEFT JOIN `Plant` AS t2 ON t1.ID+1=t2.ID WHERE t2.ID IS NULL AND t1.ID+1>='$MIN_ID' ORDER BY t1.ID+1 ASC");
			while($row = mysqli_fetch_assoc($query)){
				$id = intval($row["NextID"]);
				while(mysqli_num_rows(mysqli_query($dbconn, "SELECT `ID` FROM `Plant` WHERE `ID`='" . $id . "' LIMIT 1")) == 0){
					if(mysqli_num_rows(mysqli_query($dbconn, "SELECT `ID` FROM `Plant` WHERE `Code`='" . self::IDToCode($id) . "' LIMIT 1")) == 0){
						break 2;
					}
					$id++;
				}
			}
		}
		$code = self::IDToCode($id);
		
		mysqli_query($dbconn, "INSERT INTO Plant (`ID`, `SiteFK`, `Circle`, `Orientation`, `Code`, `Species`, `IsConifer`) VALUES ('$id', '" . $site->getID() . "', '$circle', '$orientation', '$code', 'N/A', '0')");
		
		return new Plant($id, $site, $circle, $orientation, $code, "N/A", false, null, null);
	}
	private function __construct($id, $site, $circle, $orientation, $code, $species, $isConifer, $latitude, $longitude) {
		$this->id = intval($id);
		$this->site = $site;
		$this->circle = $circle;
		$this->orientation = $orientation;
		$this->code = $code;
		$this->species = $species;
		$this->isConifer = filter_var($isConifer, FILTER_VALIDATE_BOOLEAN);
                $this->latitude = $latitude;
                $this->longitude = $longitude;
		
		$this->deleted = false;
	}

        private static function _constructFromRow($plantRow, $sites = array()) {
                $id = $plantRow["ID"];
                $site = array_key_exists($plantRow["SiteFK"], $sites) ? $sites[$plantRow["SiteFK"]] : Site::findByID($plantRow["SiteFK"]);
		$circle = $plantRow["Circle"];
		$orientation = $plantRow["Orientation"];
		$code = $plantRow["Code"];
		$species = $plantRow["Species"];
		$isConifer = $plantRow["IsConifer"];
                $latitude = $plantRow["Latitude"];
                $longitude = $plantRow["Longitude"];
		
		return new Plant($id, $site, $circle, $orientation, $code, $species, $isConifer, $latitude, $longitude);
        }

//FINDERS
	public static function findByID($id) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = mysqli_real_escape_string($dbconn, htmlentities($id));
		$query = mysqli_query($dbconn, "SELECT * FROM `Plant` WHERE `ID`='$id' LIMIT 1");
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$plantRow = mysqli_fetch_assoc($query);

                return self::_constructFromRow($plantRow);
	}
	
	public static function findByCode($code) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$code = self::validCode($dbconn, $code);
		if($code === false){
			return null;
		}
		$query = mysqli_query($dbconn, "SELECT * FROM `Plant` WHERE `Code`='$code' LIMIT 1");
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$plantRow = mysqli_fetch_assoc($query);

                return self::_constructFromRow($plantRow);
	}
	
	public static function findBySiteAndPosition($site, $circle, $orientation) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$site = self::validSite($dbconn, $site);
		$circle = self::validCircleFormat($dbconn, $circle);
		$orientation = self::validOrientationFormat($dbconn, $orientation);
		if($site === false || $circle === false || $orientation === false){
			return null;
		}
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `Plant` WHERE `SiteFK`='" . $site->getID() . "' AND `Circle`='$circle' AND `Orientation`='$orientation' LIMIT 1");
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		return self::findByID(intval(mysqli_fetch_assoc($query)["ID"]));
	}
	
	public static function findPlantsByIDs($plantIDs){
		if(count($plantIDs) == 0){
			return array();
		}
		
		for($i = 0; $i < count($plantIDs); $i++){
			$plantIDs[$i] = intval($plantIDs[$i]);
		}
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `Plant` WHERE `ID` IN (" . implode(",", $plantIDs) . ")");
		
		//get associated sites
		$associatedSiteFKs = array();
		while($plantRow = mysqli_fetch_assoc($query)){
			$associatedSiteFKs[$plantRow["SiteFK"]] = 1;
		}
		$associatedSiteFKs = array_keys($associatedSiteFKs);
		
		$associatedSitesBySiteFK = array();
		$associatedSites = Site::findSitesByIDs($associatedSiteFKs);
		for($i = 0; $i < count($associatedSites); $i++){
			$associatedSitesBySiteFK[$associatedSites[$i]->getID()] = $associatedSites[$i];
		}
		
		//make plant objects
		$plantsArray = array();
		mysqli_data_seek($query, 0);
		while($plantRow = mysqli_fetch_assoc($query)){
			array_push($plantsArray, self::_constructFromRow($plantRow, $associatedSitesBySiteFK));
		}
		return $plantsArray;
	}
	
	public static function findPlantsBySite($site){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `Plant` WHERE `SiteFK`='" . $site->getID() . "' AND `Circle`>0");

                $sites = array();
                $sites[$site->getID()] = $site;

		$plantsArray = array();
		while($plantRow = mysqli_fetch_assoc($query)){
			
			array_push($plantsArray, self::_constructFromRow($plantRow, $sites));
		}
		return $plantsArray;
	}

//GETTERS
	public function getID() {
		if($this->deleted){return null;}
		return intval($this->id);
	}
	
	public function getSite() {
		if($this->deleted){return null;}
		return $this->site;
	}
	
	public function getSpecies() {
		if($this->deleted){return null;}
		return $this->species;
	}
	
	public function getCircle() {
		if($this->deleted){return null;}
		return intval($this->circle);
	}
	
	public function getOrientation() {
		if($this->deleted){return null;}
		return $this->orientation;
	}
	
	public function getIsConifer() {
		if($this->deleted){return null;}
		return filter_var($this->isConifer, FILTER_VALIDATE_BOOLEAN);
	}

	public function getLatitude() {
		if($this->deleted){return null;}
		return $this->latitude;
	}
	public function getLongitude() {
		if($this->deleted){return null;}
		return $this->longitude;
	}

	
	public function getColor(){
		if($this->deleted){return null;}
		if($this->orientation == "A"){
			return "#ff7575";//red
		}
		else if($this->orientation == "B"){
			return "#75b3ff";//blue
		}
		else if($this->orientation == "C"){
			return "#5abd61";//green
		}
		else if($this->orientation == "D"){
			return "#ffc875";//orange
		}
		else if($this->orientation == "E"){
			return "#9175ff";//purple
		}
		return false;
	}
	
	public function getCode() {
		if($this->deleted){return null;}
		return $this->code;
	}
	
//SETTERS
	public function setSpecies($species) {
		if(!$this->deleted){
			$species = rawurldecode($species);
			if($this->species == $species){return true;}
			$species = self::validSpecies("NO DBCONN NEEDED", $species);
			if($this->species == $species){return true;}
			
			//Update only if needed
			if($species !== false){
				$dbconn = (new Keychain)->getDatabaseConnection();
				mysqli_query($dbconn, "UPDATE Plant SET `Species`='$species' WHERE ID='" . $this->id . "'");
				$this->species = $species;
				return true;
			}
		}
		return false;
	}
	
	public function setCode($code){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$code = self::validCode($dbconn, $code);
			if($code !== false){
				mysqli_query($dbconn, "UPDATE Plant SET `Code`='$code' WHERE ID='" . $this->id . "'");
				$this->code = $code;
				return true;
			}
		}
		return false;
	}
	
	public function setCircle($circle){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$circle = self::validCircleFormat($dbconn, $circle);
			if($circle !== false){
				mysqli_query($dbconn, "UPDATE Plant SET `Circle`='$circle' WHERE ID='" . $this->id . "'");
				$this->circle = $circle;
				return true;
			}
		}
		return false;
	}
	
	public function setIsConifer($isConifer){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$isConifer = filter_var($isConifer, FILTER_VALIDATE_BOOLEAN);
			mysqli_query($dbconn, "UPDATE Plant SET `IsConifer`='$isConifer' WHERE ID='" . $this->id . "'");
			$this->isConifer = $isConifer;
			return true;
		}
		return false;
	}

	public function setLatitude($latitude){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
                        $latitude = Site::validLatitude($dbconn, $latitude);
			mysqli_query($dbconn, "UPDATE Plant SET `Latitude`='$latitude' WHERE ID='" . $this->id . "'");
			$this->latitude = $latitude;
			return true;
		}
		return false;
	}

	public function setLongitude($longitude){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
                        $longitude = Site::validLongitude($dbconn, $longitude);
			mysqli_query($dbconn, "UPDATE Plant SET `Longitude`='$longitude' WHERE ID='" . $this->id . "'");
			$this->longitude = $longitude;
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
			mysqli_query($dbconn, "DELETE FROM `Plant` WHERE `ID`='" . $this->id . "'");
			$this->deleted = true;
			return true;
		}
	}
	
	public static function permanentDeleteByIDs($ids)
	{
		if(is_array($ids) && count($ids) > 0)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			mysqli_query($dbconn, "DELETE FROM `Plant` WHERE `ID` IN ('" . implode("', '", $ids) . "')");
			return true;
		}
	}
	
	public static function permanentDeleteAllLooseEnds(){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `Plant`.`ID` FROM `Plant` LEFT JOIN `Site` ON `Plant`.`SiteFK`=`Site`.`ID` WHERE `Site`.`ID` IS NULL");
		$idsToDelete = array();
		while($row = mysqli_fetch_assoc($query)){
			$idsToDelete[] = $row["ID"];
		}
		self::permanentDeleteByIDs($idsToDelete);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//validity ensurance
	public static function validSite($dbconn, $site){
		if(is_object($site) && get_class($site) == "Site"){
			return $site;
		}
		return false;
	}
	
	public static function validCircleFormat($dbconn, $circle){
		$circle = intval(preg_replace("/[^0-9-]/", "", rawurldecode($circle)));
		if($circle !== 0){
			return $circle;
		}
		return false;
	}
	
	public static function validOrientationFormat($dbconn, $orientation){
		if(in_array($orientation, array("A", "B", "C", "D", "E"))){
			return $orientation;
		}
		return false;
	}
	
	public static function validCode($dbconn, $code){
		$code = mysqli_real_escape_string($dbconn, str_replace("0", "O", preg_replace('/\s+/', '', strtoupper(htmlentities(rawurldecode($code))))));
		
		if($code == ""){
			return false;
		}
		return $code;
	}
	
	public static function validSpecies($dbconn, $species){
		$species = rawurldecode($species);
		if(preg_replace('/\s+/', '', $species) == "" || trim(strtoupper($species)) == "N/A"){return false;}
		
		$species = trim($species);
		return ucfirst(strtolower(trim(preg_replace('!\s+!', ' ', $species))));
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//FUNCTIONS
	public static function IDToCode($id){
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
		$code = mysqli_real_escape_string($dbconn, htmlentities($code));
		return $code;
	}
}		
?>
