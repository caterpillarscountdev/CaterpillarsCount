<?php

require_once('resources/Customfunctions.php');
require_once('resources/Customlogging.php');
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
	private $pupa;
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
	public static function create($survey, $originalGroup, $length, $quantity, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $originalBeetleLarva) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
		
		$survey = self::validSurvey($dbconn, $survey);
		$originalGroup = self::validGroup($dbconn, $originalGroup);
		$length = self::validLength($dbconn, $length);
		$quantity = self::validQuantity($dbconn, $quantity);
		$notes = self::validNotes($dbconn, $notes);
		$pupa = custom_filter_var_bool($pupa);
		$hairy = custom_filter_var_bool($hairy);
		$rolled = custom_filter_var_bool($rolled);
		$tented = custom_filter_var_bool($tented);
		$originalSawfly = custom_filter_var_bool($originalSawfly);
		$originalBeetleLarva = custom_filter_var_bool($originalBeetleLarva);
		
		
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
		
		mysqli_query($dbconn, "INSERT INTO ArthropodSighting (`SurveyFK`, `OriginalGroup`, `UpdatedGroup`, `Length`, `Quantity`, `PhotoURL`, `Notes`, `Pupa`, `Hairy`, `Rolled`, `Tented`, `OriginalSawfly`, `UpdatedSawfly`, `OriginalBeetleLarva`, `UpdatedBeetleLarva`) VALUES ('" . $survey->getID() . "', '$originalGroup', '$originalGroup', '$length', '$quantity', '', '$notes', '$pupa', '$hairy', '$rolled', '$tented', '$originalSawfly', '$originalSawfly', '$originalBeetleLarva', '$originalBeetleLarva')");
		$id = intval(mysqli_insert_id($dbconn));
		mysqli_close($dbconn);
		
		return new ArthropodSighting($id, $survey, $originalGroup, $originalGroup, $length, $quantity, "", $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $originalSawfly, $originalBeetleLarva, $originalBeetleLarva, "");
	}
	private function __construct($id, $survey, $originalGroup, $updatedGroup, $length, $quantity, $photoURL, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $updatedSawfly, $originalBeetleLarva, $updatedBeetleLarva, $iNaturalistID){
		$this->id = intval($id);
		$this->survey = $survey;
		$this->originalGroup = $originalGroup;
		$this->updatedGroup = $updatedGroup;
		$this->length = intval($length);
		$this->quantity = intval($quantity);
		$this->photoURL = $photoURL;
		$this->notes = $notes;
		$this->pupa = custom_filter_var_bool($pupa);
		$this->hairy = custom_filter_var_bool($hairy);
		$this->rolled = custom_filter_var_bool($rolled);
		$this->tented = custom_filter_var_bool($tented);
		$this->originalSawfly = custom_filter_var_bool($originalSawfly);
		$this->updatedSawfly = custom_filter_var_bool($updatedSawfly);
		$this->originalBeetleLarva = custom_filter_var_bool($originalBeetleLarva);
		$this->updatedBeetleLarva = custom_filter_var_bool($updatedBeetleLarva);
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
		$pupa = $arthropodSightingRow["Pupa"];
		$hairy = $arthropodSightingRow["Hairy"];
		$rolled = $arthropodSightingRow["Rolled"];
		$tented = $arthropodSightingRow["Tented"];
		$originalSawfly = $arthropodSightingRow["OriginalSawfly"];
		$updatedSawfly = $arthropodSightingRow["UpdatedSawfly"];
		$originalBeetleLarva = $arthropodSightingRow["OriginalBeetleLarva"];
		$updatedBeetleLarva = $arthropodSightingRow["UpdatedBeetleLarva"];
		$iNaturalistID = $arthropodSightingRow["INaturalistID"];
		
		return new ArthropodSighting($id, $survey, $originalGroup, $updatedGroup, $length, $quantity, $photoURL, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $updatedSawfly, $originalBeetleLarva, $updatedBeetleLarva, $iNaturalistID);
	}
	
	public static function findArthropodSightingsByIDs($arthropodSightingIDs){
		if(count($arthropodSightingIDs) == 0){
			return array();
		}
		
		for($i = 0; $i < count($arthropodSightingIDs); $i++){
			$arthropodSightingIDs[$i] = intval($arthropodSightingIDs[$i]);
		}
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `ArthropodSighting` WHERE `ID` IN ('" . implode("', '", $arthropodSightingIDs) . "')");
		mysqli_close($dbconn);
		
		//get associated surveys
		$associatedSurveyFKs = array();
		while($arthropodSightingRow = mysqli_fetch_assoc($query)){
			$associatedSurveyFKs[$arthropodSightingRow["SurveyFK"]] = 1;
		}
		$associatedSurveyFKs = array_keys($associatedSurveyFKs);
		
		$associatedSurveysBySurveyFK = array();
		$associatedSurveys = Survey::findSurveysByIDs($associatedSurveyFKs);
		for($i = 0; $i < count($associatedSurveys); $i++){
			$associatedSurveysBySurveyFK[$associatedSurveys[$i]->getID()] = $associatedSurveys[$i];
		}
		
		//make arthropodsighting objects
		$arthropodSightingsArray = array();
		mysqli_data_seek($query, 0);
		while($arthropodSightingRow = mysqli_fetch_assoc($query)){
			$id = $arthropodSightingRow["ID"];
			$survey = array_key_exists($arthropodSightingRow["SurveyFK"], $associatedSurveysBySurveyFK) ? $associatedSurveysBySurveyFK[$arthropodSightingRow["SurveyFK"]] : null;
			$originalGroup = $arthropodSightingRow["OriginalGroup"];
			$updatedGroup = $arthropodSightingRow["UpdatedGroup"];
			$length = $arthropodSightingRow["Length"];
			$quantity = $arthropodSightingRow["Quantity"];
			$photoURL = $arthropodSightingRow["PhotoURL"];
			$notes = $arthropodSightingRow["Notes"];
			$pupa = $arthropodSightingRow["Pupa"];
			$hairy = $arthropodSightingRow["Hairy"];
			$rolled = $arthropodSightingRow["Rolled"];
			$tented = $arthropodSightingRow["Tented"];
			$originalSawfly = $arthropodSightingRow["OriginalSawfly"];
			$updatedSawfly = $arthropodSightingRow["UpdatedSawfly"];
			$originalBeetleLarva = $arthropodSightingRow["OriginalBeetleLarva"];
			$updatedBeetleLarva = $arthropodSightingRow["UpdatedBeetleLarva"];
			$iNaturalistID = $arthropodSightingRow["INaturalistID"];

			$arthropodSightingsArray[] = new ArthropodSighting($id, $survey, $originalGroup, $updatedGroup, $length, $quantity, $photoURL, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $updatedSawfly, $originalBeetleLarva, $updatedBeetleLarva, $iNaturalistID);
		}
		return $arthropodSightingsArray;
	}
	
	public static function findArthropodSightingsBySurvey($survey){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `ArthropodSighting` WHERE `SurveyFK`='" . $survey->getID() . "'");
		mysqli_close($dbconn);
		
		$arthropodSightingsArray = array();
		while($arthropodSightingRow = mysqli_fetch_assoc($query)){
			$id = $arthropodSightingRow["ID"];
			$originalGroup = $arthropodSightingRow["OriginalGroup"];
			$updatedGroup = $arthropodSightingRow["UpdatedGroup"];
			$length = $arthropodSightingRow["Length"];
			$quantity = $arthropodSightingRow["Quantity"];
			$photoURL = $arthropodSightingRow["PhotoURL"];
			$notes = $arthropodSightingRow["Notes"];
			$pupa = $arthropodSightingRow["Pupa"];
			$hairy = $arthropodSightingRow["Hairy"];
			$rolled = $arthropodSightingRow["Rolled"];
			$tented = $arthropodSightingRow["Tented"];
			$originalSawfly = $arthropodSightingRow["OriginalSawfly"];
			$updatedSawfly = $arthropodSightingRow["UpdatedSawfly"];
			$originalBeetleLarva = $arthropodSightingRow["OriginalBeetleLarva"];
			$updatedBeetleLarva = $arthropodSightingRow["UpdatedBeetleLarva"];
			$iNaturalistID = $arthropodSightingRow["INaturalistID"];

			$arthropodSightingsArray[] = new ArthropodSighting($id, $survey, $originalGroup, $updatedGroup, $length, $quantity, $photoURL, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $updatedSawfly, $originalBeetleLarva, $updatedBeetleLarva, $iNaturalistID);
		}
		return $arthropodSightingsArray;
	}
	
	public static function findArthropodSightingsBySurveys($surveys){
		$surveyIDs = array(-1);//make sure it's not empty
		$surveysByID = array();
		for($i = 0; $i < count($surveys); $i++){
			$surveyID = $surveys[$i]->getID();
			$surveysByID[$surveyID] = $surveys[$i];
			$surveyIDs[] = $surveyID;
		}
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `ArthropodSighting` WHERE `SurveyFK` IN (" . implode(",", $surveyIDs) . ")");
		mysqli_close($dbconn);
		
		$arthropodSightingsArray = array();
		while($arthropodSightingRow = mysqli_fetch_assoc($query)){
			$id = $arthropodSightingRow["ID"];
			$survey = $surveysByID[$arthropodSightingRow["SurveyFK"]];
			$originalGroup = $arthropodSightingRow["OriginalGroup"];
			$updatedGroup = $arthropodSightingRow["UpdatedGroup"];
			$length = $arthropodSightingRow["Length"];
			$quantity = $arthropodSightingRow["Quantity"];
			$photoURL = $arthropodSightingRow["PhotoURL"];
			$notes = $arthropodSightingRow["Notes"];
			$pupa = $arthropodSightingRow["Pupa"];
			$hairy = $arthropodSightingRow["Hairy"];
			$rolled = $arthropodSightingRow["Rolled"];
			$tented = $arthropodSightingRow["Tented"];
			$originalSawfly = $arthropodSightingRow["OriginalSawfly"];
			$updatedSawfly = $arthropodSightingRow["UpdatedSawfly"];
			$originalBeetleLarva = $arthropodSightingRow["OriginalBeetleLarva"];
			$updatedBeetleLarva = $arthropodSightingRow["UpdatedBeetleLarva"];
			$iNaturalistID = $arthropodSightingRow["INaturalistID"];

			$arthropodSightingsArray[] = new ArthropodSighting($id, $survey, $originalGroup, $updatedGroup, $length, $quantity, $photoURL, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $updatedSawfly, $originalBeetleLarva, $updatedBeetleLarva, $iNaturalistID);
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
		return html_entity_decode($this->notes);
	}
	
	public function getPupa() {
		if($this->deleted){return null;}
		return custom_filter_var_bool($this->pupa);
	}
	
	public function getHairy() {
		if($this->deleted){return null;}
		return custom_filter_var_bool($this->hairy);
	}
	
	public function getRolled() {
		if($this->deleted){return null;}
		return custom_filter_var_bool($this->rolled);
	}
	
	public function getTented() {
		if($this->deleted){return null;}
		return custom_filter_var_bool($this->tented);
	}
	
	public function getOriginalSawfly() {
		if($this->deleted){return null;}
		return custom_filter_var_bool($this->originalSawfly);
	}
	
	public function getUpdatedSawfly() {
		if($this->deleted){return null;}
		return custom_filter_var_bool($this->updatedSawfly);
	}
	
	public function getOriginalBeetleLarva() {
		if($this->deleted){return null;}
		return custom_filter_var_bool($this->originalBeetleLarva);
	}
	
	public function getUpdatedBeetleLarva() {
		if($this->deleted){return null;}
		return custom_filter_var_bool($this->updatedBeetleLarva);
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
	
	
	public function setAllEditables($originalGroup, $length, $quantity, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $originalBeetleLarva){
		if(!$this->deleted)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			$originalGroup = self::validGroup($dbconn, $originalGroup);
			$length = self::validLength($dbconn, $length);
			$quantity = self::validQuantity($dbconn, $quantity);
			$notes = self::validNotes($dbconn, $notes);
			//$pupa = filter_var($pupa, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			$pupa = custom_filter_var_bool($pupa);
			$hairy = custom_filter_var_bool($hairy);
			$rolled = custom_filter_var_bool($rolled);
			$tented = custom_filter_var_bool($tented);
			$originalSawfly = custom_filter_var_bool($originalSawfly);
			$originalBeetleLarva = custom_filter_var_bool($originalBeetleLarva);
			
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
			$updatedGroup = $originalGroup;
			$updatedSawfly = $originalSawfly;
			$updatedBeetleLarva = $originalBeetleLarva;
			$query = mysqli_query($dbconn, "SELECT `StandardGroup`, `SawflyUpdated`, `BeetleLarvaUpdated` FROM `ExpertIdentification` WHERE `ArthropodSightingFK`='" . $this->id . "'");
			if(mysqli_num_rows($query) > 0){
				$row = mysqli_fetch_assoc($query);
				$updatedGroup = $row["StandardGroup"];
				$updatedSawfly = $row["SawflyUpdated"];
				$updatedBeetleLarva = $row["BeetleLarvaUpdated"];
			}
			
			$as_result = mysqli_query($dbconn, "UPDATE ArthropodSighting SET `OriginalGroup`='$originalGroup', `UpdatedGroup`='$updatedGroup', `Length`='$length', `Quantity`='$quantity', `Notes`='$notes', `Pupa`='$pupa', `Hairy`='$hairy', `Rolled`='$rolled', `Tented`='$tented', `OriginalSawfly`='$originalSawfly', `UpdatedSawfly`='$updatedSawfly', `OriginalBeetleLarva`='$originalBeetleLarva', `UpdatedBeetleLarva`='$updatedBeetleLarva' WHERE ID='" . $this->id . "'");
			
			if (!($as_result===true)) {
				$failures = 'SQL failed';
				mysqli_close($dbconn);
				return $failures;
			}
			
			mysqli_close($dbconn);

			$this->originalGroup = $originalGroup;
			$this->length = $length;
			$this->quantity = $quantity;
			$this->notes = $notes;
			$this->pupa = custom_filter_var_bool($pupa);
			$this->hairy = custom_filter_var_bool($hairy);
			$this->rolled = custom_filter_var_bool($rolled);
			$this->tented = custom_filter_var_bool($tented);
			$this->originalSawfly = custom_filter_var_bool($originalSawfly);
			$this->originalBeetleLarva = custom_filter_var_bool($originalBeetleLarva);

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
	
	public static function permanentDeleteByIDs($ids)
	{
		if(is_array($ids) && count($ids) > 0)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			mysqli_query($dbconn, "DELETE FROM `ArthropodSighting` WHERE `ID` IN ('" . implode("', '", $ids) . "')");
			mysqli_close($dbconn);
			return true;
		}
	}
	
	public static function permanentDeleteAllLooseEnds(){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `ArthropodSighting`.`ID` FROM `ArthropodSighting` LEFT JOIN `Survey` ON `ArthropodSighting`.`SurveyFK`=`Survey`.`ID` WHERE `Survey`.`ID` IS NULL");
		mysqli_close($dbconn);
		$idsToDelete = array();
		while($row = mysqli_fetch_assoc($query)){
			$idsToDelete[] = $row["ID"];
		}
		self::permanentDeleteByIDs($idsToDelete);
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
