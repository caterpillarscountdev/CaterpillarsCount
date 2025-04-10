<?php

require_once('resources/Keychain.php');
require_once('User.php');
require_once('Plant.php');
require_once('ArthropodSighting.php');
require('SurveyFlaggingRules.php');
require('PlantSpecies.php');

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
	private $averageNeedleLength;
	private $linearBranchLength;
	private $submittedThroughApp;
	private $reviewedAndApproved;
	private $qcComment;

        private $_flags;
        private $_arthropodSightings;
	
	private $deleted;

//FACTORY
	public static function create($observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp) {
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
		$isConifer = (intval($averageNeedleLength) !== -1);
		$numberOfLeaves = $isConifer ? -1 : self::validNumberOfLeaves($dbconn, $numberOfLeaves);
		$averageLeafLength = $isConifer ? -1 : self::validAverageLeafLength($dbconn, $averageLeafLength);
		$herbivoryScore = $isConifer ? -1 : self::validHerbivoryScore($dbconn, $herbivoryScore);
		$averageNeedleLength = $isConifer ? self::validAverageNeedleLength($dbconn, $averageNeedleLength) : -1;
		$linearBranchLength = $isConifer ? self::validLinearBranchLength($dbconn, $linearBranchLength) : -1;
		$submittedThroughApp = filter_var($submittedThroughApp, FILTER_VALIDATE_BOOLEAN);
		$reviewedAndApproved = false;
		$qcComment = null;
		
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
		if(!$isConifer && $numberOfLeaves === false){
			$failures .= "Number of leaves must be between 1 and 500. ";
		}
		if(!$isConifer && $averageLeafLength === false){
			$failures .= "Average leaf length must be between 1cm and 60cm. ";
		}
		if(!$isConifer && $herbivoryScore === false){
			$failures .= "Select an herbivory score. ";
		}
		if($isConifer && $averageNeedleLength === false){
			$failures .= "Average needle length must be between 1cm and 60cm. ";
		}
		if($isConifer && $linearBranchLength === false){
			$failures .= "Linear branch length must be between 1cm and 500cm. ";
		}
		
		if($failures != ""){
			return $failures;
		}
		
		$needToSendToSciStarter = 1;
		if($plant->getSite()->getName() == "Example Site"){
			$needToSendToSciStarter = 0;
		}
		mysqli_query($dbconn, "INSERT INTO Survey (`SubmissionTimestamp`, `UserFKOfObserver`, `PlantFK`, `LocalDate`, `LocalTime`, `ObservationMethod`, `Notes`, `WetLeaves`, `PlantSpecies`, `NumberOfLeaves`, `AverageLeafLength`, `HerbivoryScore`, `AverageNeedleLength`, `LinearBranchLength`, `SubmittedThroughApp`, `NeedToSendToSciStarter`, `ReviewedAndApproved`) VALUES ('" . $submissionTimestamp . "', '" . $observer->getID() . "', '" . $plant->getID() . "', '$localDate', '$localTime', '$observationMethod', '$notes', '$wetLeaves', '$plantSpecies', '$numberOfLeaves', '$averageLeafLength', '$herbivoryScore', '$averageNeedleLength', '$linearBranchLength', '$submittedThroughApp', '$needToSendToSciStarter', '$reviewedAndApproved')");
		$id = intval(mysqli_insert_id($dbconn));
		
		if($plant->getSite()->getDateEstablished() == "0000-00-00"){
			$plant->getSite()->setDateEstablished($localDate);
		}
		
		return new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved, $qcComment);
	}
	private function __construct($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved, $qcComment) {
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
		$this->averageNeedleLength = intval($averageNeedleLength);
		$this->linearBranchLength = intval($linearBranchLength);
		$this->submittedThroughApp = $submittedThroughApp;
		$this->arthropodSightings = null;
		$this->reviewedAndApproved = filter_var($reviewedAndApproved, FILTER_VALIDATE_BOOLEAN);
                $this->qcComment = $qcComment;
                  
		$this->deleted = false;
	}

//FINDERS
	public static function findByID($id) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = intval($id);
		$query = mysqli_query($dbconn, "SELECT * FROM `Survey` WHERE `ID`='$id' LIMIT 1");
		
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
		$averageNeedleLength = $surveyRow["AverageNeedleLength"];
		$linearBranchLength = $surveyRow["LinearBranchLength"];
		$submittedThroughApp = $surveyRow["SubmittedThroughApp"];
		$reviewedAndApproved = $surveyRow["ReviewedAndApproved"];
                $qcComment = $surveyRow["QCComment"];
		
		return new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved, $qcComment);
	}
	
	public static function findSurveysByIDs($surveyIDs, $orderBy="", $start=null, $limit=null) {
		if(count($surveyIDs) == 0){
			return array();
		}
		
		for($i = 0; $i < count($surveyIDs); $i++){
			$surveyIDs[$i] = intval($surveyIDs[$i]);
		}
		
		$acceptableOrderBys = array(
			"LocalDate DESC, LocalTime DESC"
		);
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$limitSQL = " LIMIT " . count($surveyIDs);
		if($limit !== null){
			if($start === null){
				$limitSQL = " LIMIT " . min($limit, count($surveyIDs));
			}
			else{
				$limitSQL = " LIMIT " . $start . ", " . min($limit, count($surveyIDs));
			}
		}
		
		$query = mysqli_query($dbconn, "SELECT * FROM `Survey` WHERE `ID` IN ('" . implode("', '", $surveyIDs) . "') " . (in_array($orderBy, $acceptableOrderBys) ? "ORDER BY " . $orderBy . " " : "") . $limitSQL);
		
		//get associated plants
		$associatedPlantFKs = array();
		while($surveyRow = mysqli_fetch_assoc($query)){
			$associatedPlantFKs[$surveyRow["PlantFK"]] = 1;
		}
		$associatedPlantFKs = array_keys($associatedPlantFKs);
		
		$associatedPlantsByPlantFK = array();
		$associatedPlants = Plant::findPlantsByIDs($associatedPlantFKs);
		for($i = 0; $i < count($associatedPlants); $i++){
			$associatedPlantsByPlantFK[$associatedPlants[$i]->getID()] = $associatedPlants[$i];
		}
		
		//get associated users
		$associatedUserFKs = array();
		mysqli_data_seek($query, 0);
		while($surveyRow = mysqli_fetch_assoc($query)){
			$associatedUserFKs[$surveyRow["UserFKOfObserver"]] = 1;
		}
		$associatedUserFKs = array_keys($associatedUserFKs);
		
		$associatedUsersByUserFK = array();
		$associatedUsers = User::findUsersByIDs($associatedUserFKs);
		for($i = 0; $i < count($associatedUsers); $i++){
			$associatedUsersByUserFK[$associatedUsers[$i]->getID()] = $associatedUsers[$i];
		}
		
		//make survey objects
		$surveysArray = array();
		mysqli_data_seek($query, 0);
		while($surveyRow = mysqli_fetch_assoc($query)){
			$id = intval($surveyRow["ID"]);
			$submissionTimestamp = intval($surveyRow["SubmissionTimestamp"]);
			$observer = array_key_exists($surveyRow["UserFKOfObserver"], $associatedUsersByUserFK) ? $associatedUsersByUserFK[$surveyRow["UserFKOfObserver"]] : null;
			$plant = array_key_exists($surveyRow["PlantFK"], $associatedPlantsByPlantFK) ? $associatedPlantsByPlantFK[$surveyRow["PlantFK"]] : null;
			$localDate = $surveyRow["LocalDate"];
			$localTime = $surveyRow["LocalTime"];
			$observationMethod = $surveyRow["ObservationMethod"];
			$notes = $surveyRow["Notes"];
			$wetLeaves = $surveyRow["WetLeaves"];
			$plantSpecies = $surveyRow["PlantSpecies"];
			$numberOfLeaves = $surveyRow["NumberOfLeaves"];
			$averageLeafLength = $surveyRow["AverageLeafLength"];
			$herbivoryScore = $surveyRow["HerbivoryScore"];
			$averageNeedleLength = $surveyRow["AverageNeedleLength"];
			$linearBranchLength = $surveyRow["LinearBranchLength"];
			$submittedThroughApp = $surveyRow["SubmittedThroughApp"];
			$reviewedAndApproved = $surveyRow["ReviewedAndApproved"];
                        $qcComment = $surveyRow["QCComment"];

			$surveysArray[] = new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved, $qcComment);
		}
		return $surveysArray;
	}
	
	public static function findSurveysByUser($user, $filters, $start, $limit) {
		//returns all surveys user has completed
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$start = mysqli_real_escape_string($dbconn, htmlentities(($start . "")));
		$limit = mysqli_real_escape_string($dbconn, htmlentities(($limit . "")));
		$filterKeys = array_keys($filters);
		foreach($filterKeys as $filterKey) {
			$filters[$filterKey] = mysqli_real_escape_string($dbconn, htmlentities(($filters[$filterKey] . "")));
		}

                $superUser = User::isSuperUser($user->getEmail());
                
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

                $flagSearch = "";
                if (isset($filters["flagged"])) {
                  $flagSearch = trim($filters["flagged"]);
                }
                
		$arthropodSearch = trim($filters["arthropod"]);
		$minArthropodLength = intval($filters["minArthropodLength"]);
                error_log("arth" . $arthropodSearch . "+" . $minArthropodLength . "=" . $flagSearch);
		if(strlen($arthropodSearch) > 0 || $minArthropodLength > 0){
			$baseTable = "`ArthropodSighting` JOIN `Survey` ON ArthropodSighting.SurveyFK = Survey.ID";
			$groupBy = " GROUP BY ArthropodSighting.SurveyFK";
		}
		if($flagSearch == 'flagged'){
			$baseTable = "`ArthropodSighting` RIGHT JOIN `Survey` ON ArthropodSighting.SurveyFK = Survey.ID";
			$groupBy = " GROUP BY Survey.ID";
		}
		
		if($minArthropodLength > 0){
			$additionalSQL .= " AND ArthropodSighting.Length>='" . $minArthropodLength . "'";
		}
		
		if(strlen($arthropodSearch) > 0){
			$additionalSQL .= " AND (ArthropodSighting.OriginalGroup='" . $arthropodSearch . "' OR ArthropodSighting.UpdatedGroup='" . $arthropodSearch . "')";
		}
		
		$userSearch = trim($filters["user"]);
		if(strlen($userSearch) > 0){
			$additionalSQL .= " AND CONCAT(User.FirstName, ' ', User.LastName) LIKE '%" . $userSearch . "%'";
		}

                $flaggingRules = SurveyFlaggingRules();

		if(strlen($flagSearch) > 0){
                  if ($flagSearch == 'accepted' || $flagSearch == 'rejected') {
                    $field = 'ReviewedAndApprovedSite';
                    if ($superUser) {
                      $field = 'ReviewedAndApproved';
                    }
                    $additionalSQL .= " AND " . $field . ($flagSearch == 'accepted' ? " = 1" : " > 1");
                  } else if ($flagSearch == 'flagged') {
                    $llExclude = array();
                    $llRule = array();
                    foreach($flaggingRules["leafLengthExceptions"] as $llName => $llValue) {
                      $llExclude[] = '"'.$llName.'"';
                      $llRule[] = "(PlantSpecies = '" . $llName. "' AND AverageLeafLength > '". $llValue ."')";
                    }
                    $llExclude = join(",", $llExclude);
                    $llRule = join(" OR ", $llRule);
                  
                    $sql = "`AverageNeedleLength`='-1' AND (`NumberOfLeaves`<'" . intval($flaggingRules["minSafeLeaves"]) . "' OR `NumberOfLeaves`>'" . intval($flaggingRules["maxSafeLeaves"]) . "' OR (`AverageLeafLength`>'" . intval($flaggingRules["maxSafeLeafLength"]) . "' AND PlantSpecies NOT IN (".$llExclude.") ) OR (" . $llRule . "))";
                    //arthropod flags
                    $sql .= " OR (`UpdatedSawfly`='1' AND (`Length`>'" . intval($flaggingRules["arthropodGroupFlaggingRules"]["sawfly"]["maxSafeLength"]) . "' OR Quantity>'" . intval($flaggingRules["arthropodGroupFlaggingRules"]["sawfly"]["maxSafeQuantity"]) . "'))";
                    foreach($flaggingRules["arthropodGroupFlaggingRules"] as $arthropodGroup => $flaggingData){
                      if ($arthropodGroup == "sawfly") { continue; }
                      $sql .= " OR (`UpdatedSawfly`='0' AND `UpdatedGroup`='" . mysqli_real_escape_string($dbconn, $arthropodGroup) . "' AND (`Length`>'" . intval($flaggingData["maxSafeLength"]) . "' OR `Quantity`>'" . intval($flaggingData["maxSafeQuantity"]) . "'))";
                    }

                    $additionalSQL .=  " AND ReviewedAndApproved < 1 AND (" . $sql . ")";
                  }
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
		
		$dateSearch = mysqli_real_escape_string($dbconn, trim(htmlentities(strval($filters["date"]))));
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
		$observerFKs = array();
		$plantFKs = array();
		while($surveyRow = mysqli_fetch_assoc($query)){
			$observerFKs[] = $surveyRow["UserFKOfObserver"];
			$plantFKs[] = $surveyRow["PlantFK"];
		}
		
		$users = User::findUsersByIDs($observerFKs);
		$plants = Plant::findPlantsByIDs($plantFKs);
		
		$usersByID = array();
		for($i = 0; $i < count($users); $i++){
			$usersByID[$users[$i]->getID()] = $users[$i];
		}
		
		$plantsByID = array();
		for($i = 0; $i < count($plants); $i++){
			$plantsByID[$plants[$i]->getID()] = $plants[$i];
		}
		
		mysqli_data_seek($query, 0);
		while($surveyRow = mysqli_fetch_assoc($query)){
			$id = $surveyRow["ID"];
			$submissionTimestamp = intval($surveyRow["SubmissionTimestamp"]);
			$observer = $usersByID[$surveyRow["UserFKOfObserver"]];
			$plant = $plantsByID[$surveyRow["PlantFK"]];
			$localDate = $surveyRow["LocalDate"];
			$localTime = $surveyRow["LocalTime"];
			$observationMethod = $surveyRow["ObservationMethod"];
			$notes = $surveyRow["Notes"];
			$wetLeaves = $surveyRow["WetLeaves"];
			$plantSpecies = $surveyRow["PlantSpecies"];
			$numberOfLeaves = $surveyRow["NumberOfLeaves"];
			$averageLeafLength = $surveyRow["AverageLeafLength"];
			$herbivoryScore = $surveyRow["HerbivoryScore"];
			$averageNeedleLength = $surveyRow["AverageNeedleLength"];
			$linearBranchLength = $surveyRow["LinearBranchLength"];
			$submittedThroughApp = $surveyRow["SubmittedThroughApp"];
			$reviewedAndApproved = $surveyRow["ReviewedAndApproved"];
                        $qcComment = $surveyRow["QCComment"];
			
			$survey = new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved, $qcComment);

                        $flags = $survey->getFlags();
                        
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
                if(!$this->_arthropodSightings) {
                  $this->_arthropodSightings = ArthropodSighting::findArthropodSightingsBySurvey($this);
                }
		return $this->_arthropodSightings;
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
	
	public function getAverageNeedleLength() {
		if($this->deleted){return null;}
		return intval($this->averageNeedleLength);
	}
	
	public function getLinearBranchLength() {
		if($this->deleted){return null;}
		return intval($this->linearBranchLength);
	}
	
	public function getSubmittedThroughApp(){
		if($this->deleted){return null;}
		return filter_var($this->submittedThroughApp, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function isConifer(){
		if($this->deleted){return null;}
		return intval($this->averageNeedleLength) > -1;
	}
	
	public function getReviewedAndApproved(){
		if($this->deleted){return null;}
		return filter_var($this->reviewedAndApproved, FILTER_VALIDATE_BOOLEAN);
	}

        public function getQCComment(){
		if($this->deleted){return null;}
		return $this->qcComment;
        }
		
	public function getFlags(){
          if($this->_flags) { return $this->_flags; };

          $dbconn = (new Keychain)->getDatabaseConnection();

          $id = $this->getID();
          $query = mysqli_query($dbconn, "SELECT QCNumberOfLeavesOK, QCAverageLeafLengthOK, QCArthropodLengthOK, QCArthropodQuantityOK FROM `Survey` WHERE `ID`='$id' LIMIT 1");
          if(mysqli_num_rows($query) !== 0){
            $flagOverrides = mysqli_fetch_assoc($query);
          }
		
                $flaggingRules = SurveyFlaggingRules();

		//grab flags based on info provided above...
		$flags = array();

                $sets = array();

                $hasSetArthLength = false;
                $hasSetArthQuant = false;
		$arthropodSightings = $this->getArthropodSightings();
		for($i = 0; $i < count($arthropodSightings); $i++){
			$updatedArthropodGroup = $arthropodSightings[$i]->getUpdatedGroup();
			if ($arthropodSightings[$i]->getUpdatedSawfly()) {
                          $updatedArthropodGroup = "sawfly";
                        }
			
			//flag lengths
			$arthropodLength = $arthropodSightings[$i]->getLength();
			
			if(array_key_exists($updatedArthropodGroup, $flaggingRules["arthropodGroupFlaggingRules"]) && $arthropodLength > $flaggingRules["arthropodGroupFlaggingRules"][$updatedArthropodGroup]["maxSafeLength"]){
                          $key = "QCArthropodLengthHigh";
                          $flags[] = array("text" => "LONG ARTHROPOD: " . $arthropodLength . "mm more than expected \"" . $updatedArthropodGroup . "\" limit of " . $flaggingRules["arthropodGroupFlaggingRules"][$updatedArthropodGroup]["maxSafeLength"] . "mm.", "key" => $key, "ok" => $flagOverrides["QCArthropodLengthOK"]);
                          if (!$hasSetArthLength) {
                            $sets[] = $key . " = 1";
                          }
                          $hasSetArthLength = true;
                          
                          mysqli_query($dbconn, "UPDATE ArthropodSighting SET QCArthropodLengthHigh = 1 WHERE ID = " . $arthropodSightings[$i]->getID());
                        }
			
			//flag quantities
			$arthropodQuantity = $arthropodSightings[$i]->getQuantity();
			
			if(array_key_exists($updatedArthropodGroup, $flaggingRules["arthropodGroupFlaggingRules"]) && $arthropodQuantity > $flaggingRules["arthropodGroupFlaggingRules"][$updatedArthropodGroup]["maxSafeQuantity"]){
                          $key = "QCArthropodQuantityHigh";
                          $flags[] = array("text"=> "LARGE ARTHROPOD QUANTITY: " . $arthropodQuantity . " more than expected \"" . $updatedArthropodGroup . "\" quantity limit of " . $flaggingRules["arthropodGroupFlaggingRules"][$updatedArthropodGroup]["maxSafeQuantity"] . ".", "key" => $key, "ok" => $flagOverrides["QCArthropodQuantityOK"]);

                          if (!$hasSetArthQuant) {
                            $sets[] = $key . " = 1";
                          }
                          $hasSetArthQuant = true;
                          
                          mysqli_query($dbconn, "UPDATE ArthropodSighting SET QCArthropodQuantityHigh = 1 WHERE ID = " . $arthropodSightings[$i]->getID() );
			}
		}
		
		//flag too few leaves
		$isConifer = $this->isConifer();
		$numberOfLeaves = $this->getNumberOfLeaves();
		if(!$isConifer && $numberOfLeaves < $flaggingRules["minSafeLeaves"]){
                  $key = "QCNumberOfLeavesLow";
                  $flags[] = array("text" => "TOO FEW LEAVES: " . $numberOfLeaves . " leaves less than expected " . $flaggingRules["minSafeLeaves"] . " leaves.", "key" => $key, "ok" => $flagOverrides["QCNumberOfLeavesOK"]);
                  $sets[] = $key . " = 1";
		}
		
		//flag too many leaves
		if(!$isConifer && $numberOfLeaves > $flaggingRules["maxSafeLeaves"]){
                  $key = "QCNuberOfLeavesHigh";
                  $flags[] = array("text" => "TOO MANY LEAVES: " . $numberOfLeaves . " leaves more than expected " . $flaggingRules["maxSafeLeaves"] . " leaves.", "key" => $key, "ok" => $flagOverrides["QCNumberOfLeavesOK"]);
                        $sets[] = $key . " = 1";
		}
		
		//flag long leaves
		$averageLeafLength = $this->getAverageLeafLength();
		if(!$isConifer && $averageLeafLength > $flaggingRules["maxSafeLeafLength"]){
                  $key = "QCAverageLeafLengthHigh";
                  $flags[] = array("text" => "LONG LEAVES: " . $averageLeafLength . "cm more than expected " . $flaggingRules["maxSafeLeafLength"] . "cm.", "key" => $key, "ok" => $flagOverrides["QCAverageLeafLengthOK"]);
                        $sets[] = $key ." = 1";
		}

                $sql = "UPDATE Survey SET " . join(", ", $sets) . " WHERE ID = " . $this->getID() . ";";

                $query = mysqli_query($dbconn, $sql);
                
		return $this->_flags = $flags;
	}
	
//SETTERS
	public function setPlant($plant){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$plant = self::validPlant($dbconn, $plant);
			if($plant !== false){
				mysqli_query($dbconn, "UPDATE Survey SET PlantFK='" . $plant->getID() . "' WHERE ID='" . $this->id . "'");
				$this->plant = $plant;
				return true;
			}
		}
		return false;
	}
	
	public function setLocalDate($localDate){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$localDate = self::validLocalDate($dbconn, $localDate);
			if($localDate !== false){
				mysqli_query($dbconn, "UPDATE Survey SET LocalDate='$localDate' WHERE ID='" . $this->id . "'");
				$this->localDate = $localDate;
				return true;
			}
		}
		return false;
	}
	
	public function setLocalTime($localTime){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$localTime = self::validLocalTime($dbconn, $localTime);
			if($localTime !== false){
				mysqli_query($dbconn, "UPDATE Survey SET LocalTime='$localTime' WHERE ID='" . $this->id . "'");
				$this->localTime = $localTime;
				return true;
			}
		}
		return false;
	}
	
	public function setObservationMethod($observationMethod){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$observationMethod = self::validObservationMethod($dbconn, $observationMethod);
			if($observationMethod !== false){
				mysqli_query($dbconn, "UPDATE Survey SET ObservationMethod='$observationMethod' WHERE ID='" . $this->id . "'");
				$this->observationMethod = $observationMethod;
				return true;
			}
		}
		return false;
	}
	
	public function setNotes($notes){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$notes = self::validNotes($dbconn, $notes);
			if($notes !== false){
				mysqli_query($dbconn, "UPDATE Survey SET Notes='$notes' WHERE ID='" . $this->id . "'");
				$this->notes = $notes;
				return true;
			}
		}
		return false;
	}
	
	public function setWetLeaves($wetLeaves){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$wetLeaves = filter_var($wetLeaves, FILTER_VALIDATE_BOOLEAN);
			mysqli_query($dbconn, "UPDATE Survey SET WetLeaves='$wetLeaves' WHERE ID='" . $this->id . "'");
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
				$this->plantSpecies = $plantSpecies;
				return true;
			}
		}
		return false;
	}
	
	public function setNumberOfLeaves($numberOfLeaves){
		if(!$this->deleted && !$this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$numberOfLeaves = self::validNumberOfLeaves($dbconn, $numberOfLeaves);
			if($numberOfLeaves !== false){
				mysqli_query($dbconn, "UPDATE Survey SET NumberOfLeaves='$numberOfLeaves' WHERE ID='" . $this->id . "'");
				$this->numberOfLeaves = $numberOfLeaves;
				return true;
			}
		}
		return false;
	}
	
	public function setAverageLeafLength($averageLeafLength){
		if(!$this->deleted && !$this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$averageLeafLength = self::validAverageLeafLength($dbconn, $averageLeafLength);
			if($averageLeafLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET AverageLeafLength='$averageLeafLength' WHERE ID='" . $this->id . "'");
				$this->averageLeafLength = $averageLeafLength;
				return true;
			}
		}
		return false;
	}
	
	public function setHerbivoryScore($herbivoryScore){
		if(!$this->deleted && !$this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$herbivoryScore = self::validHerbivoryScore($dbconn, $herbivoryScore);
			if($herbivoryScore !== false){
				mysqli_query($dbconn, "UPDATE Survey SET HerbivoryScore='$herbivoryScore' WHERE ID='" . $this->id . "'");
				$this->herbivoryScore = $herbivoryScore;
				return true;
			}
		}
		return false;
	}
	
	public function setAverageNeedleLength($averageNeedleLength){
		if(!$this->deleted && $this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$averageNeedleLength = self::validAverageNeedleLength($dbconn, $averageNeedleLength);
			if($averageNeedleLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET AverageNeedleLength='$averageNeedleLength' WHERE ID='" . $this->id . "'");
				$this->averageNeedleLength = $averageNeedleLength;
				return true;
			}
		}
		return false;
	}
	
	public function setLinearBranchLength($linearBranchLength){
		if(!$this->deleted && $this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$linearBranchLength = self::validLinearBranchLength($dbconn, $linearBranchLength);
			if($linearBranchLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET LinearBranchLength='$linearBranchLength' WHERE ID='" . $this->id . "'");
				$this->linearBranchLength = $linearBranchLength;
				return true;
			}
		}
		return false;
	}
	
	public function setConifer($averageNeedleLength, $linearBranchLength){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$averageNeedleLength = self::validAverageNeedleLength($dbconn, $averageNeedleLength);
			$linearBranchLength = self::validLinearBranchLength($dbconn, $linearBranchLength);
			if($averageNeedleLength !== false && $linearBranchLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET NumberOfLeaves='-1', AverageLeafLength='-1', HerbivoryScore='-1', AverageNeedleLength='$averageNeedleLength', LinearBranchLength='$linearBranchLength' WHERE ID='" . $this->id . "'");
				$this->numberOfLeaves = -1;
				$this->averageLeafLength = -1;
				$this->herbivoryScore = -1;
				$this->averageNeedleLength = $averageNeedleLength;
				$this->linearBranchLength = $linearBranchLength;
				return true;
			}
		}
		return false;
	}
	
	public function setNonConifer($numberOfLeaves, $averageLeafLength, $herbivoryScore){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$numberOfLeaves = self::validNumberOfLeaves($dbconn, $numberOfLeaves);
			$averageLeafLength = self::validAverageLeafLength($dbconn, $averageLeafLength);
			$herbivoryScore = self::validHerbivoryScore($dbconn, $herbivoryScore);
			if($numberOfLeaves !== false && $averageLeafLength !== false && $herbivoryScore !== false){
				mysqli_query($dbconn, "UPDATE Survey SET NumberOfLeaves='$numberOfLeaves', AverageLeafLength='$averageLeafLength', HerbivoryScore='$herbivoryScore', AverageNeedleLength='-1', LinearBranchLength='-1' WHERE ID='" . $this->id . "'");
				$this->numberOfLeaves = $numberOfLeaves;
				$this->averageLeafLength = $averageLeafLength;
				$this->herbivoryScore = $herbivoryScore;
				$this->averageNeedleLength = -1;
				$this->linearBranchLength = -1;
				return true;
			}
		}
		return false;
	}
	
	public function setReviewedAndApproved($reviewedAndApproved, $isSuperUser, $qccomment, $overrides){
          // 0 = flagged, 1 = Approved, 3 = Rejected
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$reviewedAndApproved = filter_var($reviewedAndApproved, FILTER_VALIDATE_INT);
                        
			if ($reviewedAndApproved ===0 or $reviewedAndApproved>0) { //either update to 0 or update to something >0 , null and "" filtered out
                          $sql = "UPDATE Survey SET ";
                          if ($isSuperUser) {
                            $sql .= "`ReviewedAndApproved`";
                          } else {
                            $sql .= "`ReviewedAndApprovedSite`";
                          }
                          $sql .= "='$reviewedAndApproved'";
                          if (!empty($qccomment)) {
                            $sql .= " , `QCComment` = '" . mysqli_real_escape_string($dbconn, $qccomment) . "'";
                          }
                          $overFields = array("QCNumberOfLeavesOK", "QCAverageLeafLengthOK", "QCArthropodLengthOK", "QCArthropodQuantityOK");
                          foreach($overFields as $k => $over) {
                            $sql .= " , `$over` = ";
                            if (in_array($over, $overrides)) {
                              $sql .= "1";
                            } else {
                              $sql .= "null";
                            }
                          }
                          $sql .= " WHERE ID='" . $this->id . "'";
                          //error_log($sql);
                          mysqli_query($dbconn, $sql);	
                          $this->reviewedAndApproved = $reviewedAndApproved;
                          return true;
			}
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
			return true;
		}
	}
	
	public static function permanentDeleteByIDs($ids)
	{
		if(is_array($ids) && count($ids) > 0)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			mysqli_query($dbconn, "DELETE FROM `Survey` WHERE `ID` IN ('" . implode("', '", $ids) . "')");
			mysqli_query($dbconn, "DELETE FROM `ArthropodSighting` WHERE `SurveyFK` IN ('" . implode("', '", $ids) . "')");
			return true;
		}
	}
	
	public static function permanentDeleteAllLooseEnds(){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `Survey`.`ID` FROM `Survey` LEFT JOIN `Plant` ON `Survey`.`PlantFK`=`Plant`.`ID` WHERE `Plant`.`ID` IS NULL");
		$idsToDelete = array();
		while($row = mysqli_fetch_assoc($query)){
			$idsToDelete[] = $row["ID"];
		}
		self::permanentDeleteByIDs($idsToDelete);
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
		$notes = mysqli_real_escape_string($dbconn, htmlentities(rawurldecode($notes)));
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
		$plantSpeciesList = PlantSpeciesList();
		for($i = 0; $i < count($plantSpeciesList); $i++){
			$plantSpeciesList[$i][0] = trim(preg_replace('!\s+!', ' ', $plantSpeciesList[$i][0]));
			$plantSpeciesList[$i][1] = trim(preg_replace('!\s+!', ' ', $plantSpeciesList[$i][1]));
			if(strtolower($plantSpecies) == strtolower($plantSpeciesList[$i][1]) || strtolower($plantSpecies) == strtolower($plantSpeciesList[$i][0])){
				return ucfirst(strtolower($plantSpeciesList[$i][0]));
			}
		}
		return ucfirst(strtolower(trim(preg_replace('!\s+!', ' ', $plantSpecies))));
	}
	
	public static function validNumberOfLeaves($dbconn, $numberOfLeaves){
		$numberOfLeaves = intval($numberOfLeaves);
		if($numberOfLeaves >= 1){
			return $numberOfLeaves;
		}
		return false;
	}
	
	public static function validAverageLeafLength($dbconn, $averageLeafLength){
		$averageLeafLength = intval($averageLeafLength);
		if($averageLeafLength >= 1){
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
	
	public static function validAverageNeedleLength($dbconn, $averageNeedleLength){
		$averageNeedleLength = intval($averageNeedleLength);
		if($averageNeedleLength >= 1 && $averageNeedleLength <= 60){
			return $averageNeedleLength;
		}
		return false;
	}
	
	public static function validLinearBranchLength($dbconn, $linearBranchLength){
		$linearBranchLength = intval($linearBranchLength);
		if($linearBranchLength >= 1 && $linearBranchLength <= 500){
			return $linearBranchLength;
		}
		return false;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//FUNCTIONS
	public function addArthropodSighting($originalGroup, $length, $quantity, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $originalBeetleLarva){
		return ArthropodSighting::create($this, $originalGroup, $length, $quantity, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $originalBeetleLarva);
	}
}		
?>
