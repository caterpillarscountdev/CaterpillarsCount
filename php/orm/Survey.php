<?php

require_once('resources/Keychain.php');
require_once('User.php');
require_once('Plant.php');
require_once('ArthropodSighting.php');

class Survey
{
//PRIVATE VARS
	private $id;							//INT
	private $submissionTimestamp;					//INT: seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) marking time that survey was submitted to our database. NOT the time entered by the user into the survey.
	private $observer;
	private $plant;
	private $localDate;						//the time entered by the user into the survey
	private $localTime;
	private $observationMethod;
	private $notes;
	private $wetLeaves;
	private $plantSpecies;
	private $numberOfLeaves;
	private $averageLeafLength;
	private $herbivoryScore;
	private $submittedThroughApp;
	
	private $deleted;

//FACTORY
	public static function create($observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $submittedThroughApp) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
		
		
		$submissionTimestamp = time();
		$observer = self::validObserver($dbconn, $observer, $plant);
		$plant = self::validPlant($dbconn, $plant);
		$localDate = self::validLocalDate($dbconn, $localDate);
		$localTime = self::validLocalTime($dbconn, $localTime);
		$observationMethod = self::validObservationMethod($dbconn, $observationMethod);
		$notes = self::validNotes($dbconn, $notes);
		$wetLeaves = filter_var($wetLeaves, FILTER_VALIDATE_BOOLEAN);
		$plantSpecies = self::validPlantSpecies($dbconn, $plantSpecies, $plant);
		$numberOfLeaves = self::validNumberOfLeaves($dbconn, $numberOfLeaves);
		$averageLeafLength = self::validAverageLeafLength($dbconn, $averageLeafLength);
		$herbivoryScore = self::validHerbivoryScore($dbconn, $herbivoryScore);
		$submittedThroughApp = filter_var($submittedThroughApp, FILTER_VALIDATE_BOOLEAN);
		
		
		$failures = "";
		
		if($plant === false){
			$failures .= "Invalid plant. ";
		}
		else if($observer === false){
			$failures .= "You have not been authenticated for this site. ";
		}
		if($localDate === false){
			$failures .= "Invalid date of survey. ";
		}
		if($localTime === false){
			$failures .= "Invalid time of survey. ";
		}
		if($observationMethod === false){
			$failures .= "Select an observation method. ";
		}
		if($notes === false){
			$failures .= "Invalid notes. ";
		}
		if($plantSpecies === false){
			$failures .= "Invalid plant species. ";
		}
		if($numberOfLeaves === false){
			$failures .= "Number of leaves must be between 1 and 500. ";
		}
		if($averageLeafLength === false){
			$failures .= "Average leaf length must be between 1cm and 60cm. ";
		}
		if($herbivoryScore === false){
			$failures .= "Select an herbivory score. ";
		}
		
		if($failures != ""){
			return $failures;
		}
		
		mysqli_query($dbconn, "INSERT INTO Survey (`SubmissionTimestamp`, `UserFKOfObserver`, `PlantFK`, `LocalDate`, `LocalTime`, `ObservationMethod`, `Notes`, `WetLeaves`, `PlantSpecies`, `NumberOfLeaves`, `AverageLeafLength`, `HerbivoryScore`, `SubmittedThroughApp`) VALUES ('" . $submissionTimestamp . "', '" . $observer->getID() . "', '" . $plant->getID() . "', '$localDate', '$localTime', '$observationMethod', '$notes', '$wetLeaves', '$plantSpecies', '$numberOfLeaves', '$averageLeafLength', '$herbivoryScore', '$submittedThroughApp')");
		$id = intval(mysqli_insert_id($dbconn));
		mysqli_close($dbconn);
		
		return new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $submittedThroughApp);
	}
	private function __construct($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $submittedThroughApp) {
		$this->id = intval($id);
		$this->submissionTimestamp = intval($submissionTimestamp);
		$this->observer = $observer;
		$this->plant = $plant;
		$this->localDate = $localDate;
		$this->localTime = $localTime;
		$this->observationMethod = $observationMethod;
		$this->notes = $notes;
		$this->wetLeaves = filter_var($wetLeaves, FILTER_VALIDATE_BOOLEAN);
		$this->plantSpecies = $plantSpecies;
		$this->numberOfLeaves = intval($numberOfLeaves);
		$this->averageLeafLength = intval($averageLeafLength);
		$this->herbivoryScore = max(0, intval($herbivoryScore));
		$this->submittedThroughApp = $submittedThroughApp;
		
		$this->deleted = false;
	}

//FINDERS
	public static function findByID($id) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = mysqli_real_escape_string($dbconn, $id);
		$query = mysqli_query($dbconn, "SELECT * FROM `Survey` WHERE `ID`='$id' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$surveyRow = mysqli_fetch_assoc($query);
		
		$submissionTimestamp = intval($surveyRow["SubmissionTimestamp"]);
		$observer = User::findByID($surveyRow["UserFKOfObserver"]);
		$plant = Plant::findByID($surveyRow["PlantFK"]);
		$localDate = $surveyRow["LocalDate"];
		$localTime = $surveyRow["LocalTime"];
		$observationMethod = $surveyRow["ObservationMethod"];
		$notes = $surveyRow["Notes"];
		$wetLeaves = $surveyRow["WetLeaves"];
		$plantSpecies = $surveyRow["PlantSpecies"];
		$numberOfLeaves = $surveyRow["NumberOfLeaves"];
		$averageLeafLength = $surveyRow["AverageLeafLength"];
		$herbivoryScore = $surveyRow["HerbivoryScore"];
		$submittedThroughApp = $surveyRow["SubmittedThroughApp"];
		
		return new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $submittedThroughApp);
	}
	
	public static function findSurveysByUser($user, $filters, $start, $limit) {
		//returns all surveys user has completed
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$start = mysqli_real_escape_string($dbconn, ($start . ""));
		$limit = mysqli_real_escape_string($dbconn, ($limit . ""));
		$filterKeys = array_keys($filters);
		foreach($filterKeys as $filterKey) {
			$filters[$filterKey] = mysqli_real_escape_string($dbconn, ($filters[$filterKey] . ""));
		}
		
		$surveysArray = array();
		//as well as all surveys completed at sites the user created or manages
		$sites = $user->getSites();
		$siteIDs = array(-1);
		for($i = 0; $i < count($sites); $i++){
			$siteIDs[] = $sites[$i]->getID();
		}
		
		$baseTable = "`Survey`";
		$additionalSQL = "";
		$groupBy = "";
		
		$arthropodSearch = trim($filters["arthropod"]);
		if(strlen($arthropodSearch) > 0){
			$baseTable = "`ArthropodSighting` JOIN `Survey` ON ArthropodSighting.SurveyFK = Survey.ID";
			$additionalSQL .= " AND ArthropodSighting.Group='" . $arthropodSearch . "'";
			$groupBy = " GROUP BY ArthropodSighting.SurveyFK";
		}
		
		$userSearch = trim($filters["user"]);
		if(strlen($userSearch) > 0){
			$additionalSQL .= " AND CONCAT(User.FirstName, ' ', User.LastName) LIKE '%" . $userSearch . "%'";
		}
		
		$siteSearch = trim(strval($filters["site"]));
		$circleSearch = trim(strval($filters["circle"]));
		$codeSearch = trim($filters["code"]);
		
		if(strlen($codeSearch) > 0){
			$additionalSQL .= " AND Plant.Code='" . $codeSearch . "'";
		}
		else if(strlen($siteSearch) > 0 && is_numeric($siteSearch)){
			$additionalSQL .= " AND Plant.SiteFK='" . $siteSearch . "'";
			if(strlen($circleSearch) > 0 && is_numeric($circleSearch)){
				$additionalSQL .= " AND Plant.Circle='" . $circleSearch . "'";
			}
		}
		
		$dateSearch = mysqli_real_escape_string($dbconn, trim(strval($filters["date"])));
		
		$totalCount = intval(mysqli_fetch_assoc(mysqli_query($dbconn, "SELECT COUNT(*) AS `Count` FROM (SELECT DISTINCT Survey.ID FROM " . $baseTable . " JOIN `Plant` ON Survey.PlantFK = Plant.ID JOIN `User` ON Survey.UserFKOfObserver=User.ID WHERE (Plant.SiteFK IN (" . join(",", $siteIDs) . ") OR Survey.UserFKOfObserver='" . $user->getID() . "') AND Survey.LocalDate LIKE '" . $dateSearch . "'" . $additionalSQL . $groupBy . ") AS Results"))["Count"]);
		if($limit === "max"){
			$limit = $totalCount;
		}
		if($start === "last"){
			$start = $totalCount - ($totalCount % intval($limit));
			if($start == $totalCount && $totalCount > 0){
				$start = $totalCount - intval($limit);
			}
		}
		$query = mysqli_query($dbconn, "SELECT Survey.* FROM " . $baseTable . " JOIN `Plant` ON Survey.PlantFK = Plant.ID JOIN `User` ON Survey.UserFKOfObserver=User.ID WHERE (Plant.SiteFK IN (" . join(",", $siteIDs) . ") OR Survey.UserFKOfObserver='" . $user->getID() . "') AND Survey.LocalDate LIKE '" . $dateSearch . "'" . $additionalSQL . $groupBy . " ORDER BY Survey.LocalDate DESC, Survey.LocalTime DESC, Plant.Code DESC LIMIT " . $start . ", " . $limit);
		while($surveyRow = mysqli_fetch_assoc($query)){
			$id = $surveyRow["ID"];
			$submissionTimestamp = intval($surveyRow["SubmissionTimestamp"]);
			$observer = User::findByID($surveyRow["UserFKOfObserver"]);
			$plant = Plant::findByID($surveyRow["PlantFK"]);
			$localDate = $surveyRow["LocalDate"];
			$localTime = $surveyRow["LocalTime"];
			$observationMethod = $surveyRow["ObservationMethod"];
			$notes = $surveyRow["Notes"];
			$wetLeaves = $surveyRow["WetLeaves"];
			$plantSpecies = $surveyRow["PlantSpecies"];
			$numberOfLeaves = $surveyRow["NumberOfLeaves"];
			$averageLeafLength = $surveyRow["AverageLeafLength"];
			$herbivoryScore = $surveyRow["HerbivoryScore"];
			$submittedThroughApp = $surveyRow["SubmittedThroughApp"];
			
			$survey = new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $submittedThroughApp);
			
			array_push($surveysArray, $survey);
		}
		return array($totalCount, $surveysArray);
	}

//GETTERS
	public function getID() {
		if($this->deleted){return null;}
		return intval($this->id);
	}
	
	public function getSubmissionTimestamp() {
		if($this->deleted){return null;}
		return intval($this->submissionTimestamp);
	}
	
	public function getObserver() {
		if($this->deleted){return null;}
		return $this->observer;
	}
	
	public function getPlant() {
		if($this->deleted){return null;}
		return $this->plant;
	}
	
	public function getLocalDate() {
		if($this->deleted){return null;}
		return $this->localDate;
	}
	
	public function getLocalTime() {
		if($this->deleted){return null;}
		return $this->localTime;
	}
	
	public function getObservationMethod() {
		if($this->deleted){return null;}
		return $this->observationMethod;
	}
	
	public function getNotes() {
		if($this->deleted){return null;}
		return $this->notes;
	}
	
	public function getWetLeaves() {
		if($this->deleted){return null;}
		return filter_var($this->wetLeaves, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getArthropodSightings() {
		if($this->deleted){return null;}
		return ArthropodSighting::findArthropodSightingsBySurvey($this);
	}
	
	public function getPlantSpecies() {
		if($this->deleted){return null;}
		return $this->plantSpecies;
	}
	
	public function getNumberOfLeaves() {
		if($this->deleted){return null;}
		return intval($this->numberOfLeaves);
	}
	
	public function getAverageLeafLength() {
		if($this->deleted){return null;}
		return intval($this->averageLeafLength);
	}
	
	public function getHerbivoryScore() {
		if($this->deleted){return null;}
		return $this->herbivoryScore;
	}
	
	public function getSubmittedThroughApp(){
		if($this->deleted){return null;}
		return filter_var($this->submittedThroughApp, FILTER_VALIDATE_BOOLEAN);
	}
	
//SETTERS
	public function setPlant($plant){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$plant = self::validPlant($dbconn, $plant);
			if($plant !== false){
				mysqli_query($dbconn, "UPDATE Survey SET PlantFK='" . $plant->getID() . "' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->plant = $plant;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setLocalDate($localDate){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$localDate = self::validLocalDate($dbconn, $localDate);
			if($localDate !== false){
				mysqli_query($dbconn, "UPDATE Survey SET LocalDate='$localDate' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->localDate = $localDate;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setLocalTime($localTime){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$localTime = self::validLocalTime($dbconn, $localTime);
			if($localTime !== false){
				mysqli_query($dbconn, "UPDATE Survey SET LocalTime='$localTime' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->localTime = $localTime;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setObservationMethod($observationMethod){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$observationMethod = self::validObservationMethod($dbconn, $observationMethod);
			if($observationMethod !== false){
				mysqli_query($dbconn, "UPDATE Survey SET ObservationMethod='$observationMethod' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->observationMethod = $observationMethod;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setNotes($notes){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$notes = self::validNotes($dbconn, $notes);
			if($notes !== false){
				mysqli_query($dbconn, "UPDATE Survey SET Notes='$notes' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->notes = $notes;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setWetLeaves($wetLeaves){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$wetLeaves = filter_var($wetLeaves, FILTER_VALIDATE_BOOLEAN);
			mysqli_query($dbconn, "UPDATE Survey SET WetLeaves='$wetLeaves' WHERE ID='" . $this->id . "'");
			mysqli_close($dbconn);
			$this->wetLeaves = $wetLeaves;
			return true;
		}
		return false;
	}
	
	public function setPlantSpecies($plantSpecies){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$plantSpecies = self::validPlantSpecies($dbconn, $plantSpecies);
			if($plantSpecies !== false){
				mysqli_query($dbconn, "UPDATE Survey SET PlantSpecies='$plantSpecies' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->plantSpecies = $plantSpecies;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setNumberOfLeaves($numberOfLeaves){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$numberOfLeaves = self::validNumberOfLeaves($dbconn, $numberOfLeaves);
			if($numberOfLeaves !== false){
				mysqli_query($dbconn, "UPDATE Survey SET NumberOfLeaves='$numberOfLeaves' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->numberOfLeaves = $numberOfLeaves;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setAverageLeafLength($averageLeafLength){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$averageLeafLength = self::validAverageLeafLength($dbconn, $averageLeafLength);
			if($averageLeafLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET AverageLeafLength='$averageLeafLength' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->averageLeafLength = $averageLeafLength;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setHerbivoryScore($herbivoryScore){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$herbivoryScore = self::validHerbivoryScore($dbconn, $herbivoryScore);
			if($herbivoryScore !== false){
				mysqli_query($dbconn, "UPDATE Survey SET HerbivoryScore='$herbivoryScore' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->herbivoryScore = $herbivoryScore;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	
//REMOVER
	public function permanentDelete()
	{
		if(!$this->deleted)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			$arthropodSightings = ArthropodSighting::findArthropodSightingsBySurvey($this);
			for($i = 0; $i < count($arthropodSightings); $i++){
				$arthropodSightings[$i]->permanentDelete();
			}
			mysqli_query($dbconn, "DELETE FROM `Survey` WHERE `ID`='" . $this->id . "'");
			$this->deleted = true;
			mysqli_close($dbconn);
			return true;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//validity ensurance
	public static function validObserver($dbconn, $observer, $plant){
		if(!is_object($plant) || get_class($plant) != "Plant" || !$plant->getSite()->validateUser($observer, "")){
			return false;
		}
		return $observer;
	}
	
	public static function validPlant($dbconn, $plant){
		if(!is_object($plant) || get_class($plant) != "Plant"){
			return false;
		}
		return $plant;
	}
	
	public static function validLocalDate($dbconn, $localDate){
		$localDate = rawurldecode($localDate);
		if(strlen($localDate) == 10){
			$year = intval(substr($localDate, 0, 4));
			$month = intval(substr($localDate, 5, 2));
			$day = intval(substr($localDate, 8, 2));
			if($year >= 1980 && $year <= 2200 && checkdate($month, $day, $year)){
				if($month < 10){$month = "0" . $month;}
				if($day < 10){$day = "0" . $day;}
				return $year . "-" . $month . "-" . $day;
			}
		}
		return false;
	}
	
	public static function validLocalTime($dbconn, $localTime){
		$localTime = rawurldecode($localTime);
		if(strlen($localTime) == 8){
			$hours = intval(substr($localTime, 0, 2));
			$minutes = intval(substr($localTime, 3, 2));
			$seconds = intval(substr($localTime, 6, 2));
			if($hours >= 0 && $hours <=23 && $minutes >=0 && $minutes <= 59 && $seconds == 0){
				if($hours < 10){$hours = "0" . $hours;}
				if($minutes < 10){$minutes = "0" . $minutes;}
				return ((string)$hours) . ":" . ((string)$minutes) . ":00";
			}
		}
		return false;
	}
	
	public static function validObservationMethod($dbconn, $observationMethod){
		if($observationMethod != "Visual" && $observationMethod != "Beat sheet"){
			return false;
		}
		return $observationMethod;
	}
	
	public static function validNotes($dbconn, $notes){
		$notes = mysqli_real_escape_string($dbconn, rawurldecode($notes));
		return $notes;
	}
	
	public static function validPlantSpecies($dbconn, $plantSpecies, $plant){
		if(self::validPlant($dbconn, $plant) !== false){
			$officialPlantSpecies = $plant->getSpecies();
			if($officialPlantSpecies != "N/A"){
				return $officialPlantSpecies;
			}
		}
		
		$plantSpecies = rawurldecode($plantSpecies);
		if(preg_replace('/\s+/', '', $plantSpecies) == ""){
			return "N/A";
		}
		
		$plantSpecies = trim($plantSpecies);
		$plantSpeciesList = array(array("Swamp cottonwood", "Populus heterophylla"), array("Plains cottonwood", "Populus deltoides"), array("Quaking aspen", "Populus tremuloides"), array("Black cottonwood", "Populus balsamifera"), array("Fremont cottonwood", "Populus fremontii"), array("Narrowleaf cottonwood", "Populus angustifolia"), array("Silver poplar", "Populus alba"), array("Lombardy poplar", "Populus nigra"), array("Mesquite spp.", "Prosopis spp."), array("Honey mesquite ", "Prosopis glandulosa"), array("Velvet mesquite", "Prosopis velutina"), array("Screwbean mesquite", "Prosopis pubescens"), array("Cherry and plum spp.", "Prunus spp."), array("Pin cherry", "Prunus pensylvanica"), array("Black cherry", "Prunus serotina"), array("Chokecherry", "Prunus virginiana"), array("Peach", "Prunus persica"), array("Canada plum", "Prunus nigra"), array("American plum", "Prunus americana"), array("Bitter cherry", "Prunus emarginata"), array("Allegheny plum", "Prunus alleghaniensis"), array("Chickasaw plum", "Prunus angustifolia"), array("Sweet cherry", "Prunus avium"), array("Sour cherry", "Prunus cerasus"), array("European plum", "Prunus domestica"), array("Mahaleb cherry", "Prunus mahaleb"), array("Oak spp.", "Quercus spp."), array("California live oak", "Quercus agrifolia"), array("White oak", "Quercus alba"), array("Arizona white oak", "Quercus arizonica"), array("Swamp white oak", "Quercus bicolor"), array("Canyon live oak", "Quercus chrysolepis"), array("Scarlet oak", "Quercus coccinea"), array("Blue oak", "Quercus douglasii"), array("Durand oak", "Quercus sinuata"), array("Northern pin oak", "Quercus ellipsoidalis"), array("Emory oak", "Quercus emoryi"), array("Engelmann oak", "Quercus engelmannii"), array("Southern red oak", "Quercus falcata"), array("Cherrybark oak", "Quercus pagoda"), array("Gambel oak", "Quercus gambelii"), array("Oregon white oak", "Quercus garryana"), array("Scrub oak", "Quercus ilicifolia"), array("Shingle oak", "Quercus imbricaria"), array("California black oak", "Quercus kelloggii"), array("Turkey oak", "Quercus laevis"), array("Laurel oak", "Quercus laurifolia"), array("California white oak", "Quercus lobata"), array("Overcup oak", "Quercus lyrata"), array("Bur oak", "Quercus macrocarpa"), array("Blackjack oak", "Quercus marilandica"), array("Swamp chestnut oak", "Quercus michauxii"), array("Chinkapin oak", "Quercus muehlenbergii"), array("Water oak", "Quercus nigra"), array("Texas red oak", "Quercus texana"), array("Mexican blue oak", "Quercus oblongifolia"), array("Pin oak", "Quercus palustris"), array("Willow oak", "Quercus phellos"), array("Bigtooth aspen", "Populus grandidentata"), array("Chestnut oak", "Quercus prinus"), array("Northern red oak", "Quercus rubra"), array("Shumard oak", "Quercus shumardii"), array("Post oak", "Quercus stellata"), array("Delta post oak", "Quercus similis"), array("Black oak", "Quercus velutina"), array("Live oak", "Quercus virginiana"), array("Interior live oak", "Quercus wislizeni"), array("Dwarf post oak", "Quercus margarettiae"), array("Dwarf live oak", "Quercus minima"), array("Bluejack oak", "Quercus incana"), array("Silverleaf oak", "Quercus hypoleucoides"), array("Oglethorpe oak", "Quercus oglethorpensis"), array("Dwarf chinkapin oak", "Quercus prinoides"), array("Gray oak", "Quercus grisea"), array("Netleaf oak", "Quercus rugosa"), array("Chisos oak", "Quercus graciliformis"), array("Sea torchwood", "Amyris elemifera"), array("Pond-apple ", "Annona glabra"), array("Gumbo limbo ", "Bursera simaruba"), array("Sheoak spp.", "Casuarina spp."), array("Gray sheoak", "Casuarina glauca"), array("Belah", "Casuarina lepidophloia"), array("Camphortree", "Cinnamomum camphora"), array("Florida fiddlewood", "Citharexylum fruticosum"), array("Citrus spp.", "Citrus spp."), array("Tietongue", "Coccoloba diversifolia"), array("Soldierwood", "Colubrina elliptica"), array("Largeleaf geigertree", "Cordia sebestena"), array("Carrotwood", "Cupaniopsis anacardioides"), array("Bluewood", "Condalia hookeri"), array("Blackbead ebony", "Ebenopsis ebano"), array("Great leucaene", "Leucaena pulverulenta"), array("Texas sophora", "Sophora affinis"), array("Red stopper", "Eugenia rhombea"), array("Butterbough", "Exothea paniculata"), array("Florida strangler fig", "Ficus aurea"), array("Wild banyantree", "Ficus citrifolia"), array("Beeftree", "Guapira discolor"), array("Manchineel", "Hippomane mancinella"), array("False tamarind", "Lysiloma latisiliquum"), array("Mango", "Mangifera indica"), array("Florida poisontree", "Metopium toxiferum"), array("Fishpoison tree", "Piscidia piscipula"), array("Octopus tree", "Schefflera actinophylla"), array("False mastic", "Sideroxylon foetidissimum"), array("White bully", "Sideroxylon salicifolium"), array("Paradisetree", "Simarouba glauca"), array("Java plum", "Syzygium cumini"), array("Tamarind", "Tamarindus indica"), array("Black locust", "Robinia pseudoacacia"), array("New mexico locust", "Robinia neomexicana"), array("Everglades palm", "Acoelorraphe wrightii"), array("Florida silver palm", "Coccothrinax argentata"), array("Coconut palm ", "Cocos nucifera"), array("Royal palm spp.", "Roystonea spp."), array("Mexican palmetto", "Sabal mexicana"), array("Cabbage palmetto", "Sabal palmetto"), array("Key thatch palm", "Thrinax morrisii"), array("Florida thatch palm", "Thrinax radiata"), array("Other palms", "Family arecaceae not listed above"), array("Western soapberry", "Sapindus saponaria"), array("Willow spp.", "Salix spp."), array("Peachleaf willow", "Salix amygdaloides"), array("Black willow", "Salix nigra"), array("Bebb willow", "Salix bebbiana"), array("Bonpland willow", "Salix bonplandiana"), array("Coastal plain willow", "Salix caroliniana"), array("Balsam willow", "Salix pyrifolia"), array("White willow", "Salix alba"), array("Scouler's willow", "Salix scouleriana"), array("Weeping willow", "Salix sepulcralis"), array("Sassafras", "Sassafras albidum"), array("Mountain-ash spp.", "Sorbus spp."), array("American mountain-ash", "Sorbus americana"), array("European mountain-ash", "Sorbus aucuparia"), array("Northern mountain-ash", "Sorbus decora"), array("West indian mahogany", "Swietenia mahagoni"), array("Basswood spp.", "Tilia spp."), array("American basswood", "Tilia americana"), array("White basswood", "Tilia americana"), array("Carolina basswood", "Tilia americana"), array("Elm spp.", "Ulmus spp."), array("Winged elm", "Ulmus alata"), array("American elm", "Ulmus americana"), array("Cedar elm", "Ulmus crassifolia"), array("Siberian elm", "Ulmus pumila"), array("Slippery elm", "Ulmus rubra"), array("September elm", "Ulmus serotina"), array("Rock elm", "Ulmus thomasii"), array("California-laurel", "Umbellularia californica"), array("Joshua tree", "Yucca brevifolia"), array("Black-mangrove", "Avicennia germinans"), array("Buttonwood-mangrove", "Conocarpus erectus"), array("White-mangrove", "Laguncularia racemosa"), array("American mangrove", "Rhizophora mangle"), array("Desert ironwood", "Olneya tesota"), array("Saltcedar", "Tamarix spp."), array("Melaleuca", "Melaleuca quinquenervia"), array("Chinaberry", "Melia azedarach"), array("Chinese tallowtree", "Triadica sebifera"), array("Tungoil tree", "Vernicia fordii"), array("Smoketree", "Cotinus obovatus"), array("Russian-olive", "Elaeagnus angustifolia"), array("Washington hawthorn", "Crataegus phaenopyrum"), array("Fleshy hawthorn", "Crataegus succulenta"), array("Dwarf hawthorn", "Crataegus uniflora"), array("Berlandier ash", "Fraxinus berlandieriana"), array("Avocado", "Persea americana"), array("Graves oak", "Quercus gravesii"), array("Mexican white oak", "Quercus polymorpha"), array("Buckley oak", "Quercus buckleyi"), array("Lacey oak", "Quercus laceyi"), array("Anacahuita", "Cordia boissieri"), array("Fir spp.", "Abies spp."), array("Pacific silver fir", "Abies amabilis"), array("Balsam fir", "Abies balsamea"), array("Santa lucia or bristlecone fir", "Abies bracteata"), array("White fir", "Abies concolor"), array("Fraser fir", "Abies fraseri"), array("Grand fir", "Abies grandis"), array("Corkbark fir", "Abies lasiocarpa"), array("Subalpine fir", "Abies lasiocarpa"), array("California red fir", "Abies magnifica"), array("Shasta red fir", "Abies shastensis"), array("Noble fir", "Abies procera"), array("White-cedar spp.", "Chamaecyparis spp."), array("Port-orford-cedar", "Chamaecyparis lawsoniana"), array("Alaska yellow-cedar", "Chamaecyparis nootkatensis"), array("Atlantic white-cedar", "Chamaecyparis thyoides"), array("Cypress", "Cupressus spp."), array("Arizona cypress", "Cupressus arizonica"), array("Modoc cypress", "Cupressus bakeri"), array("Tecate cypress", "Cupressus forbesii"), array("Monterey cypress", "Cupressus macrocarpa"), array("Sargent's cypress", "Cupressus sargentii"), array("Macnab's cypress", "Cupressus macnabiana"), array("Redcedar/juniper spp.", "Juniperus spp."), array("Pinchot juniper", "Juniperus pinchotii"), array("Redberry juniper", "Juniperus coahuilensis"), array("Drooping juniper", "Juniperus flaccida"), array("Ashe juniper", "Juniperus ashei"), array("California juniper", "Juniperus californica"), array("Alligator juniper", "Juniperus deppeana"), array("Western juniper", "Juniperus occidentalis"), array("Utah juniper", "Juniperus osteosperma"), array("Rocky mountain juniper", "Juniperus scopulorum"), array("Southern redcedar", "Juniperus virginiana"), array("Eastern redcedar", "Juniperus virginiana"), array("Oneseed juniper", "Juniperus monosperma"), array("Larch spp.", "Larix spp."), array("Tamarack (native)", "Larix laricina"), array("Subalpine larch", "Larix lyallii"), array("Western larch", "Larix occidentalis"), array("Incense-cedar", "Calocedrus decurrens"), array("Spruce spp.", "Picea spp."), array("Norway spruce", "Picea abies"), array("Brewer spruce", "Picea breweriana"), array("Engelmann spruce", "Picea engelmannii"), array("White spruce", "Picea glauca"), array("Black spruce", "Picea mariana"), array("Blue spruce", "Picea pungens"), array("Red spruce", "Picea rubens"), array("Sitka spruce", "Picea sitchensis"), array("Pine spp.", "Pinus spp."), array("Whitebark pine", "Pinus albicaulis"), array("Bristlecone pine", "Pinus aristata"), array("Knobcone pine", "Pinus attenuata"), array("Foxtail pine", "Pinus balfouriana"), array("Jack pine", "Pinus banksiana"), array("Common pinyon", "Pinus edulis"), array("Sand pine", "Pinus clausa"), array("Lodgepole pine", "Pinus contorta"), array("Coulter pine", "Pinus coulteri"), array("Shortleaf pine", "Pinus echinata"), array("Slash pine", "Pinus elliottii"), array("Apache pine", "Pinus engelmannii"), array("Limber pine", "Pinus flexilis"), array("Southwestern white pine ", "Pinus strobiformis"), array("Spruce pine", "Pinus glabra"), array("Jeffrey pine", "Pinus jeffreyi"), array("Sugar pine", "Pinus lambertiana"), array("Chihuahua pine", "Pinus leiophylla"), array("Western white pine", "Pinus monticola"), array("Bishop pine", "Pinus muricata"), array("Longleaf pine", "Pinus palustris"), array("Ponderosa pine", "Pinus ponderosa"), array("Table mountain pine", "Pinus pungens"), array("Monterey pine", "Pinus radiata"), array("Red pine", "Pinus resinosa"), array("Pitch pine", "Pinus rigida"), array("Gray or california foothill pine", "Pinus sabiniana"), array("Pond pine", "Pinus serotina"), array("Eastern white pine", "Pinus strobus"), array("Scotch pine", "Pinus sylvestris"), array("Loblolly pine", "Pinus taeda"), array("Virginia pine", "Pinus virginiana"), array("Singleleaf pinyon", "Pinus monophylla"), array("Border pinyon", "Pinus discolor"), array("Arizona pine", "Pinus arizonica"), array("Austrian pine", "Pinus nigra"), array("Washoe pine", "Pinus washoensis"), array("Four-leaf or parry pinyon pine", "Pinus quadrifolia"), array("Torrey pine", "Pinus torreyana"), array("Mexican pinyon pine", "Pinus cembroides"), array("Papershell pinyon pine", "Pinus remota"), array("Great basin bristlecone pine", "Pinus longaeva"), array("Arizona pinyon pine", "Pinus monophylla"), array("Honduras pine", "Pinus elliottii"), array("Douglas-fir spp.", "Pseudotsuga spp."), array("Bigcone douglas-fir", "Pseudotsuga macrocarpa"), array("Douglas-fir", "Pseudotsuga menziesii"), array("Redwood", "Sequoia sempervirens"), array("Giant sequoia", "Sequoiadendron giganteum"), array("Baldcypress spp.", "Taxodium spp."), array("Baldcypress", "Taxodium distichum"), array("Pondcypress", "Taxodium ascendens"), array("Montezuma baldcypress", "Taxodium mucronatum"), array("Yew spp.", "Taxus spp."), array("Pacific yew", "Taxus brevifolia"), array("Florida yew", "Taxus floridana"), array("Thuja spp.", "Thuja spp."), array("Northern white-cedar", "Thuja occidentalis"), array("Western redcedar", "Thuja plicata"), array("Torreya spp.", "Torreya spp."), array("California torreya (nutmeg)", "Torreya californica"), array("Florida torreya (nutmeg)", "Torreya taxifolia"), array("Hemlock spp.", "Tsuga spp."), array("Eastern hemlock", "Tsuga canadensis"), array("Carolina hemlock", "Tsuga caroliniana"), array("Western hemlock", "Tsuga heterophylla"), array("Mountain hemlock", "Tsuga mertensiana"), array("Acacia spp.", "Acacia spp."), array("Sweet acacia", "Acacia farnesiana"), array("Catclaw acacia", "Acacia greggii"), array("Maple spp.", "Acer spp."), array("Florida maple", "Acer barbatum"), array("Bigleaf maple", "Acer macrophyllum"), array("Boxelder", "Acer negundo"), array("Black maple", "Acer nigrum"), array("Striped maple", "Acer pensylvanicum"), array("Red maple", "Acer rubrum"), array("Silver maple", "Acer saccharinum"), array("Sugar maple", "Acer saccharum"), array("Mountain maple", "Acer spicatum"), array("Norway maple", "Acer platanoides"), array("Rocky mountain maple", "Acer glabrum"), array("Bigtooth maple", "Acer grandidentatum"), array("Chalk maple", "Acer leucoderme"), array("Buckeye spp.", "Aesculus spp."), array("Ohio buckeye", "Aesculus glabra"), array("Yellow buckeye", "Aesculus flava"), array("California buckeye", "Aesculus californica"), array("Texas buckeye", "Aesculus glabra"), array("Red buckeye", "Aesculus pavia"), array("Painted buckeye", "Aesculus sylvatica"), array("Ailanthus", "Ailanthus altissima"), array("Mimosa", "Albizia julibrissin"), array("Alder spp.", "Alnus spp."), array("Red alder", "Alnus rubra"), array("White alder", "Alnus rhombifolia"), array("Arizona alder", "Alnus oblongifolia"), array("European alder", "Alnus glutinosa"), array("Serviceberry spp.", "Amelanchier spp."), array("Common serviceberry", "Amelanchier arborea"), array("Roundleaf serviceberry", "Amelanchier sanguinea"), array("Madrone spp.", "Arbutus spp."), array("Pacific madrone", "Arbutus menziesii"), array("Arizona madrone", "Arbutus arizonica"), array("Texas madrone", "Arbutus xalapensis"), array("Pawpaw", "Asimina triloba"), array("Birch spp.", "Betula spp."), array("Yellow birch", "Betula alleghaniensis"), array("Sweet birch", "Betula lenta"), array("River birch", "Betula nigra"), array("Water birch", "Betula occidentalis"), array("Paper birch", "Betula papyrifera"), array("Virginia roundleaf birch", "Betula uber"), array("Northwestern paper birch", "Betula x utahensis"), array("Gray birch", "Betula populifolia"), array("Chittamwood", "Sideroxylon lanuginosum"), array("American hornbeam", "Carpinus caroliniana"), array("Hickory spp.", "Carya spp."), array("Water hickory", "Carya aquatica"), array("Bitternut hickory", "Carya cordiformis"), array("Pignut hickory", "Carya glabra"), array("Pecan", "Carya illinoinensis"), array("Shellbark hickory", "Carya laciniosa"), array("Nutmeg hickory", "Carya myristiciformis"), array("Shagbark hickory", "Carya ovata"), array("Black hickory", "Carya texana"), array("Mockernut hickory", "Carya alba"), array("Sand hickory", "Carya pallida"), array("Scrub hickory", "Carya floridana"), array("Red hickory", "Carya ovalis"), array("Southern shagbark hickory", "Carya carolinae-septentrionalis"), array("Chestnut spp.", "Castanea spp."), array("American chestnut", "Castanea dentata"), array("Allegheny chinkapin", "Castanea pumila"), array("Ozark chinkapin", "Castanea pumila"), array("Chinese chestnut", "Castanea mollissima"), array("Giant chinkapin", "Chrysolepis chrysophylla"), array("Catalpa spp.", "Catalpa spp."), array("Southern catalpa", "Catalpa bignonioides"), array("Northern catalpa", "Catalpa speciosa"), array("Hackberry spp.", "Celtis spp."), array("Sugarberry", "Celtis laevigata"), array("Hackberry", "Celtis occidentalis"), array("Netleaf hackberry", "Celtis laevigata"), array("Eastern redbud", "Cercis canadensis"), array("Curlleaf mountain-mahogany", "Cercocarpus ledifolius"), array("Yellowwood", "Cladrastis kentukea"), array("Dogwood spp.", "Cornus spp."), array("Flowering dogwood", "Cornus florida"), array("Pacific dogwood", "Cornus nuttallii"), array("Hawthorn spp.", "Crataegus spp."), array("Cockspur hawthorn", "Crataegus crus-galli"), array("Downy hawthorn", "Crataegus mollis"), array("Brainerd's hawthorn", "Crataegus brainerdii"), array("Pear hawthorn", "Crataegus calpodendron"), array("Fireberry hawthorn", "Crataegus chrysocarpa"), array("Broadleaf hawthorn", "Crataegus dilatata"), array("Fanleaf hawthorn", "Crataegus flabellata"), array("Oneseed hawthorn", "Crataegus monogyna"), array("Scarlet hawthorn", "Crataegus pedicellata"), array("Eucalyptus spp.", "Eucalyptus spp."), array("Tasmanian bluegum", "Eucalyptus globulus"), array("River redgum", "Eucalyptus camaldulensis"), array("Grand eucalyptus", "Eucalyptus grandis"), array("Swampmahogany", "Eucalyptus robusta"), array("Persimmon spp.", "Diospyros spp."), array("Common persimmon", "Diospyros virginiana"), array("Texas persimmon", "Diospyros texana"), array("Anacua knockaway", "Ehretia anacua"), array("American beech", "Fagus grandifolia"), array("Ash spp.", "Fraxinus spp."), array("White ash", "Fraxinus americana"), array("Oregon ash", "Fraxinus latifolia"), array("Black ash", "Fraxinus nigra"), array("Green ash", "Fraxinus pennsylvanica"), array("Pumpkin ash", "Fraxinus profunda"), array("Blue ash", "Fraxinus quadrangulata"), array("Velvet ash", "Fraxinus velutina"), array("Carolina ash", "Fraxinus caroliniana"), array("Texas ash", "Fraxinus texensis"), array("Honeylocust spp.", "Gleditsia spp."), array("Waterlocust", "Gleditsia aquatica"), array("Honeylocust", "Gleditsia triacanthos"), array("Loblolly-bay", "Gordonia lasianthus"), array("Ginkgo", "Ginkgo biloba"), array("Kentucky coffeetree", "Gymnocladus dioicus"), array("Silverbell spp.", "Halesia spp."), array("Carolina silverbell", "Halesia carolina"), array("Two-wing silverbell", "Halesia diptera"), array("Little silverbell", "Halesia parviflora"), array("American holly", "Ilex opaca"), array("Walnut spp.", "Juglans spp."), array("Butternut", "Juglans cinerea"), array("Black walnut", "Juglans nigra"), array("Northern california black walnut", "Juglans hindsii"), array("Southern california black walnut", "Juglans californica"), array("Texas walnut", "Juglans microcarpa"), array("Arizona walnut", "Juglans major"), array("Sweetgum", "Liquidambar styraciflua"), array("Yellow-poplar", "Liriodendron tulipifera"), array("Tanoak", "Lithocarpus densiflorus"), array("Osage-orange", "Maclura pomifera"), array("Magnolia spp.", "Magnolia spp."), array("Cucumbertree", "Magnolia acuminata"), array("Southern magnolia", "Magnolia grandiflora"), array("Sweetbay", "Magnolia virginiana"), array("Bigleaf magnolia", "Magnolia macrophylla"), array("Mountain or fraser magnolia", "Magnolia fraseri"), array("Pyramid magnolia", "Magnolia pyramidata"), array("Umbrella magnolia", "Magnolia tripetala"), array("Apple spp.", "Malus spp."), array("Oregon crab apple", "Malus fusca"), array("Southern crab apple", "Malus angustifolia"), array("Sweet crab apple", "Malus coronaria"), array("Prairie crab apple", "Malus ioensis"), array("Mulberry spp.", "Morus spp."), array("White mulberry", "Morus alba"), array("Red mulberry", "Morus rubra"), array("Texas mulberry", "Morus microphylla"), array("Black mulberry", "Morus nigra"), array("Tupelo spp.", "Nyssa spp."), array("Water tupelo", "Nyssa aquatica"), array("Ogeechee tupelo", "Nyssa ogeche"), array("Blackgum", "Nyssa sylvatica"), array("Swamp tupelo", "Nyssa biflora"), array("Eastern hophornbeam", "Ostrya virginiana"), array("Sourwood", "Oxydendrum arboreum"), array("Paulownia empress-tree", "Paulownia tomentosa"), array("Bay spp.", "Persea spp."), array("Redbay", "Persea borbonia"), array("Water-elm planertree", "Planera aquatica"), array("Sycamore spp.", "Platanus spp."), array("California sycamore", "Platanus racemosa"), array("American sycamore", "Platanus occidentalis"), array("Arizona sycamore", "Platanus wrightii"), array("Cottonwood and poplar spp.", "Populus spp."), array("Balsam poplar", "Populus balsamifera"), array("Eastern cottonwood", "Populus deltoides"));
		for($i = 0; $i < count($plantSpeciesList); $i++){
			$plantSpeciesList[$i][0] = trim(preg_replace('!\s+!', ' ', $plantSpeciesList[$i][0]));
			$plantSpeciesList[$i][1] = trim(preg_replace('!\s+!', ' ', $plantSpeciesList[$i][1]));
			if(strtolower($plantSpecies) == strtolower($plantSpeciesList[$i][1]) || strtolower($plantSpecies) == strtolower($plantSpeciesList[$i][0])){
				return $plantSpeciesList[$i][0];
			}
		}
		return ucfirst(strtolower(trim(preg_replace('!\s+!', ' ', $plantSpecies))));
	}
	
	public static function validNumberOfLeaves($dbconn, $numberOfLeaves){
		$numberOfLeaves = intval($numberOfLeaves);
		if($numberOfLeaves >= 1 && $numberOfLeaves <= 500){
			return $numberOfLeaves;
		}
		return false;
	}
	
	public static function validAverageLeafLength($dbconn, $averageLeafLength){
		$averageLeafLength = intval($averageLeafLength);
		if($averageLeafLength >= 1 && $averageLeafLength <= 60){
			return $averageLeafLength;
		}
		return false;
	}
	
	public static function validHerbivoryScore($dbconn, $herbivoryScore){
		$herbivoryScore = intval(preg_replace("/[^0-9]/", "", $herbivoryScore));
		if($herbivoryScore >= 0 && $herbivoryScore <= 4){
			return $herbivoryScore;
		}
		return false;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//FUNCTIONS
	public function addArthropodSighting($group, $length, $quantity, $notes, $hairy, $rolled, $tented, $sawfly, $beetleLarva){
		return ArthropodSighting::create($this, $group, $length, $quantity, $notes, $hairy, $rolled, $tented, $sawfly, $beetleLarva);
	}
}		
?>
