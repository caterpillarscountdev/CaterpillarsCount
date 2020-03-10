<?php

require_once('resources/Keychain.php');
require_once('Survey.php');

class ArthropodSighting
{
//PRIVATE VARS
	private $id;							//INT
	private $survey;
	private $originalGroup;
	private $updatedGroup;
	private $length;
	private $quantity;
	private $photoURL;
	private $notes;
	private $hairy;
	private $rolled;
	private $tented;
	private $originalSawfly;
	private $updatedSawfly;
	private $originalBeetleLarva;
	private $updatedBeetleLarva;
	private $iNaturalistID;
	
	private $deleted;

//FACTORY
	public static function create($survey, $originalGroup, $length, $quantity, $notes, $hairy, $rolled, $tented, $originalSawfly, $originalBeetleLarva) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
		
		$survey = self::validSurvey($dbconn, $survey);
		$originalGroup = self::validGroup($dbconn, $originalGroup);
		$length = self::validLength($dbconn, $length);
		$quantity = self::validQuantity($dbconn, $quantity);
		$notes = self::validNotes($dbconn, $notes);
		$hairy = filter_var($hairy, FILTER_VALIDATE_BOOLEAN);
		$rolled = filter_var($rolled, FILTER_VALIDATE_BOOLEAN);
		$tented = filter_var($tented, FILTER_VALIDATE_BOOLEAN);
		$originalSawfly = filter_var($originalSawfly, FILTER_VALIDATE_BOOLEAN);
		$originalBeetleLarva = filter_var($originalBeetleLarva, FILTER_VALIDATE_BOOLEAN);
		
		
		$failures = "";
		
		if($originalGroup === false){
			$originalGroup = "Invalid arthropod group";
			$failures .= "Invalid arthropod group. ";
		}
		if($survey === false){
			$failures .= $originalGroup . " is attached to an invalid survey. ";
		}
		if($length === false){
			$failures .= $originalGroup . " length must be between 1mm and 300mm. ";
		}
		if($quantity === false){
			$failures .= $originalGroup . " quantity must be between 1 and 1000. ";
		}
		if($notes === false){
			$failures .= "Invalid " . $originalGroup . " notes. ";
		}
		
		if($failures != ""){
			return $failures;
		}
		
		mysqli_query($dbconn, "INSERT INTO ArthropodSighting (`SurveyFK`, `OriginalGroup`, `UpdatedGroup`, `Length`, `Quantity`, `PhotoURL`, `Notes`, `Hairy`, `Rolled`, `Tented`, `OriginalSawfly`, `UpdatedSawfly`, `OriginalBeetleLarva`, `UpdatedBeetleLarva`) VALUES ('" . $survey->getID() . "', '$originalGroup', '$originalGroup', '$length', '$quantity', '', '$notes', '$hairy', '$rolled', '$tented', '$originalSawfly', '$originalSawfly', '$originalBeetleLarva', '$originalBeetleLarva')");
		$id = intval(mysqli_insert_id($dbconn));
		mysqli_close($dbconn);
		
		return new ArthropodSighting($id, $survey, $originalGroup, $originalGroup, $length, $quantity, "", $notes, $hairy, $rolled, $tented, $originalSawfly, $originalSawfly, $originalBeetleLarva, $originalBeetleLarva, "");
	}
	private function __construct($id, $survey, $originalGroup, $updatedGroup, $length, $quantity, $photoURL, $notes, $hairy, $rolled, $tented, $originalSawfly, $updatedSawfly, $originalBeetleLarva, $updatedBeetleLarva, $iNaturalistID){
		$this->id = intval($id);
		$this->survey = $survey;
		$this->originalGroup = $originalGroup;
		$this->updatedGroup = $updatedGroup;
		$this->length = intval($length);
		$this->quantity = intval($quantity);
		$this->photoURL = $photoURL;
		$this->notes = $notes;
		$this->hairy = filter_var($hairy, FILTER_VALIDATE_BOOLEAN);
		$this->rolled = filter_var($rolled, FILTER_VALIDATE_BOOLEAN);
		$this->tented = filter_var($tented, FILTER_VALIDATE_BOOLEAN);
		$this->originalSawfly = filter_var($originalSawfly, FILTER_VALIDATE_BOOLEAN);
		$this->updatedSawfly = filter_var($updatedSawfly, FILTER_VALIDATE_BOOLEAN);
		$this->originalBeetleLarva = filter_var($originalBeetleLarva, FILTER_VALIDATE_BOOLEAN);
		$this->updatedBeetleLarva = filter_var($updatedBeetleLarva, FILTER_VALIDATE_BOOLEAN);
		$this->iNaturalistID = intval($iNaturalistID);
		
		$this->deleted = false;
	}

//FINDERS
	public static function findByID($id) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = mysqli_real_escape_string($dbconn, htmlentities($id));
		$query = mysqli_query($dbconn, "SELECT * FROM `ArthropodSighting` WHERE `ID`='$id' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$arthropodSightingRow = mysqli_fetch_assoc($query);
		
		$survey = Survey::findByID($arthropodSightingRow["SurveyFK"]);
		$originalGroup = $arthropodSightingRow["OriginalGroup"];
		$updatedGroup = $arthropodSightingRow["UpdatedGroup"];
		$length = $arthropodSightingRow["Length"];
		$quantity = $arthropodSightingRow["Quantity"];
		$photoURL = $arthropodSightingRow["PhotoURL"];
		$notes = $arthropodSightingRow["Notes"];
		$hairy = $arthropodSightingRow["Hairy"];
		$rolled = $arthropodSightingRow["Rolled"];
		$tented = $arthropodSightingRow["Tented"];
		$originalSawfly = $arthropodSightingRow["OriginalSawfly"];
		$updatedSawfly = $arthropodSightingRow["UpdatedSawfly"];
		$originalBeetleLarva = $arthropodSightingRow["OriginalBeetleLarva"];
		$updatedBeetleLarva = $arthropodSightingRow["UpdatedBeetleLarva"];
		$iNaturalistID = $arthropodSightingRow["INaturalistID"];
		
		return new ArthropodSighting($id, $survey, $originalGroup, $updatedGroup, $length, $quantity, $photoURL, $notes, $hairy, $rolled, $tented, $originalSawfly, $updatedSawfly, $originalBeetleLarva, $updatedBeetleLarva, $iNaturalistID);
	}
	
	public static function findArthropodSightingsBySurvey($survey){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `ArthropodSighting` WHERE `SurveyFK`='" . $survey->getID() . "'");
		mysqli_close($dbconn);
		
		$arthropodSightingsArray = array();
		while($arthropodSightingRow = mysqli_fetch_assoc($query)){
			$id = $arthropodSightingRow["ID"];
			$survey = Survey::findByID($arthropodSightingRow["SurveyFK"]);
			$originalGroup = $arthropodSightingRow["OriginalGroup"];
			$updatedGroup = $arthropodSightingRow["UpdatedGroup"];
			$length = $arthropodSightingRow["Length"];
			$quantity = $arthropodSightingRow["Quantity"];
			$photoURL = $arthropodSightingRow["PhotoURL"];
			$notes = $arthropodSightingRow["Notes"];
			$hairy = $arthropodSightingRow["Hairy"];
			$rolled = $arthropodSightingRow["Rolled"];
			$tented = $arthropodSightingRow["Tented"];
			$originalSawfly = $arthropodSightingRow["OriginalSawfly"];
			$updatedSawfly = $arthropodSightingRow["UpdatedSawfly"];
			$originalBeetleLarva = $arthropodSightingRow["OriginalBeetleLarva"];
			$updatedBeetleLarva = $arthropodSightingRow["UpdatedBeetleLarva"];
			$iNaturalistID = $arthropodSightingRow["INaturalistID"];

			$arthropodSightingsArray[] = new ArthropodSighting($id, $survey, $originalGroup, $updatedGroup, $length, $quantity, $photoURL, $notes, $hairy, $rolled, $tented, $originalSawfly, $updatedSawfly, $originalBeetleLarva, $updatedBeetleLarva, $iNaturalistID);
		}
		return $arthropodSightingsArray;
	}

//GETTERS
	public function getID() {
		if($this->deleted){return null;}
		return intval($this->id);
	}
	
	public function getSurvey() {
		if($this->deleted){return null;}
		return $this->survey;
	}
	
	public function getOriginalGroup() {
		if($this->deleted){return null;}
		return $this->originalGroup;
	}
	
	public function getUpdatedGroup() {
		if($this->deleted){return null;}
		return $this->updatedGroup;
	}
	
	public function getLength() {
		if($this->deleted){return null;}
		return intval($this->length);
	}
	
	public function getQuantity() {
		if($this->deleted){return null;}
		return intval($this->quantity);
	}
	
	public function getPhotoURL() {
		if($this->deleted){return null;}
		return $this->photoURL;
	}
	
	public function getNotes() {
		if($this->deleted){return null;}
		return $this->notes;
	}
	
	public function getHairy() {
		if($this->deleted){return null;}
		return filter_var($this->hairy, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getRolled() {
		if($this->deleted){return null;}
		return filter_var($this->rolled, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getTented() {
		if($this->deleted){return null;}
		return filter_var($this->tented, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getOriginalSawfly() {
		if($this->deleted){return null;}
		return filter_var($this->originalSawfly, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getUpdatedSawfly() {
		if($this->deleted){return null;}
		return filter_var($this->updatedSawfly, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getOriginalBeetleLarva() {
		if($this->deleted){return null;}
		return filter_var($this->originalBeetleLarva, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getUpdatedBeetleLarva() {
		if($this->deleted){return null;}
		return filter_var($this->updatedBeetleLarva, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getINaturalistID() {
		if($this->deleted){return null;}
		return intval($this->iNaturalistID);
	}
	
	public function getINaturalistObservationURL() {
		if($this->deleted){return null;}
		return "https://www.inaturalist.org/observations/" . intval($this->iNaturalistID);
	}
	
//SETTERS
	public function setPhotoURL($photoURL, $needToSendToINaturalist){
		if(!$this->deleted)
		{
			$needToSendToINaturalist = (int)$needToSendToINaturalist;
			$dbconn = (new Keychain)->getDatabaseConnection();
			$photoURL = self::validPhotoURL($dbconn, $photoURL);
			if($photoURL !== false){
				if($photoURL == "" || $this->survey->getPlant()->getSite()->getName() == "Example Site"){
					$needToSendToINaturalist = 0;
				}
				mysqli_query($dbconn, "UPDATE ArthropodSighting SET PhotoURL='$photoURL', NeedToSendToINaturalist='$needToSendToINaturalist' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->photoURL = $photoURL;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setAllEditables($originalGroup, $length, $quantity, $notes, $hairy, $rolled, $tented, $originalSawfly, $originalBeetleLarva){
		if(!$this->deleted)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			
			$originalGroup = self::validGroup($dbconn, $originalGroup);
			$length = self::validLength($dbconn, $length);
			$quantity = self::validQuantity($dbconn, $quantity);
			$notes = self::validNotes($dbconn, $notes);
			$hairy = filter_var($hairy, FILTER_VALIDATE_BOOLEAN);
			$rolled = filter_var($rolled, FILTER_VALIDATE_BOOLEAN);
			$tented = filter_var($tented, FILTER_VALIDATE_BOOLEAN);
			$originalSawfly = filter_var($originalSawfly, FILTER_VALIDATE_BOOLEAN);
			$originalBeetleLarva = filter_var($originalBeetleLarva, FILTER_VALIDATE_BOOLEAN);
			
			$failures = "";
		
			if($originalGroup === false){
				$originalGroup = "Invalid arthropod group";
				$failures .= "Invalid arthropod group. ";
			}
			if($length === false){
				$failures .= $originalGroup . " length must be between 1mm and 300mm. ";
			}
			if($quantity === false){
				$failures .= $originalGroup . " quantity must be between 1 and 1000. ";
			}
			if($notes === false){
				$failures .= "Invalid " . $originalGroup . " notes. ";
			}

			if($failures != ""){
				return $failures;
			}
			
			mysqli_query($dbconn, "UPDATE ArthropodSighting SET `OriginalGroup`='$originalGroup', `Length`='$length', `Quantity`='$quantity', `Notes`='$notes', `Hairy`='$hairy', `Rolled`='$rolled', `Tented`='$tented', `OriginalSawfly`='$originalSawfly', `OriginalBeetleLarva`='$originalBeetleLarva' WHERE ID='" . $this->id . "'");
			mysqli_close($dbconn);

			$this->originalGroup = $originalGroup;
			$this->length = $length;
			$this->quantity = $quantity;
			$this->notes = $notes;
			$this->hairy = filter_var($hairy, FILTER_VALIDATE_BOOLEAN);
			$this->rolled = filter_var($rolled, FILTER_VALIDATE_BOOLEAN);
			$this->tented = filter_var($tented, FILTER_VALIDATE_BOOLEAN);
			$this->originalSawfly = filter_var($originalSawfly, FILTER_VALIDATE_BOOLEAN);
			$this->originalBeetleLarva = filter_var($originalBeetleLarva, FILTER_VALIDATE_BOOLEAN);

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
			mysqli_query($dbconn, "DELETE FROM `ArthropodSighting` WHERE `ID`='" . $this->id . "'");
			$this->deleted = true;
			mysqli_close($dbconn);
			return true;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//validity ensurance
	public static function validSurvey($dbconn, $survey){
		if(is_object($survey) && get_class($survey) == "Survey"){
			return $survey;
		}
		return false;
	}
	
	public static function validGroup($dbconn, $group){
		$group = trim(rawurldecode($group));
		$groups = array("ant", "aphid", "bee", "beetle", "caterpillar", "daddylonglegs", "fly", "grasshopper", "leafhopper", "moths", "spider", "truebugs", "other", "unidentified");
		if(in_array($group, $groups)){
			return $group;
		}
		return false;
	}
	
	public static function validLength($dbconn, $length){
		$length = intval(preg_replace("/[^0-9]/", "", rawurldecode($length)));
		if($length < 1 || $length > 300){
			return false;
		}
		return $length;
	}
	
	public static function validQuantity($dbconn, $quantity){
		$quantity = intval(preg_replace("/[^0-9]/", "", rawurldecode($quantity)));
		if($quantity < 1 || $quantity > 1000){
			return false;
		}
		return $quantity;
	}
	
	public static function validPhotoURL($dbconn, $photoURL){
		//TODO: validate domain
		return mysqli_real_escape_string($dbconn, htmlentities(rawurldecode($photoURL)));
	}
	
	public static function validNotes($dbconn, $notes){
		return mysqli_real_escape_string($dbconn, htmlentities(rawurldecode($notes)));
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//FUNCTIONS
	//none
}		
?>
